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
        Schema::create('student_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->references('id')
                ->on('student')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('study_plan_id')
                ->references('id')
                ->on('study_plan')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->datetime('registration_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_graduated')->default(false);
            $table->dateTime('graduated_date')->nullable();
            $table->boolean('is_titled')->default(false);
            $table->dateTime('titled_date')->nullable();
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
        Schema::dropIfExists('student_plan');
    }
};
