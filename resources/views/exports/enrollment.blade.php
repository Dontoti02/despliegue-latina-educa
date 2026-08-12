<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 1cm;
            size: landscape;
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
        }

        .pTwo {
            text-align: right;
            font-size: 10pt;
        }

        .pThree {
            text-align: left;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;
        }

        .pFour {
            text-align: left;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;
            padding: 4px;
        }

        .tableOne {
            width: 100%;
            margin: auto;
            border-collapse: collapse;
        }

        .thOne {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .thOne,
        .tdOne {
            font-size: 10pt;
            padding: 4px
        }
        .pCertificate {
          font-size: 10pt;
          text-align: justify;
          line-height: 1.6;
          margin-top: 12px;
          margin-bottom: 16px;
      }
      .signature-container {
          width: 100%;
          margin-top: 70px;
      }

      .signature-box {
          width: 320px;
          float: right;
          text-align: center;
      }

      .signature-line {
          border-top: 1px solid #000;
          margin-bottom: 6px;
      }

      .signature-title {
          font-size: 10pt;
          font-weight: bold;
      }

      .signature-subtitle {
          font-size: 9pt;
      }
    </style>
</head>

<body>
    @include('exports/header')
    <h1 class="h1One">{{ $title }}</h1>
    <p class="pTwo">{{ $date }}</p>
    <p class="pThree"><strong>ALUMNO:</strong> {{ $student }}</p>
    <p class="pThree"><strong>SEMESTRE:</strong> {{ $period }}</p>
    <br>
    <p class="pCertificate">
      Por la presente se deja constancia que el estudiante

      <strong>{{ $student }}</strong>,

      identificado(a) con

      <strong>{{ $document_type }} N.° {{ $document_number }}</strong>,

      se encuentra regularmente matriculado(a) en el

      <strong>Periodo Académico {{ $period }}</strong>

      del <strong>{{ $institutionName }}</strong>, según el siguiente detalle:

  </p>

    <table class="tableOne">
        <tr>
            @foreach ($columns as $key => $column)
                <th class="thOne"
                    style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                    {{ $column }}
                </th>
            @endforeach
        </tr>
        @php
            $rows = array_map(function ($row) use ($columns) {
                $row = array_intersect_key($row, $columns);
                return array_merge($columns, $row);
            }, $rows);
        @endphp
        @foreach ($rows as $indexRows => $row)
            <tr
                style="{{ $indexRows + 1 == count($rows) ? 'border-bottom: 1px solid #000;' : '' }}
            {{ ($indexRows + 1) % 2 != 0 ? 'background-color: #f5f5f5;' : '' }}">
                @foreach ($row as $key => $value)
                    <td class="tdOne"
                        style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                        {{ $key == 'id' ? $indexRows + 1 : $value ?? 'SIN DATOS' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
    <p class="pFour"><strong>NRO. DE CURSOS:</strong> {{ count($rows) }}</p>

    <div class="signature-container">

      <div class="signature-box">

          <div style="height:70px;">
              <!-- Espacio para firma -->
          </div>

          <div class="signature-line"></div>

          <div class="signature-title">
              Secretario Académico
          </div>

          <div class="signature-subtitle">
              {{ $institutionName }}
          </div>

      </div>

      <div style="clear: both;"></div>

  </div>
</body>

</html>
