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
        Schema::create('study_plan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')
                ->references('id')
                ->on('study_plan')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('cycle_id')
                ->references('id')
                ->on('cycle')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('course_id')
                ->references('id')
                ->on('course')
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
        Schema::dropIfExists('study_plan_detail');
    }
};
