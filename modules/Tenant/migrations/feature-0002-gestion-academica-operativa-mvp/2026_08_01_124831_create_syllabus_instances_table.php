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
        Schema::create('syllabus_instances', function (Blueprint $table) {
        $table->id();
        $table->string('classroom_id')->index();
        $table->string('file')->nullable();
        $table->foreignId('syllabus_template_version_id')->nullable();
        $table->foreign(
            'syllabus_template_version_id',
            'si_stv_fk'
        )->references('id')
        ->on('syllabus_template_versions')
        ->nullOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabus_instances');
    }
};
