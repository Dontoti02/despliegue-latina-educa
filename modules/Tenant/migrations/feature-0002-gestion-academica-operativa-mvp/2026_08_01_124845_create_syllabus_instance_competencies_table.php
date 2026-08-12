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
        Schema::create('syllabus_instance_competencies', function (Blueprint $table) {
          $table->id();
          
          $table->foreignId('syllabus_instance_id');
          $table->foreign('syllabus_instance_id', 'sic_si_fk')
              ->references('id')
              ->on('syllabus_instances')
              ->cascadeOnUpdate()
              ->cascadeOnDelete();

          $table->unsignedInteger('sort_order')->default(1);
          $table->string('name');
          $table->text('description')->nullable();
          $table->longText('rich_content')->nullable();
          $table->text('objective')->nullable();

          $table->enum('status', ['pending', 'in_progress', 'completed'])
                ->default('pending');

          $table->timestamp('status_changed_at')->useCurrent();
          $table->timestamp('started_at')->nullable();
          $table->timestamp('completed_at')->nullable();

          $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllabus_instance_competencies');
    }
};
