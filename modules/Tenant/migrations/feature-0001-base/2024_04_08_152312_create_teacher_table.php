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
        Schema::create('working_condition', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('working_condition_id')
                ->references('id')
                ->on('working_condition')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('study_program_id')
                ->nullable()
                ->references('id')
                ->on('study_program')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->dateTime('registration_date');
            $table->string('resolution_number')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher');
        Schema::dropIfExists('working_condition');
    }
};
