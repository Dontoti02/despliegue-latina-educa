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
        Schema::create('syllabus_template_competencies', function (Blueprint $table) {
            $table->id();
  
            $table->foreignId('syllabus_template_version_id');
            $table->foreign(
                'syllabus_template_version_id',
                'stc_stv_fk'
            )->references('id')
            ->on('syllabus_template_versions')
            ->cascadeOnDelete();
            
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('rich_content')->nullable();
            $table->text('objective')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabus_template_competencies');
    }
};
