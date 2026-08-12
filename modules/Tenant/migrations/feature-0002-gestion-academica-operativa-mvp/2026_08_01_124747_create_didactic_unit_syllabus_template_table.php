<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('didactic_unit_syllabus_template', function (Blueprint $table) {
            $table->id();

            $table->foreignId('syllabus_template_id')
                  ->constrained('syllabus_templates')
                  ->cascadeOnDelete();
        
            $table->foreignId('didactic_unit_id')
                  ->constrained('course')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['syllabus_template_id', 'didactic_unit_id'], 'template_unit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('didactic_unit_syllabus_template');
    }
};
