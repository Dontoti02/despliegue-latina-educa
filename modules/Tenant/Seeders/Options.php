<?php

namespace Modules\Tenant\Seeders;

use Illuminate\Support\Facades\DB;

class Options
{
    public static function get(string|null $databaseName = null)
    {
        $secretary = 'SECRETARIO ACADÉMICO';
        $teacher = 'DOCENTE';
        $student = 'ESTUDIANTE';
        $admin = 'ADMINISTRADOR';
        $trainingCoordinator = 'COORDINADOR DE CAPACITACIONES';
        $trainingTeacher = 'DOCENTE DE CAPACITACIONES';
        $trainingStudent = 'ESTUDIANTE DE CAPACITACIONES';
        $company = 'EMPRESA';

        $menus = [
            [
                'name' => 'Principal',
                'order' => 1,
                'options' => [
                    [
                        'name' => 'Inicio',
                        'name_url' => 'Home',
                        'order' => 1,
                        'roles' => [$secretary, $teacher, $student, $admin, $trainingCoordinator, $trainingTeacher, $trainingStudent, $company],
                    ]
                ]
            ],
            [
                'name' => 'Aula virtual',
                'order' => 2,
                'options' => [
                    [
                        'name' => 'Mis unidades didácticas',
                        'name_url' => 'MyCourses',
                        'order' => 1,
                        'roles' => [$teacher, $student],
                        'options' => [
                            [
                                'name' => 'Actuales',
                                'name_url' => 'CurrentCourses',
                                'order' => 1,
                                'roles' => [$teacher, $student],
                            ],
                            [
                                'name' => 'Archivados',
                                'name_url' => 'ArchivedCourses',
                                'order' => 2,
                                'roles' => [$teacher, $student],
                            ]
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Preferencias',
                'order' => 3,
                'options' => [
                    [
                        'name' => 'Condiciones laborales',
                        'name_url' => 'WorkingCondition',
                        'order' => 1,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Docentes',
                        'name_url' => 'Teacher',
                        'order' => 2,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Ajustes',
                        'name_url' => 'Settings',
                        'order' => 3,
                        'roles' => [$secretary, $admin],
                    ],
                ]
            ],
            [
                'name' => 'Configuración',
                'order' => 4,
                'options' => [
                    [
                        'name' => 'Horarios',
                        'name_url' => 'Schedules',
                        'order' => 1,
                        'roles' => [$secretary, $teacher, $student, $admin],
                    ],
                    [
                        'name' => 'Usuarios',
                        'name_url' => 'UsersList',
                        'order' => 2,
                        'roles' => [$secretary, $admin],
                    ],
                    [
                        'name' => 'Importación',
                        'name_url' => 'Importation',
                        'order' => 3,
                        'roles' => [$secretary, $admin],
                    ],
                    [
                        'name' => 'Landing Page',
                        'name_url' => 'LandingPage',
                        'order' => 4,
                        'roles' => [$secretary, $admin],
                    ]
                ]
            ],
            [
                'name' => 'Acceso rápido',
                'order' => 5,
                'options' => [
                    [
                        'name' => 'Mi Perfil',
                        'name_url' => 'Profile',
                        'order' => 1,
                        'roles' => [$secretary, $teacher, $student, $admin, $trainingCoordinator, $trainingTeacher, $trainingStudent, $company],
                    ]
                ]
            ],
            [
                'name' => 'Procesos Académicos',
                'order' => 6,
                'options' => [
                    [
                        'name' => 'Familia productiva',
                        'name_url' => 'ProductiveFamily',
                        'order' => 1,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Programa de estudio',
                        'name_url' => 'StudyProgram',
                        'order' => 2,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Tipos de planes de estudio',
                        'name_url' => 'StudyPlanType',
                        'order' => 3,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Planes de estudio',
                        'name_url' => 'StudyPlan',
                        'order' => 4,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Periodos Académicos',
                        'name_url' => 'Cycle',
                        'order' => 5,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Tipos de modalidades formativas',
                        'name_url' => 'ModuleType',
                        'order' => 6,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Modalidades Formativas',
                        'name_url' => 'Module',
                        'order' => 7,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Tipos de Unidades Didácticas',
                        'name_url' => 'CourseType',
                        'order' => 8,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Unidades Didácticas',
                        'name_url' => 'Course',
                        'order' => 9,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Periodos Lectivos',
                        'name_url' => 'Period',
                        'order' => 10,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Turnos',
                        'name_url' => 'Shift',
                        'order' => 11,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Secciones',
                        'name_url' => 'Section',
                        'order' => 12,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Clases',
                        'name_url' => 'Classroom',
                        'order' => 13,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Historial académico',
                        'name_url' => 'AcademicHistory',
                        'order' => 14,
                        'roles' => [$secretary, $student],
                    ],
                    [
                        'name' => 'Lista de mérito',
                        'name_url' => 'ListOfMerit',
                        'order' => 15,
                        'roles' => [$secretary, $admin],
                    ]
                ]
            ],
            [
                'name' => 'Capacitaciones',
                'order' => 7,
                'options' => [
                    [
                        'name' => 'Mis Capacitaciones',
                        'name_url' => 'AdminCapacitationList',
                        'order' => 1,
                        'roles' => [$trainingCoordinator, $trainingTeacher, $trainingStudent],
                    ],
                    [
                        'name' => 'Reportes',
                        'name_url' => 'ReportCapacitation',
                        'order' => 2,
                        'roles' => [$trainingCoordinator],
                    ],
                    [
                        'name' => 'Crear capacitaciones',
                        'name_url' => 'ManageCapacitation',
                        'order' => 3,
                        'roles' => [$trainingCoordinator],
                    ],
                    [
                        'name' => 'Listado de estudiantes',
                        'name_url' => 'CapacitationStudents',
                        'order' => 4,
                        'roles' => [$trainingCoordinator],
                    ],
                    [
                        'name' => 'Contenido de capacitaciones',
                        'name_url' => 'CurrentTraining',
                        'order' => 5,
                        'roles' => [$trainingTeacher, $trainingStudent],
                    ]
                ]
            ],
            [
                'name' => 'Tesorería',
                'order' => 8,
                'options' => [
                    [
                        'name' => 'Matricular Estudiante',
                        'name_url' => 'enrollStudent',
                        'order' => 1,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Matriculas',
                        'name_url' => 'enrollList',
                        'order' => 2,
                        'roles' => [$secretary, $student],
                    ],
                    [
                        'name' => 'Escalas',
                        'name_url' => 'Scales',
                        'order' => 3,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Conceptos de Pago',
                        'name_url' => 'PaymentConcepts',
                        'order' => 4,
                        'roles' => [$secretary],
                    ],
                    [
                        'name' => 'Pagos',
                        'name_url' => 'payments',
                        'order' => 5,
                        'roles' => [$secretary],
                    ]
                ]
            ],
            [
                'name' => 'Bolsa Laboral',
                'order' => 9,
                'options' => [
                    [
                        'name' => 'Ofertas laborales',
                        'name_url' => 'Offers',
                        'order' => 1,
                        'roles' => [$admin, $company]
                    ],
                    [
                        'name' => 'Empresas',
                        'name_url' => 'Companies',
                        'order' => 2,
                        'roles' => [$admin]
                    ],
                    [
                        'name' => 'Postulaciones',
                        'name_url' => 'Applications',
                        'order' => 3,
                        'roles' => [$admin, $company]
                    ],
                    [
                        'name' => 'Candidato',
                        'name_url' => 'Candidate',
                        'order' => 4,
                        'roles' => [$teacher, $student]
                    ],
                    [
                        'name' => 'Mantenedores',
                        'name_url' => 'JobMaintainers',
                        'order' => 5,
                        'roles' => [$admin]
                    ]
                ]
            ]
        ];

        $table = $databaseName ? "$databaseName.rol" : 'rol';

        $roles = DB::table($table)->get();

        $date = now();

        $menuId = 0;
        $optionId = 0;
        $menuRecords = [];
        $optionRecords = [];
        $rolOptionRecords = [];
        foreach ($menus as $menu) {
            $menuId++;

            $menuRecords[] = [
                'id' => $menuId,
                'name' => $menu['name'],
                'order' => $menu['order'],
                'created_at' => $date,
            ];

            foreach ($menu['options'] as $option) {
                $optionId++;

                $optionRecords[] = [
                    'id' => $optionId,
                    'option_id' => $option['option_id'] ?? null,
                    'menu_id' => $menuId,
                    'name' => $option['name'],
                    'name_url' => $option['name_url'],
                    'icon' => $option['icon'] ?? null,
                    'is_visible' => $option['is_visible'] ?? true,
                    'order' => $option['order'],
                    'created_at' => $date,
                ];

                foreach ($option['roles'] as $rolName) {
                    $rolOptionRecords[] = [
                        'rol_id' => $roles->where('name', $rolName)->first()->id,
                        'option_id' => $optionId,
                    ];
                }

                if (isset($option['options'])) {
                    $aux = $optionId;

                    foreach ($option['options'] as $subOption) {
                        $optionId++;

                        $optionRecords[] = [
                            'id' => $optionId,
                            'option_id' => $aux,
                            'menu_id' => $menuId,
                            'name' => $subOption['name'],
                            'name_url' => $subOption['name_url'],
                            'icon' => $subOption['icon'] ?? null,
                            'is_visible' => $subOption['is_visible'] ?? true,
                            'order' => $subOption['order'],
                            'created_at' => $date,
                        ];

                        foreach ($subOption['roles'] as $rolName) {
                            $rolOptionRecords[] = [
                                'rol_id' => $roles->where('name', $rolName)->first()->id,
                                'option_id' => $optionId,
                            ];
                        }
                    }
                }
            }
        }

        $result = [
            'menus' => $menuRecords,
            'options' => $optionRecords,
            'rolOptions' => $rolOptionRecords,
        ];

        return $result;
    }
}
