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
        $folder = $request->input('folder');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$folder) {
            // Return list of available folders
            $folders = ['AGBAN', 'AKOUEDO', 'BAE'];
            $response = [];
            foreach ($folders as $f) {
                $fileCount = count(Storage::disk('public')->files("downloads/$f"));
                $response[] = [
                    'name' => $f,
                    'is_folder' => true,
                    'file_count' => $fileCount
                ];
            }
            return response()->json($response);
        }

        $files = Storage::disk('public')->files("downloads/$folder");
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
                    'timestamp' => $time,
                    'is_folder' => false
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

    public function download(Request $request, $name)
    {
        $folder = $request->input('folder');
        $path = $folder ? "downloads/$folder/$name" : "downloads/$name";

        if (!Storage::disk('public')->exists($path)) {
            return abort(404, 'Fichier introuvable');
        }
        return Storage::disk('public')->download($path);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string',
            'folder' => 'required|string',
        ]);

        $folder = $request->folder;
        $oldPath = "downloads/$folder/" . $request->old_name;
        if (!Storage::disk('public')->exists($oldPath)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $newName = $request->new_name;
        if (!str_ends_with($newName, '.zip')) {
            $newName .= '.zip';
        }
        $newPath = "downloads/$folder/" . $newName;

        if (Storage::disk('public')->exists($newPath)) {
            return response()->json(['message' => 'Un fichier avec ce nom existe déjà'], 400);
        }

        Storage::disk('public')->move($oldPath, $newPath);

        return response()->json(['message' => 'Fichier renommé avec succès']);
    }

    public function delete(Request $request, $name)
    {
        $folder = $request->input('folder');
        $path = "downloads/$folder/" . $name;
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        Storage::disk('public')->delete($path);

        return response()->json(['message' => 'Fichier supprimé avec succès']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'folder' => 'required|string',
        ]);

        $folder = $request->folder;
        $filesToDelete = $request->input('files');

        $deletedCount = 0;
        foreach ($filesToDelete as $filename) {
            $path = "downloads/$folder/" . $filename;
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deletedCount++;
            }
        }

        return response()->json(['message' => "$deletedCount fichier(s) supprimé(s) avec succès"]);
    }

    public function bulkDownload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'folder' => 'required|string',
        ]);

        $folder = $request->folder;
        $filesToZip = $request->input('files');

        if (empty($filesToZip)) {
            return response()->json(['message' => 'Aucun fichier sélectionné'], 400);
        }

        $zip = new \ZipArchive();
        $zipName = 'bulk_download_' . time() . '.zip';
        $zipPath = storage_path('app/public/downloads/' . $zipName);

        // Ensure directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return response()->json(['message' => 'Impossible de créer le fichier ZIP'], 500);
        }

        foreach ($filesToZip as $filename) {
            $filePath = "downloads/$folder/" . $filename;
            if (Storage::disk('public')->exists($filePath)) {
                $absolutePath = Storage::disk('public')->path($filePath);
                $zip->addFile($absolutePath, $filename);
            }
        }

        $zip->close();

        if (!file_exists($zipPath)) {
            return response()->json(['message' => 'Erreur lors de la génération du ZIP'], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip,pdf,xls,xlsx|max:102400',
            'folder' => 'required|string',
        ], [
            'file.required' => 'Aucun fichier sélectionné.',
            'file.mimes'    => 'Formats acceptés : ZIP, PDF, XLS, XLSX.',
            'file.max'      => 'Le fichier ne doit pas dépasser 100 Mo.',
            'folder.required' => 'Le dossier de destination est requis.',
        ]);

        $uploadedFile = $request->file('file');
        $filename = $uploadedFile->getClientOriginalName();
        $folder = $request->folder;

        // Éviter d'écraser un fichier existant
        $destinationDir = 'downloads/' . $folder;
        if (!Storage::disk('public')->exists($destinationDir)) {
            Storage::disk('public')->makeDirectory($destinationDir);
        }

        $destination = $destinationDir . '/' . $filename;
        if (Storage::disk('public')->exists($destination)) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $ext  = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = $name . '_' . now()->format('YmdHis') . '.' . $ext;
        }

        $uploadedFile->storeAs($destinationDir, $filename, 'public');

        return response()->json(['message' => 'Fichier importé avec succès', 'filename' => $filename]);
    }
}
