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
        Schema::create('classroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')
                ->references('id')
                ->on('period')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('study_plan_detail_id')
                ->references('id')
                ->on('study_plan_detail')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('shift_id')
                ->references('id')
                ->on('shift')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('section_id')
                ->references('id')
                ->on('section')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('teacher_id')
                ->nullable()
                ->references('id')
                ->on('teacher')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->boolean('is_closed')->default(false);
            $table->text('avatar')->nullable();
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
        Schema::dropIfExists('classroom');
    }
};
