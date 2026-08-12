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
        Schema::create('family', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('document_type', 50);
            $table->string('document_number', 50);
            $table->string('full_names', 255);
            $table->string('phone', 20)->nullable();
            $table->string('cell_phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('relationship', 50);
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
        Schema::dropIfExists('family');
    }
};
