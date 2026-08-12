<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 1cm;
            size: portrait;
        }

        .divOne {
            width: 100%;
            height: 80px;
            position: relative;

            background-color: #e0f7fa;
        }

        .divTwo {
            width: 80%;
            height: 100%;

            position: relative;
        }

        .pOne {
            text-align: left;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;

            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .divThree {
            max-width: 20%;
            height: 100%;

            text-align: center;

            position: absolute;
            right: 0px;
            top: 0px;
        }

        .imgOne {
            height: 100%;
        }

        .h1One {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 4px;
        }

        .pTwo {
            text-align: right;
            font-size: 9pt;
            margin-top: 0;
        }

        .tableOne {
            width: 100%;
            margin: auto;
            border-collapse: collapse;
        }

        .thOne {
            font-size: 9pt;
            padding: 3px;
            text-align: left;
            width: 25%;
        }

        .tdOne {
            font-size: 9pt;
            padding: 3px;
            text-align: left;
            font-weight: normal;
        }

        .thTwo {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .thTwo,
        .tdTwo {
            font-size: 8pt;
            padding: 4px;
        }

        .unitBlock {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .unitTitle {
            font-size: 10pt;
            font-weight: bold;
            margin: 0;
            padding: 4px;
            background-color: #e0f7fa;
        }

        .unitMeta {
            font-size: 8pt;
            margin: 2px 0 0 0;
            padding: 0 4px;
            color: #333;
        }

        .barOuter {
            width: 100%;
            height: 10px;
            background-color: #e0e0e0;
        }

        .barInner {
            height: 10px;
            background-color: #26a69a;
        }

        .noSyllabus {
            font-size: 8pt;
            font-style: italic;
            padding: 6px 4px;
            color: #b71c1c;
        }

        .statusPending {
            color: #b71c1c;
        }

        .statusProgress {
            color: #ef6c00;
        }

        .statusCompleted {
            color: #1b5e20;
        }
    </style>
</head>

<body>
    @include('exports/header')

    <h1 class="h1One">{{ $title }}</h1>
    <p class="pTwo">{{ $date }}</p>

    <table class="tableOne">
        @foreach ($filters as $label => $value)
            <tr>
                <th class="thOne">{{ $label }}</th>
                <th class="tdOne">{{ $value ?? '-' }}</th>
            </tr>
        @endforeach
    </table>

    <br>

    <table class="tableOne">
        <tr>
            <th class="thTwo">UNIDADES DIDÁCTICAS</th>
            <th class="thTwo">CON SÍLABO</th>
            <th class="thTwo">SIN SÍLABO</th>
            <th class="thTwo">COMPETENCIAS</th>
            <th class="thTwo">COMPLETADAS</th>
            <th class="thTwo">AVANCE PROMEDIO</th>
        </tr>
        <tr>
            <td class="tdTwo" style="text-align: center;">{{ $summary['total_units'] }}</td>
            <td class="tdTwo" style="text-align: center;">{{ $summary['units_with_syllabus'] }}</td>
            <td class="tdTwo" style="text-align: center;">{{ $summary['units_without_syllabus'] }}</td>
            <td class="tdTwo" style="text-align: center;">{{ $summary['total_competencies'] }}</td>
            <td class="tdTwo" style="text-align: center;">{{ $summary['completed_competencies'] }}</td>
            <td class="tdTwo" style="text-align: center;">{{ $summary['average_percent'] }}%</td>
        </tr>
    </table>

    @foreach ($units as $unit)
        @php
            $syllabus = $unit['syllabus'] ?? null;
        @endphp
        <div class="unitBlock">
            <p class="unitTitle">{{ $unit['code'] }} - {{ $unit['name'] }}</p>
            <p class="unitMeta">
                Programa: {{ $unit['study_program'] ?? '-' }} |
                Año: {{ $unit['year'] ?? '-' }} |
                Créditos: {{ $unit['credits'] ?? '-' }} |
                Horas: {{ $unit['hours'] ?? '-' }}
            </p>

            @if (!$syllabus)
                <p class="noSyllabus">Sin sílabo registrado para esta unidad didáctica.</p>
            @else
                <p class="unitMeta">
                    Sílabo: <strong>{{ $syllabus['name'] }}</strong> |
                    Creado: {{ $syllabus['created_at'] ?? '-' }} |
                    Periodo: {{ $syllabus['classroom']['period'] ?? '-' }} |
                    Sección: {{ $syllabus['classroom']['section'] ?? '-' }} |
                    Turno: {{ $syllabus['classroom']['shift'] ?? '-' }} |
                    Docente: {{ $syllabus['classroom']['teacher'] ?? '-' }}
                </p>
                <p class="unitMeta">
                    Competencias: {{ $syllabus['total_competencies'] }} |
                    Completadas: {{ $syllabus['completed_competencies'] }} |
                    En progreso: {{ $syllabus['in_progress_competencies'] }} |
                    Pendientes: {{ $syllabus['pending_competencies'] }} |
                    Avance: <strong>{{ $syllabus['total_percent'] }}%</strong>
                </p>

                <div class="barOuter">
                    <div class="barInner" style="width: {{ $syllabus['total_percent'] }}%;"></div>
                </div>

                <br>

                <table class="tableOne">
                    <tr>
                        <th class="thTwo" style="text-align: center; width: 4%;">#</th>
                        <th class="thTwo" style="width: 22%;">COMPETENCIA</th>
                        <th class="thTwo" style="width: 30%;">DESCRIPCIÓN</th>
                        <th class="thTwo" style="width: 30%;">OBJETIVO</th>
                        <th class="thTwo" style="text-align: center; width: 14%;">ESTADO</th>
                    </tr>
                    @foreach ($syllabus['competencies'] as $index => $competency)
                        @php
                            $statusClass = match ($competency['status']) {
                                'completed' => 'statusCompleted',
                                'in_progress' => 'statusProgress',
                                default => 'statusPending',
                            };
                        @endphp
                        <tr
                            style="{{ $index + 1 == count($syllabus['competencies']) ? 'border-bottom: 1px solid #000;' : '' }}
                                   {{ ($index + 1) % 2 != 0 ? 'background-color: #f5f5f5;' : '' }}">
                            <td class="tdTwo" style="text-align: center;">{{ $index + 1 }}</td>
                            <td class="tdTwo">{{ $competency['name'] ?? '-' }}</td>
                            <td class="tdTwo">{{ $competency['description'] ?? '-' }}</td>
                            <td class="tdTwo">{{ $competency['objective'] ?? '-' }}</td>
                            <td class="tdTwo {{ $statusClass }}" style="text-align: center;">
                                {{ $competency['status_label'] }}
                            </td>
                        </tr>
                    @endforeach
                    @if (count($syllabus['competencies']) === 0)
                        <tr style="border-bottom: 1px solid #000;">
                            <td class="tdTwo" colspan="5" style="text-align: center;">
                                El sílabo no tiene competencias registradas.
                            </td>
                        </tr>
                    @endif
                </table>
            @endif
        </div>
    @endforeach
</body>

</html>
