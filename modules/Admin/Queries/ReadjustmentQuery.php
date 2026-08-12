<?php

namespace Modules\Admin\Queries;

use Illuminate\Support\Facades\DB;

class ReadjustmentQuery
{
    private static function insert(array $records, string $databaseName, string $tableName)
    {
        foreach (array_chunk($records, 1000) as $chunk) {
            DB::table("$databaseName.$tableName")->insert($chunk);
        }
    }

    public static function cycles(string $databaseName, array $records)
    {
        $log = [];

        $updatedCycles = $records['updatedCycles'];

        // Actualizar la tabla "cycle"
        DB::statement("
            ALTER TABLE $databaseName.cycle
            ADD COLUMN `order` INT NULL AFTER name;
        ");

        $log[] = "$databaseName | Tabla 'cycle' actualizada.";

        // Actualizar registros en la tabla "cycle"
        foreach ($updatedCycles as $row) {
            DB::table("$databaseName.cycle")
                ->where('id', $row['id'])
                ->update([
                    'order' => $row['order'],
                ]);
        }

        $log[] = "$databaseName | Registros actualizados en la tabla 'cycle'.";

        return $log;
    }

    public static function programsAndPlans(string $databaseName, array $records)
    {
        $log = [];

        $createdStudyPlanTypes = $records['createdStudyPlanTypes'];
        $createdStudyPlans = $records['createdStudyPlans'];

        // Actualizar la tabla "study_program"
        DB::statement("
            ALTER TABLE $databaseName.study_program
            ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER name;
        ");

        $log[] = "$databaseName | Tabla 'study_program' actualizada.";

        // Crear la tabla "study_plan_type"
        DB::statement("
            CREATE TABLE $databaseName.study_plan_type (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            );
        ");

        $log[] = "$databaseName | Tabla 'study_plan_type' creada.";

        // Insertar registros en la tabla "study_plan_type"
        self::insert($createdStudyPlanTypes, $databaseName, 'study_plan_type');

        $log[] = "$databaseName | Registros insertados en la tabla 'study_plan_type'.";

        // Eliminar la tabla "study_plan"
        DB::statement("DROP TABLE IF EXISTS $databaseName.study_plan;");

        $log[] = "$databaseName | Tabla 'study_plan' eliminada.";

        // Crear la tabla "study_plan"
        DB::statement("
            CREATE TABLE $databaseName.study_plan (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                study_program_id BIGINT UNSIGNED NOT NULL,
                type_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(255) NOT NULL,
                year VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                score_min_to_pass DECIMAL(4, 2) NOT NULL DEFAULT 10.50,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (study_program_id) REFERENCES $databaseName.study_program(id),
                FOREIGN KEY (type_id) REFERENCES $databaseName.study_plan_type(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'study_plan' creada.";

        // Insertar registros en la tabla "study_plan"
        self::insert($createdStudyPlans, $databaseName, 'study_plan');

        $log[] = "$databaseName | Registros insertados en la tabla 'study_plan'.";

        // Eliminar la tabla "program_plan"
        DB::statement("DROP TABLE IF EXISTS $databaseName.program_plan;");

        $log[] = "$databaseName | Tabla 'program_plan' eliminada.";

        return $log;
    }

    public static function coursesAndTeachersAndClassrooms(string $databaseName, array $records)
    {
        $log = [];

        $createdCourses = $records['createdCourses'];
        $createdStudyPlanDetails = $records['createdStudyPlanDetails'];
        $createdWorkingConditions = $records['createdWorkingConditions'];
        $createdTeachers = $records['createdTeachers'];
        $updatedClassrooms = $records['updatedClassrooms'];

        // Crear la tabla "module_type"
        DB::statement("
            CREATE TABLE $databaseName.module_type (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            );
        ");

        $log[] = "$databaseName | Tabla 'module_type' creada.";

        // Crear la tabla "module"
        DB::statement("
            CREATE TABLE $databaseName.module (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                study_program_id BIGINT UNSIGNED NOT NULL,
                type_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(255) NOT NULL,
                year VARCHAR(255) NOT NULL,
                `order` INT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (study_program_id) REFERENCES $databaseName.study_program(id),
                FOREIGN KEY (type_id) REFERENCES $databaseName.module_type(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'module' creada.";

        // Crear la tabla "course_type"
        DB::statement("
            CREATE TABLE $databaseName.course_type (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            );
        ");

        $log[] = "$databaseName | Tabla 'course_type' creada.";

        // Eliminar la tabla "course"
        DB::statement("DROP TABLE IF EXISTS $databaseName.course;");

        $log[] = "$databaseName | Tabla 'course' eliminada.";

        // Crear la tabla "course"
        DB::statement("
            CREATE TABLE $databaseName.course (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                study_program_id BIGINT UNSIGNED NULL,
                module_id BIGINT UNSIGNED NULL,
                type_id BIGINT UNSIGNED NULL,
                code VARCHAR(255) NULL,
                name VARCHAR(255) NOT NULL,
                year VARCHAR(255) NULL,
                credits DECIMAL(4, 2) NULL,
                hours INT NULL,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (study_program_id) REFERENCES $databaseName.study_program(id),
                FOREIGN KEY (module_id) REFERENCES $databaseName.module(id),
                FOREIGN KEY (type_id) REFERENCES $databaseName.course_type(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'course' creada.";

        // Insertar registros en la tabla "course"
        self::insert($createdCourses, $databaseName, 'course');

        $log[] = "$databaseName | Registros insertados en la tabla 'course'.";

        // Crear la tabla "study_plan_detail"
        DB::statement("
            CREATE TABLE $databaseName.study_plan_detail (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                study_plan_id BIGINT UNSIGNED NOT NULL,
                cycle_id BIGINT UNSIGNED NOT NULL,
                course_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (study_plan_id) REFERENCES $databaseName.study_plan(id),
                FOREIGN KEY (cycle_id) REFERENCES $databaseName.cycle(id),
                FOREIGN KEY (course_id) REFERENCES $databaseName.course(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'study_plan_detail' creada.";

        // Insertar registros en la tabla "study_plan_detail"
        self::insert($createdStudyPlanDetails, $databaseName, 'study_plan_detail');

        $log[] = "$databaseName | Registros insertados en la tabla 'study_plan_detail'.";

        // Crear la tabla "working_condition"
        DB::statement("
            CREATE TABLE $databaseName.working_condition (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,                
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            );
        ");

        $log[] = "$databaseName | Tabla 'working_condition' creada.";

        // Insertar registros en la tabla "working_condition"
        self::insert($createdWorkingConditions, $databaseName, 'working_condition');

        $log[] = "$databaseName | Registros insertados en la tabla 'working_condition'.";

        // Eliminar la tabla "teacher"
        DB::statement("DROP TABLE IF EXISTS $databaseName.teacher;");

        $log[] = "$databaseName | Tabla 'teacher' eliminada.";

        // Crear la tabla "teacher"
        DB::statement("
            CREATE TABLE $databaseName.teacher (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                person_id BIGINT UNSIGNED NOT NULL,
                working_condition_id BIGINT UNSIGNED NOT NULL,
                study_program_id BIGINT UNSIGNED NULL,
                registration_date DATETIME NOT NULL,
                resolution_number VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (person_id) REFERENCES $databaseName.person(id),
                FOREIGN KEY (working_condition_id) REFERENCES $databaseName.working_condition(id),
                FOREIGN KEY (study_program_id) REFERENCES $databaseName.study_program(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'teacher' creada.";

        // Insertar registros en la tabla "teacher"
        self::insert($createdTeachers, $databaseName, 'teacher');

        $log[] = "$databaseName | Registros insertados en la tabla 'teacher'.";

        // Actualizar la tabla "classroom"
        DB::statement("
            ALTER TABLE $databaseName.classroom
            DROP FOREIGN KEY classroom_course_id_foreign,
            DROP COLUMN course_id,
            ADD COLUMN study_plan_detail_id BIGINT UNSIGNED NULL AFTER period_id,
            ADD COLUMN teacher_id BIGINT UNSIGNED NULL AFTER shift_id,
            ADD COLUMN is_closed TINYINT(1) NOT NULL DEFAULT 0 AFTER teacher_id,
            ADD FOREIGN KEY (study_plan_detail_id) REFERENCES $databaseName.study_plan_detail(id),
            ADD FOREIGN KEY (teacher_id) REFERENCES $databaseName.teacher(id);
        ");

        $log[] = "$databaseName | Tabla 'classroom' actualizada.";

        // Actualizar registros en la tabla "classroom"
        foreach ($updatedClassrooms as $row) {
            DB::table("$databaseName.classroom")
                ->where('id', $row['id'])
                ->update([
                    'study_plan_detail_id' => $row['study_plan_detail_id'],
                    'teacher_id' => $row['teacher_id'],
                ]);
        }

        $log[] = "$databaseName | Registros actualizados en la tabla 'classroom'.";

        // Actualizar la tabla "classroom"
        DB::statement("
            ALTER TABLE $databaseName.classroom
            MODIFY COLUMN study_plan_detail_id BIGINT UNSIGNED NOT NULL;
        ");

        $log[] = "$databaseName | Tabla 'classroom' actualizada (modificación de columna).";

        // Eliminar la tabla "staff"
        DB::statement("DROP TABLE IF EXISTS $databaseName.staff;");

        $log[] = "$databaseName | Tabla 'staff' eliminada.";

        // Actualizar la tabla "person"
        DB::statement("
            ALTER TABLE $databaseName.person
            DROP COLUMN email;
        ");

        $log[] = "$databaseName | Tabla 'person' actualizada.";

        // Renombrar la tabla "person_aditional_data" a "person_additional_data"
        DB::statement("RENAME TABLE $databaseName.person_aditional_data TO $databaseName.person_additional_data;");

        $log[] = "$databaseName | Tabla 'person_aditional_data' renombrada a 'person_additional_data'.";

        // Actualizar la tabla "participant"
        DB::statement("
            ALTER TABLE $databaseName.participant
            ADD COLUMN is_approved TINYINT(1) NULL AFTER score;
        ");

        $log[] = "$databaseName | Tabla 'participant' actualizada.";

        return $log;
    }

    public static function periods(string $databaseName, array $records)
    {
        $log = [];

        $updatedPeriods = $records['updatedPeriods'];

        // Actualizar la tabla "period"
        DB::statement("
            ALTER TABLE $databaseName.period
            DROP COLUMN status,
            ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 0 AFTER name,
            MODIFY COLUMN start_date DATETIME NULL AFTER is_current,
            MODIFY COLUMN end_date DATETIME NULL AFTER start_date,
            ADD COLUMN enrollment_start_date DATETIME NULL AFTER end_date,
            ADD COLUMN enrollment_end_date DATETIME NULL AFTER enrollment_start_date,
            ADD COLUMN classroom_start_date DATETIME NULL AFTER enrollment_end_date,
            ADD COLUMN classroom_end_date DATETIME NULL AFTER classroom_start_date,
            ADD COLUMN type_min_requirement_to_pass TINYINT(1) NOT NULL DEFAULT 1 AFTER classroom_end_date,
            ADD COLUMN min_requirement_to_pass DECIMAL(5, 2) NOT NULL DEFAULT 50 AFTER type_min_requirement_to_pass,
            ADD COLUMN is_required_enrollment_payment TINYINT(1) NOT NULL DEFAULT 0 AFTER min_requirement_to_pass;
        ");

        $log[] = "$databaseName | Tabla 'period' actualizada.";

        // Actualizar registros en la tabla "period"
        foreach ($updatedPeriods as $row) {
            DB::table("$databaseName.period")
                ->where('id', $row['id'])
                ->update([
                    'is_current' => $row['is_current'],
                ]);
        }

        $log[] = "$databaseName | Registros actualizados en la tabla 'period'.";

        return $log;
    }

    public static function studentsAndEnrollments(string $databaseName, array $records)
    {
        $log = [];

        $createdStudents = $records['createdStudents'];
        $createdStudentPlans = $records['createdStudentPlans'];
        $createdEnrollmentTypes = $records['createdEnrollmentTypes'];
        $createdEnrollments = $records['createdEnrollments'];

        // Eliminar la tabla "student"
        DB::statement("DROP TABLE IF EXISTS $databaseName.student;");

        $log[] = "$databaseName | Tabla 'student' eliminada.";

        // Crear la tabla "student"
        DB::statement("
            CREATE TABLE $databaseName.student (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                person_id BIGINT UNSIGNED NOT NULL,
                code VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (person_id) REFERENCES $databaseName.person(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'student' creada.";

        // Insertar registros en la tabla "student"
        self::insert($createdStudents, $databaseName, 'student');

        $log[] = "$databaseName | Registros insertados en la tabla 'student'.";

        // Crear la tabla "student_plan"
        DB::statement("
            CREATE TABLE $databaseName.student_plan (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id BIGINT UNSIGNED NOT NULL,
                study_plan_id BIGINT UNSIGNED NOT NULL,
                registration_date DATETIME NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                is_graduated TINYINT(1) NOT NULL DEFAULT 0,
                graduated_date DATETIME NULL,
                is_titled TINYINT(1) NOT NULL DEFAULT 0,
                titled_date DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (student_id) REFERENCES $databaseName.student(id),
                FOREIGN KEY (study_plan_id) REFERENCES $databaseName.study_plan(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'student_plan' creada.";

        // Insertar registros en la tabla "student_plan"
        self::insert($createdStudentPlans, $databaseName, 'student_plan');

        $log[] = "$databaseName | Registros insertados en la tabla 'student_plan'.";

        // Crear la tabla "enrollment_type"
        DB::statement("
            CREATE TABLE $databaseName.enrollment_type (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            );
        ");

        $log[] = "$databaseName | Tabla 'enrollment_type' creada.";

        // Insertar registros en la tabla "enrollment_type"
        self::insert($createdEnrollmentTypes, $databaseName, 'enrollment_type');

        // Eliminar la tabla "enrollment"
        DB::statement("DROP TABLE IF EXISTS $databaseName.enrollment;");

        $log[] = "$databaseName | Tabla 'enrollment' eliminada.";

        // Crear la tabla "enrollment"
        DB::statement("
            CREATE TABLE $databaseName.enrollment (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_plan_id BIGINT UNSIGNED NOT NULL,
                type_id BIGINT UNSIGNED NOT NULL,
                period_id BIGINT UNSIGNED NOT NULL,
                cycle_id BIGINT UNSIGNED NOT NULL,
                shift_id BIGINT UNSIGNED NULL,
                section_id BIGINT UNSIGNED NULL,
                is_approved TINYINT(1) NULL,
                registration_date DATETIME NOT NULL,

                observations TEXT NULL,
                is_full_payment TINYINT(1) NULL,

                scale_id BIGINT UNSIGNED NULL,
                scale_authorization_document_type VARCHAR(255) NULL,
                scale_authorization_document_number VARCHAR(255) NULL,
                scale_authorization_full_names VARCHAR(255) NULL,

                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                FOREIGN KEY (student_plan_id) REFERENCES $databaseName.student_plan(id),
                FOREIGN KEY (type_id) REFERENCES $databaseName.enrollment_type(id),
                FOREIGN KEY (period_id) REFERENCES $databaseName.period(id),
                FOREIGN KEY (cycle_id) REFERENCES $databaseName.cycle(id),
                FOREIGN KEY (shift_id) REFERENCES $databaseName.shift(id),
                FOREIGN KEY (section_id) REFERENCES $databaseName.section(id),
                FOREIGN KEY (scale_id) REFERENCES $databaseName.scale(id)
            );
        ");

        $log[] = "$databaseName | Tabla 'enrollment' creada.";

        // Insertar registros en la tabla "enrollment"
        self::insert($createdEnrollments, $databaseName, 'enrollment');

        $log[] = "$databaseName | Registros insertados en la tabla 'enrollment'.";

        return $log;
    }

    public static function options(string $databaseName, array $records)
    {
        $log = [];

        $createdMenus = $records['createdMenus'];
        $createdOptions = $records['createdOptions'];
        $createdRolOptions = $records['createdRolOptions'];

        // Truncate a la tabla "menu"
        DB::statement("TRUNCATE TABLE $databaseName.menu;");

        $log[] = "$databaseName | Tabla 'menu' truncada.";

        // Actualizar la tabla "menu"
        DB::statement("
            ALTER TABLE $databaseName.menu
            ADD COLUMN `order` INT NULL AFTER name;
        ");

        $log[] = "$databaseName | Tabla 'menu' actualizada.";

        // Insertar registros en la tabla "menu"
        self::insert($createdMenus, $databaseName, 'menu');

        $log[] = "$databaseName | Registros insertados en la tabla 'menu'.";

        // Truncate a la tabla "option"
        DB::statement("TRUNCATE TABLE $databaseName.option;");

        $log[] = "$databaseName | Tabla 'option' truncada.";

        // Actualizar la tabla "option"
        DB::statement("
            ALTER TABLE $databaseName.option
            ADD COLUMN `order` INT NULL AFTER name;
        ");

        $log[] = "$databaseName | Tabla 'option' actualizada.";

        // Insertar registros en la tabla "option"
        self::insert($createdOptions, $databaseName, 'option');

        $log[] = "$databaseName | Registros insertados en la tabla 'option'.";

        // Truncate a la tabla "rol_option"
        DB::statement("TRUNCATE TABLE $databaseName.rol_option;");

        $log[] = "$databaseName | Tabla 'rol_option' truncada.";

        // Insertar registros en la tabla "rol_option"
        self::insert($createdRolOptions, $databaseName, 'rol_option');

        $log[] = "$databaseName | Registros insertados en la tabla 'rol_option'.";

        return $log;
    }
}
