<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('app_url');
            $table->string('bearer_token', 64)->unique();
            $table->boolean('enabled')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('bearer_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_sources');
    }
};
