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
        Schema::create('syllabus_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syllabus_template_id')
                  ->constrained('syllabus_templates')
                  ->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->unique(
              ['syllabus_template_id', 'version_number'],
              'unique_template_version'
              );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabus_template_versions');
    }
};
