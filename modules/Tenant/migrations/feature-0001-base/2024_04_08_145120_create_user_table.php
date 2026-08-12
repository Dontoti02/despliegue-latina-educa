<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Seeders\Options;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->integer('level');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('rol_id')
                ->nullable()
                ->references('id')
                ->on('rol')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->string('reset_password_token')->nullable();
            $table->boolean('default_user')->default(false);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login')->nullable();
            $table->text('avatar')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('last_attempt')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('rol_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')
                ->references('id')
                ->on('rol')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->references('id')
                ->on('user')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')
                ->nullable()
                ->references('id')
                ->on('option')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('menu_id')
                ->references('id')
                ->on('menu')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name');
            $table->string('name_url');
            $table->string('icon')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('order');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('rol_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')
                ->references('id')
                ->on('rol')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('option_id')
                ->references('id')
                ->on('option')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        $roles = [
            [
                'id' => 1,
                'name' => 'SECRETARIO ACADÉMICO',
                'key' => 'rol_academic_secretary',
                'level' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'DOCENTE',
                'key' => 'rol_teacher',
                'level' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'ESTUDIANTE',
                'key' => 'rol_student',
                'level' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'ADMINISTRADOR',
                'key' => 'rol_admin',
                'level' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'COORDINADOR DE CAPACITACIONES',
                'key' => 'rol_admin_training',
                'level' => 2,
                'created_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'DOCENTE DE CAPACITACIONES',
                'key' => 'rol_teacher_training',
                'level' => 2,
                'created_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'ESTUDIANTE DE CAPACITACIONES',
                'key' => 'rol_student_training',
                'level' => 2,
                'created_at' => now(),
            ],
            [
                'id' => 8,
                'name' => 'EMPRESA',
                'key' => 'rol_company',
                'level' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 9,
                'name' => 'DIRECTOR GENERAL',
                'key' => 'rol_general_director',
                'level' => 1,
                'created_at' => now(),
            ],
        ];

        DB::table('rol')->insert($roles);

        $result = Options::get();

        DB::table('menu')->insert($result['menus']);
        DB::table('option')->insert($result['options']);
        DB::table('rol_option')->insert($result['rolOptions']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('rol_user');
        Schema::dropIfExists('menu');
        Schema::dropIfExists('option');
        Schema::dropIfExists('rol_option');
    }
};
