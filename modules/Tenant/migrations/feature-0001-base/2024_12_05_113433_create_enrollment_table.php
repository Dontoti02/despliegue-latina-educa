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
        Schema::create('enrollment_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('enrollment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_plan_id')
                ->references('id')
                ->on('student_plan')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('type_id')
                ->references('id')
                ->on('enrollment_type')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('period_id')
                ->references('id')
                ->on('period')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('cycle_id')
                ->references('id')
                ->on('cycle')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('shift_id')
                ->nullable()
                ->references('id')
                ->on('shift')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('section_id')
                ->nullable()
                ->references('id')
                ->on('section')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->boolean('is_approved')->nullable();
            $table->datetime('registration_date');

            $table->text('observations')->nullable();
            $table->boolean('is_full_payment')->nullable();

            $table->foreignId('scale_id')
                ->nullable()
                ->references('id')
                ->on('scale')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('scale_authorization_document_type')->nullable();
            $table->string('scale_authorization_document_number')->nullable();
            $table->string('scale_authorization_full_names')->nullable();

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
        Schema::dropIfExists('enrollment');
        Schema::dropIfExists('enrollment_type');
    }
};
