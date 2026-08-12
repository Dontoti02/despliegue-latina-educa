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
        Schema::create('study_plan_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('study_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')
                ->references('id')
                ->on('study_program')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('type_id')
                ->references('id')
                ->on('study_plan_type')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name');
            $table->string('year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('score_min_to_pass', 4, 2)->default(10.50);
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
        Schema::dropIfExists('study_plan');
    }
};
