<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('method');
            $table->text('url');
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();
            $table->integer('status_code');
            $table->integer('duration_ms');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_request_logs');
    }
};
