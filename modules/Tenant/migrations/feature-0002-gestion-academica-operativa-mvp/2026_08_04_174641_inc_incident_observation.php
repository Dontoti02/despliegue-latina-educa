<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inc_incident_observation', function (Blueprint $table) {
            $table->id();
            $table->text('request');
            $table->text('response')->nullable();
            $table->string('file_url', 255)->nullable();
            
            $table->foreignId('incident_id')->constrained('inc_incident');
            
            $table->foreignId('admin_user_id')->constrained('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inc_incident_observation');
    }
};