<?php

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Caisse;
use App\Models\DetailTypeService;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


function generateImmatriculation($prefix = 'CI', $length = 8)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomPart = '';

    for ($i = 0; $i < $length; $i++) {
        $randomPart .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $prefix . '-' . $randomPart;
}


function getIdSite()
{
    $user = Auth::user();
    return $user->id_site;
}

function getIdCaisse()
{
    $site_id = getIdSite();
    $caisse = Caisse::where('site_id', $site_id)->first();
    return $caisse->id;
}
function getConnectedUserId()
{
    $user = Auth::user();
    return $user->id;
}

function getConnectedUserRole()
{
    $user = Auth::user();
    $role = $user->r_user_role->nom_role;
    return $role;
}


function getUserWithRelation($id)
{
    $user = User::with(["r_user_role", "r_user_site", 'r_user_permissions'])->find($id);
    if ($user) {
        return $user;
    }
    else {
        return null;
    }
}


//recupere les permissions
function getUserPermissions()
{
    $userId = Auth::id();

    $permissions = DB::table('user_permission')
        ->where('id_user', $userId)
        ->pluck('id_Permission')
        ->toArray();

    return $permissions;
}


function generateStrongPassword($length = 8)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}


//generate numero chrono 

function generateChronoNumber(string $service)
{
    $sites = [
        'POST' => 'ABJ1',
        'REIM' => 'KGO',
        'DUPL' => 'BKE',
        'OPSP' => 'ABJ2',
    ];

    $codeSite = $sites[$service] ?? 'ABJ1';

    $year = now()->format('y');
    $month = now()->format('m');

    // On utilise une transaction pour éviter les doublons
    return DB::transaction(function () use ($service, $codeSite, $year, $month) {

        // Récupère ou crée la ligne pour ce mois/service
        $row = DB::table('chronos')
            ->where('service', $service)
            ->where('year', $year)
            ->where('month', $month)
            ->lockForUpdate() // 🔒 empêche deux accès en même temps
            ->first();

        if (!$row) {
            // Crée la ligne avec counter = 1
            DB::table('chronos')->insert([
                'service' => $service,
                'year' => $year,
                'month' => $month,
                'counter' => 1,
            ]);
            $counter = 1;
        }
        else {
            // Incrémente en base (atomic)
            DB::table('chronos')
                ->where('id', $row->id)
                ->increment('counter');
            $counter = $row->counter + 1;
        }

        $serial = str_pad($counter, 5, '0', STR_PAD_LEFT);

        return strtoupper($service . $codeSite . $year . $month . $serial);
    });
}


//récupérer les permissions de l'utilisateur connecté
function getPermissions()
{
    $user = Auth::user();
    return $user->r_permissions;
}


function servicesAccessibles()
{
    $user = Auth::user();

    if (!$user) {
        return collect(); // retourne une collection vide si pas connecté
    }

    // Retourne les IDs uniques des services pour le site de l'utilisateur
    return DetailTypeService::where('id_site', $user->id_site)
        ->distinct() // sélectionne seulement les valeurs uniques
        ->pluck('id_service');
}



if (!function_exists('getFreshHashAndTime')) {
    function getFreshHashAndTime()
    {
        $baseUrl  = config('digimmat.base_url');
        $username = config('digimmat.username');
        $password = config('digimmat.password');

        // Utiliser une instance Http avec support des cookies (CookieJar explicite pour éviter l'erreur Guzzle)
        $client = Http::withOptions(['cookies' => new \GuzzleHttp\Cookie\CookieJar(), 'verify' => false]);

        try {
            // 1. Récupérer la page de login pour avoir le cookie JSESSIONID et le token CSRF
            $loginPageResponse = $client->get("$baseUrl/login");
            if (!$loginPageResponse->successful()) {
                throw new Exception("Impossible d'accéder à la page de login de l'API.");
            }

            $html = $loginPageResponse->body();
            // Extraction du token CSRF via regex simple
            if (preg_match('/name="_csrf" type="hidden" value="([^"]+)"/', $html, $matches)) {
                $csrfToken = $matches[1];
            }
            else {
                throw new Exception("Token CSRF non trouvé sur la page de login.");
            }

            // 2. Effectuer le login
            $loginResponse = $client->asForm()->post("$baseUrl/login", [
                'username' => $username,
                'password' => $password,
                '_csrf' => $csrfToken,
            ]);

            if (!$loginResponse->successful()) {
                Log::error("Échec de l'authentification sur l'API Digimmat", [
                    'status' => $loginResponse->status(),
                    'body' => $loginResponse->body()
                ]);
                throw new Exception("Échec de l'authentification sur l'API.");
            }

            // 3. Récupérer le hash et le timestamp sur l'endpoint protégé
            $response = $client->accept('application/json')->get("$baseUrl/api/hsign");

            if ($response->successful()) {
                $data = $response->json();

                if (!$data || !isset($data['time']) || !isset($data['hash'])) {
                    Log::error("Réponse invalide de l'API hsign après login", ['data' => $data]);
                    throw new Exception("Réponse invalide de l'API hsign : données manquantes.");
                }

                Log::info("Authentification réussie sur l'API Digimmat", [
                    'time' => $data['time'],
                    'hash' => $data['hash']
                ]);

                return [
                    'time' => $data['time'],
                    'hash' => $data['hash'],
                    'cookies' => $client->getOptions()['cookies'] // Retourner le cookie jar
                ];
            }

            throw new Exception("Impossible de récupérer le hash et le timestamp après authentification (Status: " . $response->status() . ").");

        }
        catch (\Throwable $e) {
            Log::error("Erreur critique getFreshHashAndTime : " . $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('sendPlateNumberData')) {
    function sendDocumentData($numChronoCil, $numeroChassisVehicule, $numImmat, $listeDocuments)
    {
        // Récupérer le hash, le timestamp et la SESSION (cookies)
        $authData = getFreshHashAndTime();
        $time = $authData['time'];
        $hash = $authData['hash'];
        $cookies = $authData['cookies'];

        // Construire l'URL de l'API avec le time et le hash
        $url = "https://rbadd.digimmat.ci/api/plate-number-dgttc/completion/{$time}/{$hash}";

        // Préparer les données à envoyer
        $documentList = $listeDocuments['Liste_documents'] ?? $listeDocuments;

        $payload = [
            'numChronoCil' => $numChronoCil,
            'numeroChassisVehicule' => $numeroChassisVehicule,
            'numImmat' => $numImmat,
            // Selon l'exemple fonctionnel final, liste_documents doit être une STRING JSON 
            // contenant un objet avec la clé 'Liste_documents'.
            'liste_documents' => json_encode([
                'Liste_documents' => $documentList
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        Log::info("Envoi des documents à l'API Digimmat", [
            'url' => $url,
            'payload' => $payload
        ]);

        // Envoyer la requête POST avec LES COOKIES d'authentification
        $response = Http::withOptions([
            'cookies' => $cookies,
            'verify' => false
        ])->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        Log::info("Réponse de l'API Digimmat", [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return $response->json();
    }
}