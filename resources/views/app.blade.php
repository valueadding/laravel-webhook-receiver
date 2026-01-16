<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Webhook Log Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .log-error { background-color: rgba(239, 68, 68, 0.1); }
        .log-warning { background-color: rgba(245, 158, 11, 0.1); }
        .log-info { background-color: rgba(59, 130, 246, 0.1); }
        .log-debug { background-color: rgba(107, 114, 128, 0.05); }
        pre { font-family: 'JetBrains Mono', 'Fira Code', monospace; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100">
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useCallback } = React;
        const API_PREFIX = '/api/{{ config("webhook-receiver.route_prefix", "webhook") }}';

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
            async get(endpoint) { return this.fetch(endpoint); },
            async post(endpoint, data) {
                return this.fetch(endpoint, { method: 'POST', body: JSON.stringify(data) });
            },
            async delete(endpoint) {
                return this.fetch(endpoint, { method: 'DELETE' });
            }
        };

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
                    if (res.ok) onLogin();
                    else setError(data.error || 'Login failed');
                } catch (err) {
                    setError('Network error');
                }
                setLoading(false);
            };

            return (
                <div className="min-h-screen flex items-center justify-center bg-gray-950">
                    <div className="bg-gray-900 p-6 rounded-xl shadow-2xl w-80 border border-gray-800">
                        <div className="flex items-center justify-center mb-6">
                            <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h1 className="text-xl font-semibold text-white">Log Viewer</h1>
                        </div>
                        <form onSubmit={handleSubmit}>
                            {error && <div className="bg-red-500/10 border border-red-500/20 text-red-400 px-3 py-2 rounded-lg mb-4 text-sm">{error}</div>}
                            <div className="mb-3">
                                <input type="text" placeholder="Username" value={username}
                                    onChange={(e) => setUsername(e.target.value)}
                                    className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                                    required />
                            </div>
                            <div className="mb-4">
                                <input type="password" placeholder="Password" value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                                    required />
                            </div>
                            <button type="submit" disabled={loading}
                                className="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-500 disabled:opacity-50 transition-colors text-sm font-medium">
                                {loading ? 'Signing in...' : 'Sign In'}
                            </button>
                        </form>
                    </div>
                </div>
            );
        }

        function LogDetail({ log, onClose, onDelete }) {
            if (!log) return null;

            const handleDelete = async (e) => {
                e.stopPropagation();
                if (confirm('Are you sure you want to delete this log?')) {
                    await onDelete(log.id);
                    onClose();
                }
            };

            return (
                <div className="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50" onClick={onClose}>
                    <div className="bg-gray-900 rounded-xl max-w-4xl w-full max-h-[85vh] overflow-hidden border border-gray-800 shadow-2xl" onClick={e => e.stopPropagation()}>
                        <div className="flex justify-between items-center px-4 py-3 border-b border-gray-800 bg-gray-900/50">
                            <div className="flex items-center gap-3">
                                <h2 className="text-base font-semibold text-white">Log Details</h2>
                                {log.viewed_at && (
                                    <span className="text-xs text-gray-500">Viewed {new Date(log.viewed_at).toLocaleString()}</span>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <button onClick={handleDelete} className="text-gray-500 hover:text-red-400 transition-colors p-1 hover:bg-gray-800 rounded" title="Delete log">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <button onClick={onClose} className="text-gray-500 hover:text-white transition-colors p-1 hover:bg-gray-800 rounded">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div className="p-4 overflow-auto max-h-[calc(85vh-52px)]">
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                                <div className="bg-gray-800/50 rounded-lg p-3">
                                    <div className="text-xs text-gray-500 mb-1">Level</div>
                                    <span className={`px-2 py-0.5 rounded text-xs font-medium ${getLevelClass(log.level_name)}`}>{log.level_name}</span>
                                </div>
                                <div className="bg-gray-800/50 rounded-lg p-3">
                                    <div className="text-xs text-gray-500 mb-1">Channel</div>
                                    <div className="text-sm text-white">{log.channel}</div>
                                </div>
                                <div className="bg-gray-800/50 rounded-lg p-3">
                                    <div className="text-xs text-gray-500 mb-1">Source</div>
                                    <div className="text-sm text-white">{log.source?.name || 'Unknown'}</div>
                                </div>
                                <div className="bg-gray-800/50 rounded-lg p-3">
                                    <div className="text-xs text-gray-500 mb-1">Occurrences</div>
                                    <div className="text-sm text-white font-mono">{log.occurrence_count}</div>
                                </div>
                                <div className="bg-gray-800/50 rounded-lg p-3">
                                    <div className="text-xs text-gray-500 mb-1">First Seen</div>
                                    <div className="text-sm text-white">{new Date(log.first_seen_at).toLocaleString()}</div>
                                </div>
                                <div className="bg-gray-800/50 rounded-lg p-3">
                                    <div className="text-xs text-gray-500 mb-1">Last Seen</div>
                                    <div className="text-sm text-white">{new Date(log.last_seen_at).toLocaleString()}</div>
                                </div>
                            </div>

                            <div className="mb-4">
                                <div className="text-xs text-gray-500 mb-2">Message</div>
                                <pre className="bg-gray-800 p-3 rounded-lg text-sm text-gray-200 whitespace-pre-wrap overflow-auto max-h-32">{log.message}</pre>
                            </div>

                            {log.context && Object.keys(log.context).length > 0 && (
                                <div className="mb-4">
                                    <div className="text-xs text-gray-500 mb-2">Context</div>
                                    <pre className="bg-gray-800 p-3 rounded-lg text-xs text-gray-300 overflow-auto max-h-96">{JSON.stringify(log.context, null, 2)}</pre>
                                </div>
                            )}

                            {log.extra && Object.keys(log.extra).length > 0 && (
                                <div>
                                    <div className="text-xs text-gray-500 mb-2">Extra</div>
                                    <pre className="bg-gray-800 p-3 rounded-lg text-xs text-gray-300 overflow-auto max-h-48">{JSON.stringify(log.extra, null, 2)}</pre>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            );
        }

        function HelpPage({ onClose }) {
            return (
                <div className="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50" onClick={onClose}>
                    <div className="bg-gray-900 rounded-xl max-w-2xl w-full max-h-[85vh] overflow-hidden border border-gray-800 shadow-2xl" onClick={e => e.stopPropagation()}>
                        <div className="flex justify-between items-center px-4 py-3 border-b border-gray-800 bg-gray-900/50">
                            <h2 className="text-base font-semibold text-white">Help & Documentation</h2>
                            <button onClick={onClose} className="text-gray-500 hover:text-white transition-colors p-1 hover:bg-gray-800 rounded">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div className="p-4 overflow-auto max-h-[calc(85vh-52px)] text-gray-300 text-sm space-y-4">
                            <section>
                                <h3 className="text-white font-medium mb-2">Overview</h3>
                                <p>This is a centralized log viewer that collects logs from multiple applications. Logs are sent via webhooks and stored in a central database for easy monitoring and debugging.</p>
                            </section>

                            <section>
                                <h3 className="text-white font-medium mb-2">Stats Dashboard</h3>
                                <p className="mb-2">The top section shows summary statistics:</p>
                                <ul className="list-disc list-inside space-y-1 text-gray-400">
                                    <li><strong className="text-gray-300">Total Logs</strong> - Total number of log entries (with current filters)</li>
                                    <li><strong className="text-gray-300">Sources</strong> - Number of applications sending logs</li>
                                    <li><strong className="text-gray-300">Today</strong> - Logs received today</li>
                                    <li><strong className="text-gray-300">Errors Today</strong> - Error-level logs received today</li>
                                    <li><strong className="text-gray-300">Unviewed</strong> - Logs that haven't been opened yet</li>
                                </ul>
                            </section>

                            <section>
                                <h3 className="text-white font-medium mb-2">Per-Source Breakdown</h3>
                                <p>Below the stats, you'll see a breakdown by source application. Click on a source to filter logs to only that application. Click again to clear the filter.</p>
                            </section>

                            <section>
                                <h3 className="text-white font-medium mb-2">Filtering</h3>
                                <p className="mb-2">Use the filter bar to narrow down logs:</p>
                                <ul className="list-disc list-inside space-y-1 text-gray-400">
                                    <li><strong className="text-gray-300">Source</strong> - Filter by sending application</li>
                                    <li><strong className="text-gray-300">Level</strong> - Filter by log level (ERROR, WARNING, INFO, DEBUG)</li>
                                    <li><strong className="text-gray-300">Channel</strong> - Filter by log channel</li>
                                    <li><strong className="text-gray-300">Search</strong> - Search in log messages</li>
                                </ul>
                            </section>

                            <section>
                                <h3 className="text-white font-medium mb-2">Log Table</h3>
                                <p className="mb-2">The table shows all logs with color coding:</p>
                                <ul className="list-disc list-inside space-y-1 text-gray-400">
                                    <li><span className="text-red-400">Red</span> - Errors, Critical, Alert, Emergency</li>
                                    <li><span className="text-yellow-400">Yellow</span> - Warnings</li>
                                    <li><span className="text-blue-400">Blue</span> - Info, Notice</li>
                                    <li><span className="text-gray-400">Gray</span> - Debug</li>
                                </ul>
                                <p className="mt-2">A blue dot indicates an unviewed log. Click a row to view details.</p>
                            </section>

                            <section>
                                <h3 className="text-white font-medium mb-2">Log Deduplication</h3>
                                <p>Identical logs (same level, message, channel) within a 5-minute window are grouped together. The "Count" column shows how many times the same log occurred. First/Last Seen timestamps track the time range.</p>
                            </section>

                            <section>
                                <h3 className="text-white font-medium mb-2">Actions</h3>
                                <ul className="list-disc list-inside space-y-1 text-gray-400">
                                    <li><strong className="text-gray-300">Mark All Viewed</strong> - Mark all logs (matching current filters) as viewed</li>
                                    <li><strong className="text-gray-300">Delete</strong> - Delete individual logs from the detail view</li>
                                </ul>
                            </section>

                            <section>
                                <h3 className="text-white font-medium mb-2">Retention</h3>
                                <p>Logs are automatically deleted after 7 days to keep the database clean.</p>
                            </section>
                        </div>
                    </div>
                </div>
            );
        }

        function getLevelClass(level) {
            switch(level?.toUpperCase()) {
                case 'ERROR': case 'CRITICAL': case 'ALERT': case 'EMERGENCY':
                    return 'bg-red-500/20 text-red-400';
                case 'WARNING':
                    return 'bg-yellow-500/20 text-yellow-400';
                case 'INFO': case 'NOTICE':
                    return 'bg-blue-500/20 text-blue-400';
                default:
                    return 'bg-gray-500/20 text-gray-400';
            }
        }

        function getRowClass(level) {
            switch(level?.toUpperCase()) {
                case 'ERROR': case 'CRITICAL': case 'ALERT': case 'EMERGENCY':
                    return 'log-error';
                case 'WARNING':
                    return 'log-warning';
                case 'INFO': case 'NOTICE':
                    return 'log-info';
                default:
                    return 'log-debug';
            }
        }

        function Dashboard({ onLogout }) {
            const [logs, setLogs] = useState([]);
            const [sources, setSources] = useState([]);
            const [levels, setLevels] = useState([]);
            const [channels, setChannels] = useState([]);
            const [stats, setStats] = useState({});
            const [loading, setLoading] = useState(true);
            const [selectedLog, setSelectedLog] = useState(null);
            const [showHelp, setShowHelp] = useState(false);
            const [filters, setFilters] = useState({ source_id: '', level: '', channel: '', search: '' });
            const [page, setPage] = useState(1);
            const [pagination, setPagination] = useState({});

            const buildFilterParams = useCallback(() => {
                const params = new URLSearchParams();
                if (filters.source_id) params.set('source_id', filters.source_id);
                if (filters.level) params.set('level', filters.level);
                if (filters.channel) params.set('channel', filters.channel);
                if (filters.search) params.set('search', filters.search);
                return params.toString();
            }, [filters]);

            useEffect(() => { loadFilters(); }, []);
            useEffect(() => { loadLogs(); loadStats(); }, [filters, page]);

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
                } catch (err) { console.error('Failed to load filters', err); }
            };

            const loadStats = async () => {
                try {
                    const filterParams = buildFilterParams();
                    const res = await api.get(`/viewer/stats${filterParams ? '?' + filterParams : ''}`);
                    setStats(await res.json());
                } catch (err) { console.error('Failed to load stats', err); }
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
                    setPagination({ current_page: data.current_page, last_page: data.last_page, total: data.total });
                } catch (err) { console.error('Failed to load logs', err); }
                setLoading(false);
            };

            const handleLogClick = async (log) => {
                // Fetch full log details (this also marks it as viewed)
                try {
                    const res = await api.get(`/viewer/logs/${log.id}`);
                    const fullLog = await res.json();
                    setSelectedLog(fullLog);
                    // Update the log in the list to show as viewed
                    setLogs(prev => prev.map(l => l.id === log.id ? { ...l, viewed_at: fullLog.viewed_at } : l));
                    loadStats(); // Refresh stats to update unviewed count
                } catch (err) {
                    console.error('Failed to load log details', err);
                }
            };

            const handleDelete = async (logId) => {
                try {
                    await api.delete(`/viewer/logs/${logId}`);
                    setLogs(prev => prev.filter(l => l.id !== logId));
                    loadStats();
                } catch (err) {
                    console.error('Failed to delete log', err);
                }
            };

            const handleMarkAllViewed = async () => {
                try {
                    const filterParams = buildFilterParams();
                    await api.post(`/viewer/logs/mark-all-viewed${filterParams ? '?' + filterParams : ''}`, {});
                    loadLogs();
                    loadStats();
                } catch (err) {
                    console.error('Failed to mark all as viewed', err);
                }
            };

            const handleLogout = async () => { await api.post('/auth/logout', {}); onLogout(); };
            const handleFilterChange = (key, value) => { setFilters(prev => ({ ...prev, [key]: value })); setPage(1); };
            const hasActiveFilters = filters.source_id || filters.level || filters.channel || filters.search;

            return (
                <div className="min-h-screen bg-gray-950">
                    <header className="bg-gray-900 border-b border-gray-800 sticky top-0 z-40">
                        <div className="max-w-7xl mx-auto px-4 py-2.5 flex justify-between items-center">
                            <div className="flex items-center">
                                <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-2">
                                    <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h1 className="text-base font-semibold text-white">Log Viewer</h1>
                                {hasActiveFilters && <span className="ml-2 px-1.5 py-0.5 bg-blue-600/20 text-blue-400 text-xs rounded">Filtered</span>}
                            </div>
                            <div className="flex items-center gap-3">
                                <button onClick={() => setShowHelp(true)} className="text-gray-400 hover:text-white text-sm transition-colors flex items-center gap-1">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Help
                                </button>
                                <button onClick={handleLogout} className="text-gray-400 hover:text-white text-sm transition-colors">Logout</button>
                            </div>
                        </div>
                    </header>

                    <main className="max-w-7xl mx-auto px-4 py-4">
                        {/* Summary Stats */}
                        <div className="grid grid-cols-5 gap-3 mb-3">
                            <div className="bg-gray-900 p-3 rounded-xl border border-gray-800">
                                <div className="text-xs text-gray-500">Total Logs</div>
                                <div className="text-xl font-semibold text-white">{stats.total_logs?.toLocaleString() || 0}</div>
                            </div>
                            <div className="bg-gray-900 p-3 rounded-xl border border-gray-800">
                                <div className="text-xs text-gray-500">Sources</div>
                                <div className="text-xl font-semibold text-white">{stats.total_sources || 0}</div>
                            </div>
                            <div className="bg-gray-900 p-3 rounded-xl border border-gray-800">
                                <div className="text-xs text-gray-500">Today</div>
                                <div className="text-xl font-semibold text-white">{stats.logs_today?.toLocaleString() || 0}</div>
                            </div>
                            <div className="bg-gray-900 p-3 rounded-xl border border-gray-800">
                                <div className="text-xs text-gray-500">Errors Today</div>
                                <div className="text-xl font-semibold text-red-400">{stats.errors_today?.toLocaleString() || 0}</div>
                            </div>
                            <div className="bg-gray-900 p-3 rounded-xl border border-gray-800">
                                <div className="text-xs text-gray-500">Unviewed</div>
                                <div className="text-xl font-semibold text-blue-400">{stats.unviewed?.toLocaleString() || 0}</div>
                            </div>
                        </div>

                        {/* Per-Source Stats */}
                        {stats.by_source?.length > 0 && (
                            <div className="bg-gray-900 rounded-xl border border-gray-800 mb-4 overflow-hidden">
                                <div className="px-3 py-2 border-b border-gray-800 flex items-center justify-between">
                                    <span className="text-xs font-medium text-gray-400">By Source</span>
                                </div>
                                <div className="divide-y divide-gray-800/50">
                                    {stats.by_source.map(source => (
                                        <div key={source.id}
                                            className={`px-3 py-2 flex items-center justify-between hover:bg-gray-800/30 cursor-pointer transition-colors ${filters.source_id == source.id ? 'bg-blue-600/10 border-l-2 border-blue-500' : ''}`}
                                            onClick={() => handleFilterChange('source_id', filters.source_id == source.id ? '' : source.id)}>
                                            <span className="text-sm text-gray-300">{source.name}</span>
                                            <div className="flex items-center gap-4 text-xs">
                                                <span className="text-gray-500">
                                                    <span className="text-gray-300 font-medium">{source.total}</span> total
                                                </span>
                                                <span className="text-gray-500">
                                                    <span className="text-gray-300 font-medium">{source.today}</span> today
                                                </span>
                                                {source.errors_today > 0 && (
                                                    <span className="text-red-400 font-medium">{source.errors_today} errors</span>
                                                )}
                                                {source.unviewed > 0 && (
                                                    <span className="text-blue-400 font-medium">{source.unviewed} unviewed</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Filters */}
                        <div className="bg-gray-900 p-3 rounded-xl border border-gray-800 mb-4">
                            <div className="flex gap-2 flex-wrap">
                                <select value={filters.source_id} onChange={(e) => handleFilterChange('source_id', e.target.value)}
                                    className="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-blue-500">
                                    <option value="">All Sources</option>
                                    {sources.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                                <select value={filters.level} onChange={(e) => handleFilterChange('level', e.target.value)}
                                    className="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-blue-500">
                                    <option value="">All Levels</option>
                                    {levels.map(l => <option key={l} value={l}>{l}</option>)}
                                </select>
                                <select value={filters.channel} onChange={(e) => handleFilterChange('channel', e.target.value)}
                                    className="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-blue-500">
                                    <option value="">All Channels</option>
                                    {channels.map(c => <option key={c} value={c}>{c}</option>)}
                                </select>
                                <input type="text" placeholder="Search..." value={filters.search}
                                    onChange={(e) => handleFilterChange('search', e.target.value)}
                                    className="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 flex-1 min-w-[150px]"
                                />
                                {hasActiveFilters && (
                                    <button onClick={() => { setFilters({ source_id: '', level: '', channel: '', search: '' }); setPage(1); }}
                                        className="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-sm text-gray-400 hover:text-white hover:border-gray-600 transition-colors">
                                        Clear
                                    </button>
                                )}
                                {stats.unviewed > 0 && (
                                    <button onClick={handleMarkAllViewed}
                                        className="px-3 py-1.5 bg-blue-600/20 border border-blue-600/30 rounded-lg text-sm text-blue-400 hover:bg-blue-600/30 transition-colors">
                                        Mark All Viewed
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Logs Table */}
                        <div className="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-800">
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-8"></th>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Count</th>
                                        <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last Seen</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-800/50">
                                    {loading ? (
                                        <tr><td colSpan="7" className="px-3 py-8 text-center text-gray-500 text-sm">Loading...</td></tr>
                                    ) : logs.length === 0 ? (
                                        <tr><td colSpan="7" className="px-3 py-8 text-center text-gray-500 text-sm">No logs found</td></tr>
                                    ) : logs.map(log => (
                                        <tr key={log.id} className={`${getRowClass(log.level_name)} cursor-pointer hover:bg-gray-800/50 transition-colors`}
                                            onClick={() => handleLogClick(log)}>
                                            <td className="px-3 py-2">
                                                {!log.viewed_at && (
                                                    <span className="w-2 h-2 bg-blue-500 rounded-full inline-block" title="Unviewed"></span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <span className={`px-1.5 py-0.5 rounded text-xs font-medium ${getLevelClass(log.level_name)}`}>
                                                    {log.level_name}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2 text-xs text-gray-300">{log.source?.name || '-'}</td>
                                            <td className="px-3 py-2 text-xs text-gray-300 max-w-md truncate font-mono">{log.message}</td>
                                            <td className="px-3 py-2 text-xs text-gray-400">{log.channel}</td>
                                            <td className="px-3 py-2 text-xs text-gray-400 font-mono">{log.occurrence_count}</td>
                                            <td className="px-3 py-2 text-xs text-gray-400">{new Date(log.last_seen_at).toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {pagination.last_page > 1 && (
                                <div className="px-3 py-2 border-t border-gray-800 flex items-center justify-between">
                                    <div className="text-xs text-gray-500">
                                        Page {pagination.current_page} of {pagination.last_page} ({pagination.total} total)
                                    </div>
                                    <div className="flex gap-1">
                                        <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                                            className="px-2 py-1 text-xs bg-gray-800 border border-gray-700 rounded text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                            Prev
                                        </button>
                                        <button onClick={() => setPage(p => Math.min(pagination.last_page, p + 1))} disabled={page === pagination.last_page}
                                            className="px-2 py-1 text-xs bg-gray-800 border border-gray-700 rounded text-gray-400 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                            Next
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </main>

                    {selectedLog && <LogDetail log={selectedLog} onClose={() => setSelectedLog(null)} onDelete={handleDelete} />}
                    {showHelp && <HelpPage onClose={() => setShowHelp(false)} />}
                </div>
            );
        }

        function App() {
            const [authenticated, setAuthenticated] = useState(null);

            useEffect(() => { checkAuth(); }, []);

            const checkAuth = async () => {
                try {
                    const res = await api.get('/auth/check');
                    const data = await res.json();
                    setAuthenticated(data.authenticated);
                } catch { setAuthenticated(false); }
            };

            if (authenticated === null) {
                return <div className="min-h-screen flex items-center justify-center bg-gray-950 text-gray-500">Loading...</div>;
            }

            return authenticated
                ? <Dashboard onLogout={() => setAuthenticated(false)} />
                : <Login onLogin={() => setAuthenticated(true)} />;
        }

        ReactDOM.createRoot(document.getElementById('root')).render(<App />);
    </script>
</body>
</html>
