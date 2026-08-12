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
        Schema::create('course_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')
                ->nullable()
                ->references('id')
                ->on('study_program')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('module_id')
                ->nullable()
                ->references('id')
                ->on('module')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('type_id')
                ->nullable()
                ->references('id')
                ->on('course_type')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('year')->nullable();
            $table->decimal('credits', 4, 2)->nullable();
            $table->integer('hours')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('course');
        Schema::dropIfExists('course_type');
    }
};
