<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inc_incident', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 100);
            $table->string('description', 256);
            
            $table->foreignId('incident_type_id')->constrained('inc_incident_type');
            $table->foreignId('status_id')->constrained('inc_status');
            
            $table->string('file_url', 255);
            $table->foreignId('user_id')->constrained('user');
            $table->string('incident_number', 20);
            $table->dateTime('register_date');
            $table->dateTime('completion_date')->nullable();
            $table->text('conclusion')->nullable();
            $table->foreignId('admin_user_id')->nullable()->constrained('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inc_incident');
    }
};