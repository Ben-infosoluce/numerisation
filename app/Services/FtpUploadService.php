<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FtpUploadService
{
    /**
     * Upload un fichier ZIP vers le serveur FTP distant avec l'arborescence :
     *   {folder}/CARTE GRISE EDITEE/
     *   {folder}/DOCUMENT DE FORMALITE/{dateFolder}/{zipFileName}
     *
     * @param string $zipFilePath  Chemin absolu local du fichier ZIP
     * @param string $folder       Nom du dossier site (BAE, AKOUEDO, AGBAN, OTHERS)
     * @param string $zipFileName  Nom du fichier ZIP (ex: VIN.zip)
     * @return string              Chemin FTP stocké en base de données
     */
    public function uploadZipToFtp(string $zipFilePath, string $folder, string $zipFileName): string
    {
        try {
            $zipContent = file_get_contents($zipFilePath);
            $ftpDisk = Storage::disk('ftp_scandfs');

            // Format de date : 20042026
            $dateFolder = now()->format('dmY');

            // Construction des chemins
            $carteGrisePath = $folder . '/CARTE GRISE EDITEE';
            $docFormalitePath = $folder . '/DOCUMENT DE FORMALITE';
            $datePath = $docFormalitePath . '/' . $dateFolder;
            $ftpFilePath = $datePath . '/' . $zipFileName;

            // Création des dossiers permanents (une seule fois)
            if (!$ftpDisk->exists($carteGrisePath)) {
                $ftpDisk->makeDirectory($carteGrisePath);
                Log::info("FtpUploadService - Création dossier FTP: $carteGrisePath");
            }

            if (!$ftpDisk->exists($docFormalitePath)) {
                $ftpDisk->makeDirectory($docFormalitePath);
                Log::info("FtpUploadService - Création dossier FTP: $docFormalitePath");
            }

            // Création du dossier daté
            if (!$ftpDisk->exists($datePath)) {
                $ftpDisk->makeDirectory($datePath);
                Log::info("FtpUploadService - Création dossier FTP daté: $datePath");
            }

            // Upload du fichier ZIP
            $ftpDisk->put($ftpFilePath, $zipContent);
            Log::info("FtpUploadService - Fichier ZIP uploadé sur FTP: $ftpFilePath");

            // Suppression du fichier local
            unlink($zipFilePath);
            Log::info("FtpUploadService - Fichier ZIP local supprimé.");

            return 'ftp://' . $ftpFilePath;
        } catch (\Exception $e) {
            Log::error("FtpUploadService - Erreur critique lors de l'upload FTP:", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'host' => config('filesystems.disks.ftp_scandfs.host'),
                'user' => config('filesystems.disks.ftp_scandfs.username')
            ]);

            // Fallback
            return 'ftp://' . $folder . '/' . $zipFileName;
        }
    }
}
