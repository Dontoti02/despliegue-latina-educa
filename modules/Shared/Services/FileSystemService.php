<?php

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileSystemService
{

    protected static $driver = 'do-spaces';

    public static function create(string $path, $file)
    {
        try {
            return Storage::disk(self::$driver)->putFile($path, $file);
        } catch (\Exception $th) {
          Log::error('Error creating file: ' . $th->getMessage(), ['exception' => $th]);
          throw $th;
        }
    }

    public static function get(string $url)
    {
        try {
            return Storage::disk(self::$driver)->get($url);
        } catch (\Exception $th) {
            Log::error('Error getting file: ' . $th->getMessage(), ['exception' => $th]);
            throw $th;
        }
    }

    public static function delete(string $path)
    {
        try {
            return Storage::disk(self::$driver)->delete($path);
        } catch (\Exception $th) {
            throw $th;
        }
    }
}
