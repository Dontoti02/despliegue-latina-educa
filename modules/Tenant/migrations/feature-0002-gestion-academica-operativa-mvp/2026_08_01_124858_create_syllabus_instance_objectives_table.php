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
        Schema::create('syllabus_instance_objectives', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('syllabus_instance_competency_id');
            $table->foreign('syllabus_instance_competency_id', 'sio_sic_fk')
                  ->references('id')
                  ->on('syllabus_instance_competencies')
                  ->cascadeOnDelete();

            $table->text('description');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabus_instance_objectives');
    }
};
