<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'audit_db';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('audit_db')->create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->string('institution_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_email')->nullable();

            $table->string('event_type', 30)->index();
            $table->string('method', 10);
            $table->text('request_url');
            $table->json('request_body')->nullable();
            $table->integer('status_code');
            $table->text('response_message')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->uuid('trace_id')->nullable()->index();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['institution_id', 'created_at']);
            $table->index(['institution_id', 'event_type']);
            $table->index(['institution_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::connection('audit_db')->dropIfExists('audit_logs');
    }
};
