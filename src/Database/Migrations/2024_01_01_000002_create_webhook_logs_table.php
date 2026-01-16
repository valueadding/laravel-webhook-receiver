<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_source_id')->constrained('webhook_sources')->onDelete('cascade');
            $table->string('log_hash', 32)->index();
            $table->integer('level');
            $table->string('level_name', 20)->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('datetime')->index();
            $table->string('channel', 100)->index();
            $table->json('extra')->nullable();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();

            // Composite index for deduplication lookups
            $table->index(['webhook_source_id', 'log_hash', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
