<?php

namespace Modules\Shared\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class JsonFileService
{
    /**
     * Crear o sobrescribir un archivo JSON.
     */
    public static function write(string $fileName, array $data): bool
    {
        $dbName = DB::connection()->getDatabaseName();

        $directoryPath = storage_path('app/json/' . $dbName);

        // Crear directorio si no existe
        File::ensureDirectoryExists($directoryPath);

        $fullPath = $directoryPath . '/' . $fileName . '.json';

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        $result = File::put($fullPath, $json) !== false;

        return $result;
    }

    /**
     * Leer un archivo JSON y retornarlo como array.
     */
    public static function read(string $fileName): object
    {
        $dbName = DB::connection()->getDatabaseName();

        $directoryPath = storage_path('app/json/' . $dbName);

        $fullPath = $directoryPath . '/' . $fileName . '.json';

        if (!File::exists($fullPath)) {
            throw new Exception('Archivo no encontrado: ' . $fullPath);
        }

        $content = File::get($fullPath);

        $result = json_decode($content);

        return $result;
    }
}
