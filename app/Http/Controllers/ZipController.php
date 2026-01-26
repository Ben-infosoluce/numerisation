<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ZipController extends Controller
{

    public function zipList()
    {
        return inertia('ZipFile');
    }
    public function getZips(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $files = Storage::disk('public')->files('downloads');
        $zipFiles = [];

        foreach ($files as $file) {
            $path = Storage::disk('public')->path($file);
            if (file_exists($path)) {
                $size = filesize($path);
                $time = filemtime($path);

                // Filtre par date si les paramètres sont présents
                if ($startDate || $endDate) {
                    $fileDate = date('Y-m-d', $time);
                    $start = $startDate ? Carbon::parse($startDate)->startOfDay()->timestamp : 0;
                    $end = $endDate ? Carbon::parse($endDate)->endOfDay()->timestamp : PHP_INT_MAX;

                    if ($time < $start || $time > $end) {
                        continue;
                    }
                }

                $zipFiles[] = [
                    'name' => basename($file),
                    'url'  => Storage::disk('public')->url($file),
                    'size' => $this->formatSizeUnits($size),
                    'date' => date('d/m/Y H:i', $time),
                    'timestamp' => $time
                ];
            }
        }

        // Tri par date (plus récent en premier)
        usort($zipFiles, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return response()->json($zipFiles);
    }


    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        if ($bytes > 1) return $bytes . ' bytes';
        if ($bytes == 1) return '1 byte';
        return '0 bytes';
    }
}
