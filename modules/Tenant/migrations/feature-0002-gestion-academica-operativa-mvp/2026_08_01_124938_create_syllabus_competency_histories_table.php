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
        Schema::create('syllabus_competency_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('syllabus_instance_competency_id');
            $table->foreign('syllabus_instance_competency_id', 'sch_sic_fk')
                  ->references('id')
                  ->on('syllabus_instance_competencies')
                  ->cascadeOnDelete();

            $table->enum('previous_status', ['pending', 'in_progress', 'completed'])->nullable();
            $table->enum('new_status', ['pending', 'in_progress', 'completed']);

            $table->unsignedBigInteger('changed_by')->nullable();
            $table->foreign('changed_by', 'sch_changed_by_fk')
                  ->references('id')
                  ->on('user')
                  ->nullOnDelete();

            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabus_competency_histories');
    }
};
