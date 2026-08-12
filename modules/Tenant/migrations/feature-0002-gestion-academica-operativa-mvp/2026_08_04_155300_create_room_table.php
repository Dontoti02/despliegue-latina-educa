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
        Schema::create('room_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->foreignId('room_type_id')
                ->references('id')
                ->on('room_type')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->integer('capacity');
            $table->boolean('is_active');
            $table->string('image')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('room_reserve', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')
                ->references('id')
                ->on('room')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->date('date');
            $table->time('hour_start');
            $table->time('hour_end');
            $table->string('applicant');
            $table->text('motive');
            $table->boolean('is_confirmed')->nullable();
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
        Schema::dropIfExists('room_reserve');
        Schema::dropIfExists('room');
        Schema::dropIfExists('room_type');
    }
};
