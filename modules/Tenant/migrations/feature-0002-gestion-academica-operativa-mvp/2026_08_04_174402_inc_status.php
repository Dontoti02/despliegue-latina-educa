<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inc_status', function (Blueprint $table) {
            $table->id(); 
            $table->string('name', 255);
            $table->boolean('closes_incident');
        });
        
        DB::table('inc_status')->insert([
            ['name' => 'REGISTRADO', 'closes_incident' => false],
            ['name' => 'EN REVISION', 'closes_incident' => false],
            ['name' => 'OBSERVADO', 'closes_incident' => false],
            ['name' => 'RECHAZADO', 'closes_incident' => true],
            ['name' => 'COMPLETADO', 'closes_incident' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inc_status');
    }
};