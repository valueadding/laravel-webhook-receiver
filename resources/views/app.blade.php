<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Webhook Log Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <style>
        .log-error { background-color: #fef2f2; }
        .log-warning { background-color: #fffbeb; }
        .log-info { background-color: #eff6ff; }
        .log-debug { background-color: #f9fafb; }
    </style>
</head>
<body class="bg-gray-100">
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect } = React;
        const API_PREFIX = '/api/{{ config("webhook-receiver.route_prefix", "webhook") }}';

        // API helper
        const api = {
            async fetch(endpoint, options = {}) {
                const response = await fetch(`${API_PREFIX}${endpoint}`, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        ...options.headers,
                    },
                    credentials: 'same-origin',
                });
                return response;
            },
            async get(endpoint) {
                return this.fetch(endpoint);
            },
            async post(endpoint, data) {
                return this.fetch(endpoint, {
                    method: 'POST',
                    body: JSON.stringify(data),
                });
            }
        };

        // Login Component
        function Login({ onLogin }) {
            const [username, setUsername] = useState('');
            const [password, setPassword] = useState('');
            const [error, setError] = useState('');
            const [loading, setLoading] = useState(false);

            const handleSubmit = async (e) => {
                e.preventDefault();
                setLoading(true);
                setError('');

                try {
                    const res = await api.post('/auth/login', { username, password });
                    const data = await res.json();

                    if (res.ok) {
                        onLogin();
                    } else {
                        setError(data.error || 'Login failed');
                    }
                } catch (err) {
                    setError('Network error');
                }
                setLoading(false);
            };

            return (
                <div className="min-h-screen flex items-center justify-center">
                    <div className="bg-white p-8 rounded-lg shadow-md w-96">
                        <h1 className="text-2xl font-bold mb-6 text-center">Webhook Log Viewer</h1>
                        <form onSubmit={handleSubmit}>
                            {error && <div className="bg-red-100 text-red-700 p-3 rounded mb-4">{error}</div>}
                            <div className="mb-4">
                                <label className="block text-gray-700 mb-2">Username</label>
                                <input
                                    type="text"
                                    value={username}
                                    onChange={(e) => setUsername(e.target.value)}
                                    className="w-full p-2 border rounded"
                                    required
                                />
                            </div>
                            <div className="mb-6">
                                <label className="block text-gray-700 mb-2">Password</label>
                                <input
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    className="w-full p-2 border rounded"
                                    required
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700 disabled:opacity-50"
                            >
                                {loading ? 'Logging in...' : 'Login'}
                            </button>
                        </form>
                    </div>
                </div>
            );
        }

        // Log Detail Modal
        function LogDetail({ log, onClose }) {
            if (!log) return null;

            return (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                    <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-auto">
                        <div className="p-6">
                            <div className="flex justify-between items-start mb-4">
                                <h2 className="text-xl font-bold">Log Details</h2>
                                <button onClick={onClose} className="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                            </div>

                            <div className="grid grid-cols-2 gap-4 mb-4">
                                <div><strong>Level:</strong> <span className={`px-2 py-1 rounded text-sm ${getLevelClass(log.level_name)}`}>{log.level_name}</span></div>
                                <div><strong>Channel:</strong> {log.channel}</div>
                                <div><strong>Source:</strong> {log.source?.name || 'Unknown'}</div>
                                <div><strong>Occurrences:</strong> {log.occurrence_count}</div>
                                <div><strong>First Seen:</strong> {new Date(log.first_seen_at).toLocaleString()}</div>
                                <div><strong>Last Seen:</strong> {new Date(log.last_seen_at).toLocaleString()}</div>
                            </div>

                            <div className="mb-4">
                                <strong>Message:</strong>
                                <pre className="bg-gray-100 p-4 rounded mt-2 whitespace-pre-wrap overflow-auto">{log.message}</pre>
                            </div>

                            {log.context && Object.keys(log.context).length > 0 && (
                                <div className="mb-4">
                                    <strong>Context:</strong>
                                    <pre className="bg-gray-100 p-4 rounded mt-2 text-sm overflow-auto">{JSON.stringify(log.context, null, 2)}</pre>
                                </div>
                            )}

                            {log.extra && Object.keys(log.extra).length > 0 && (
                                <div className="mb-4">
                                    <strong>Extra:</strong>
                                    <pre className="bg-gray-100 p-4 rounded mt-2 text-sm overflow-auto">{JSON.stringify(log.extra, null, 2)}</pre>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            );
        }

        function getLevelClass(level) {
            switch(level?.toUpperCase()) {
                case 'ERROR':
                case 'CRITICAL':
                case 'ALERT':
                case 'EMERGENCY':
                    return 'bg-red-100 text-red-800';
                case 'WARNING':
                    return 'bg-yellow-100 text-yellow-800';
                case 'INFO':
                case 'NOTICE':
                    return 'bg-blue-100 text-blue-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }

        function getRowClass(level) {
            switch(level?.toUpperCase()) {
                case 'ERROR':
                case 'CRITICAL':
                case 'ALERT':
                case 'EMERGENCY':
                    return 'log-error';
                case 'WARNING':
                    return 'log-warning';
                case 'INFO':
                case 'NOTICE':
                    return 'log-info';
                default:
                    return 'log-debug';
            }
        }

        // Main Dashboard
        function Dashboard({ onLogout }) {
            const [logs, setLogs] = useState([]);
            const [sources, setSources] = useState([]);
            const [levels, setLevels] = useState([]);
            const [channels, setChannels] = useState([]);
            const [stats, setStats] = useState({});
            const [loading, setLoading] = useState(true);
            const [selectedLog, setSelectedLog] = useState(null);

            // Filters
            const [filters, setFilters] = useState({
                source_id: '',
                level: '',
                channel: '',
                search: '',
            });
            const [page, setPage] = useState(1);
            const [pagination, setPagination] = useState({});

            useEffect(() => {
                loadFilters();
                loadStats();
            }, []);

            useEffect(() => {
                loadLogs();
            }, [filters, page]);

            const loadFilters = async () => {
                try {
                    const [sourcesRes, levelsRes, channelsRes] = await Promise.all([
                        api.get('/viewer/sources'),
                        api.get('/viewer/levels'),
                        api.get('/viewer/channels'),
                    ]);
                    setSources(await sourcesRes.json());
                    setLevels(await levelsRes.json());
                    setChannels(await channelsRes.json());
                } catch (err) {
                    console.error('Failed to load filters', err);
                }
            };

            const loadStats = async () => {
                try {
                    const res = await api.get('/viewer/stats');
                    setStats(await res.json());
                } catch (err) {
                    console.error('Failed to load stats', err);
                }
            };

            const loadLogs = async () => {
                setLoading(true);
                try {
                    const params = new URLSearchParams();
                    params.set('page', page);
                    if (filters.source_id) params.set('source_id', filters.source_id);
                    if (filters.level) params.set('level', filters.level);
                    if (filters.channel) params.set('channel', filters.channel);
                    if (filters.search) params.set('search', filters.search);

                    const res = await api.get(`/viewer/logs?${params}`);
                    const data = await res.json();
                    setLogs(data.data || []);
                    setPagination({
                        current_page: data.current_page,
                        last_page: data.last_page,
                        total: data.total,
                    });
                } catch (err) {
                    console.error('Failed to load logs', err);
                }
                setLoading(false);
            };

            const handleLogout = async () => {
                await api.post('/auth/logout', {});
                onLogout();
            };

            const handleFilterChange = (key, value) => {
                setFilters(prev => ({ ...prev, [key]: value }));
                setPage(1);
            };

            return (
                <div className="min-h-screen">
                    {/* Header */}
                    <header className="bg-white shadow">
                        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                            <h1 className="text-xl font-bold">Webhook Log Viewer</h1>
                            <button onClick={handleLogout} className="text-gray-600 hover:text-gray-900">Logout</button>
                        </div>
                    </header>

                    <main className="max-w-7xl mx-auto px-4 py-6">
                        {/* Stats */}
                        <div className="grid grid-cols-4 gap-4 mb-6">
                            <div className="bg-white p-4 rounded shadow">
                                <div className="text-gray-500 text-sm">Total Logs</div>
                                <div className="text-2xl font-bold">{stats.total_logs?.toLocaleString() || 0}</div>
                            </div>
                            <div className="bg-white p-4 rounded shadow">
                                <div className="text-gray-500 text-sm">Sources</div>
                                <div className="text-2xl font-bold">{stats.total_sources || 0}</div>
                            </div>
                            <div className="bg-white p-4 rounded shadow">
                                <div className="text-gray-500 text-sm">Logs Today</div>
                                <div className="text-2xl font-bold">{stats.logs_today?.toLocaleString() || 0}</div>
                            </div>
                            <div className="bg-white p-4 rounded shadow">
                                <div className="text-gray-500 text-sm">Errors Today</div>
                                <div className="text-2xl font-bold text-red-600">{stats.errors_today?.toLocaleString() || 0}</div>
                            </div>
                        </div>

                        {/* Filters */}
                        <div className="bg-white p-4 rounded shadow mb-6">
                            <div className="grid grid-cols-5 gap-4">
                                <select
                                    value={filters.source_id}
                                    onChange={(e) => handleFilterChange('source_id', e.target.value)}
                                    className="p-2 border rounded"
                                >
                                    <option value="">All Sources</option>
                                    {sources.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                                <select
                                    value={filters.level}
                                    onChange={(e) => handleFilterChange('level', e.target.value)}
                                    className="p-2 border rounded"
                                >
                                    <option value="">All Levels</option>
                                    {levels.map(l => <option key={l} value={l}>{l}</option>)}
                                </select>
                                <select
                                    value={filters.channel}
                                    onChange={(e) => handleFilterChange('channel', e.target.value)}
                                    className="p-2 border rounded"
                                >
                                    <option value="">All Channels</option>
                                    {channels.map(c => <option key={c} value={c}>{c}</option>)}
                                </select>
                                <input
                                    type="text"
                                    placeholder="Search messages..."
                                    value={filters.search}
                                    onChange={(e) => handleFilterChange('search', e.target.value)}
                                    className="p-2 border rounded"
                                />
                                <button
                                    onClick={() => { setFilters({ source_id: '', level: '', channel: '', search: '' }); setPage(1); }}
                                    className="p-2 bg-gray-200 rounded hover:bg-gray-300"
                                >
                                    Clear Filters
                                </button>
                            </div>
                        </div>

                        {/* Logs Table */}
                        <div className="bg-white rounded shadow overflow-hidden">
                            <table className="w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Seen</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {loading ? (
                                        <tr><td colSpan="6" className="px-4 py-8 text-center text-gray-500">Loading...</td></tr>
                                    ) : logs.length === 0 ? (
                                        <tr><td colSpan="6" className="px-4 py-8 text-center text-gray-500">No logs found</td></tr>
                                    ) : logs.map(log => (
                                        <tr
                                            key={log.id}
                                            className={`${getRowClass(log.level_name)} cursor-pointer hover:opacity-80`}
                                            onClick={() => setSelectedLog(log)}
                                        >
                                            <td className="px-4 py-3">
                                                <span className={`px-2 py-1 rounded text-xs ${getLevelClass(log.level_name)}`}>
                                                    {log.level_name}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm">{log.source?.name || '-'}</td>
                                            <td className="px-4 py-3 text-sm max-w-md truncate">{log.message}</td>
                                            <td className="px-4 py-3 text-sm">{log.channel}</td>
                                            <td className="px-4 py-3 text-sm">{log.occurrence_count}</td>
                                            <td className="px-4 py-3 text-sm">{new Date(log.last_seen_at).toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {/* Pagination */}
                            {pagination.last_page > 1 && (
                                <div className="px-4 py-3 border-t flex items-center justify-between">
                                    <div className="text-sm text-gray-500">
                                        Showing page {pagination.current_page} of {pagination.last_page} ({pagination.total} total)
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() => setPage(p => Math.max(1, p - 1))}
                                            disabled={page === 1}
                                            className="px-3 py-1 border rounded disabled:opacity-50"
                                        >
                                            Previous
                                        </button>
                                        <button
                                            onClick={() => setPage(p => Math.min(pagination.last_page, p + 1))}
                                            disabled={page === pagination.last_page}
                                            className="px-3 py-1 border rounded disabled:opacity-50"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </main>

                    {selectedLog && <LogDetail log={selectedLog} onClose={() => setSelectedLog(null)} />}
                </div>
            );
        }

        // App Root
        function App() {
            const [authenticated, setAuthenticated] = useState(null);

            useEffect(() => {
                checkAuth();
            }, []);

            const checkAuth = async () => {
                try {
                    const res = await api.get('/auth/check');
                    const data = await res.json();
                    setAuthenticated(data.authenticated);
                } catch {
                    setAuthenticated(false);
                }
            };

            if (authenticated === null) {
                return <div className="min-h-screen flex items-center justify-center">Loading...</div>;
            }

            return authenticated
                ? <Dashboard onLogout={() => setAuthenticated(false)} />
                : <Login onLogin={() => setAuthenticated(true)} />;
        }

        ReactDOM.createRoot(document.getElementById('root')).render(<App />);
    </script>
</body>
</html>
