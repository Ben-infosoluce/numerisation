<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\EmuciDocument;
use App\Models\Dossier;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use setasign\Fpdi\Fpdi;
use App\Helpers\apiHelpers;


// ✅ Pour les règles de validation

class NumerisationController extends Controller
{
    //
    //dashbord
    public function showNumerisationDashboard()
    {
        $currentDateTime = now();
        return inertia('Numerisation/Dashbord', ['currentDateTime' => $currentDateTime]);
    }
    //verifie vin
    public function verifieVin($vin)
    {
        //dd($vin);
        // sleep(1);
        try {
            $exists = Vehicule::where('vin', $vin)->exists();

            return response()->json([
                'exists' => $exists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //show immatriculation
    public function showNumerisationList()
    {
        return inertia('Numerisation/index');
    }

    //get immatriculation data
    public function getNumerisationData(Request $request)
    {
        $userSiteId = getIdSite();
        // dd($userSiteId);

        $query = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('statut_paiement', 2)
            ->where(function ($q) use ($userSiteId) {
                $q->where('id_site', $userSiteId)
                    ->orWhere('id_site', 0);
            });
        $filtre_per_page = $request->input('filtre_per_page', 1);
        $statut = $request->input('statut_numerisation');
        $filtre_type = $request->input('filtre_type');
        $search_data = $request->input('search_data');
        $date_start = $request->input('date_start');
        $date_end = $request->input('date_end');

        // Filtre par VIN (via search_data) et num_chrono
        if ($search_data) {
            // Récupérer les IDs des véhicules qui correspondent au VIN
            $vehicules = Vehicule::where('vin', 'like', "%{$search_data}%")->pluck('id');

            // Appliquer le filtre sur les dossiers
            $query->where(function ($q) use ($search_data, $vehicules) {
                $q->whereIn('id_vehicule', $vehicules)
                    ->orWhere('num_chrono', 'like', "%{$search_data}%");
            });
        }

        // Filtre par statut
        if ($statut) {
            $query->where("statut_numerisation", $statut);
        }

        // Filtre par date de création (created_at)
        if ($date_start && $date_end) {
            // On s'assure que ce sont des dates valides
            try {
                // $start = Carbon::parse($date_start)->startOfDay();
                //  $end = Carbon::parse($date_end)->endOfDay();
                $start = $date_start;
                $end = $date_end;

                $query->whereBetween('date_creation', [$start, $end]);
            } catch (\Exception $e) {
                // Optionnel : log ou ignorer si erreur de date
            }
        }

        $dossiers = $query->latest()->paginate($filtre_per_page);

        return response()->json([
            'dossiers' => $dossiers,
            'filtres' => $request->only(
                "filtre_per_page",
                "statut",
                "search_data",
                "filtre_type",
                "date_start",
                "date_end"
            ),
        ]);
    }

    public function showNewNumerisation()
    {
        return inertia('Numerisation/new');
    }

    // listes de numérisations d'un dossier
    public function listDocuments($id_dossier)
    {
        // Charger tous les documents avec leurs dossiers associés
        // Récupère le dossier + ses documents
        $dossierPrincipal = Dossier::with('r_dossier_documents')->findOrFail($id_dossier);

        if (!$dossierPrincipal) {
            abort(404, 'Dossier non trouvé');
        }

        // $dossierLier = null;
        // 🔵 2. Vérifier l'existence d'un dossier lié
        // if (!empty($dossierPrincipal->id_dossier_lier)) {

        //     // Charger le dossier lié
        //     $dossierLier = Dossier::with([
        //         'r_dossier_vehicule',
        //         'r_dossier_user',
        //         'r_dossier_client',
        //         'r_dossier_documents',
        //         'r_dossier_services',
        //         'r_dossier_services.r_service_types',
        //         'r_dossier_transactions',
        //         'r_dossier_documents',
        //     ])
        //         ->where('id', $dossierPrincipal->id_dossier_lier)
        //         ->first();

        // 🔴 3. Si dossier lié → redirection vers selectDossier
        // return inertia('Numerisation/selectDossierNumeriser', [
        //     'dossier_lier' => $dossierLier,
        //     'dossier' => $dossierPrincipal,
        //     'documents' => $dossierPrincipal->r_dossier_documents
        // ]);
        // }

        return inertia('Numerisation/indexdocuments', [
            'dossier' => $dossierPrincipal,
            'documents' => $dossierPrincipal->r_dossier_documents
        ]);
    }
    public function listDocumentsforApi($id_dossier)
    {
        // dd($id_dossier);
        // Charger tous les documents avec leurs dossiers associés
        // Récupère le dossier + ses documents
        $dossierPrincipal = Dossier::with('r_dossier_documents')->findOrFail($id_dossier);

        if (!$dossierPrincipal) {
            abort(404, 'Dossier non trouvé');
        }
        return response()->json([
            'dossiers' => $dossierPrincipal,
            'documents' => $dossierPrincipal->r_dossier_documents
        ]);
    }
    public function listDocumentsWithDossierLier($id_dossier)
    {
        $dossier = Dossier::with('r_dossier_documents')->findOrFail($id_dossier);
        return inertia('Numerisation/indexdocuments', [
            'dossier' => $dossier,
            'documents' => $dossier->r_dossier_documents
        ]);
    }

    public function EditlistDocuments($id_dossier)
    {
        // Charger tous les documents avec leurs dossiers associés
        // Récupère le dossier + ses documents
        $dossier = Dossier::with('r_dossier_documents')->findOrFail($id_dossier);

        return inertia('Numerisation/edit', [
            'dossier' => $dossier,
            'documents' => $dossier->r_dossier_documents
        ]);
    }
    public function editDocument($id)
    {
        try {
            $document = Document::with('r_document_dossier')->findOrFail($id);
            return inertia('Numerisation/edit', [
                'document' => $document,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Document introuvable']);
        }
    }

    public function getDocumentJson($id)
    {
        try {
            $document = Document::with('r_document_dossier')->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $document,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document introuvable',
            ], 404);
        }
    }

    public function showNumerisationGetData1($vin)
    {
        //suprimer espace dans la data $vin

        $dossiers = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])->where('num_chrono', $vin)->first();

        if (!$dossiers) {
            abort(404, 'Dossier non trouvé');
        }

        return inertia('Numerisation/components/createForm', [
            'chrono' => $vin,
            'dossier' => $dossiers,
        ]);
    }


    public function showNumerisationGetData($vin)
    {
        // 🔵 1. Charger le dossier principal
        $dossier = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('num_chrono', $vin)
            ->first();

        if (!$dossier) {
            abort(404, 'Dossier non trouvé');
        }

        // 🔵 2. Récupération du log de modification
        $log_id = $dossier->modification_log_id;
        $log = DB::table('modification_logs')->where('id', $log_id)->first();

        if ($log) {
            $old = json_decode($log->old_values, true);
            $new = json_decode($log->new_values, true);
        } else {
            $old = $new = [];
        }

        // 🔵 3. Récupération du dossier lié s'il existe
        $dossierLier = null;

        if (!empty($dossier->id_dossier_lier)) {

            $dossierLier = Dossier::with([
                'r_dossier_vehicule',
                'r_dossier_user',
                'r_dossier_client',
                'r_dossier_documents',
                'r_dossier_services',
                'r_dossier_services.r_service_types',
                'r_dossier_transactions',
            ])
                ->where('id', $dossier->id_dossier_lier)
                ->first();
        }

        // 🔵 4. Retour vers Inertia
        return inertia('Numerisation/components/createForm1', [
            'vin' => $vin,
            'dossier' => $dossier,
            'dossier_lier' => $dossierLier, // 
            'log' => $log,
            'old' => $old,
            'new' => $new,
        ]);
    }

    public function showNumerisationGetDataForApi($vin)
    {
        // 🔵 1. Charger le dossier principal
        $dossier = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('num_chrono', $vin)
            ->first();

        if (!$dossier) {
            return response()->json([
                'message' => 'Dossier non trouvé'
            ], 404);
        }

        // 🔵 2. Récupération du log de modification
        $log_id = $dossier->modification_log_id;
        $log = DB::table('modification_logs')->where('id', $log_id)->first();

        if ($log) {
            $old = json_decode($log->old_values, true);
            $new = json_decode($log->new_values, true);
        } else {
            $old = $new = [];
        }

        // 🔵 3. Retour JSON
        return response()->json([
            'vin' => $vin,
            'dossier' => $dossier,
            'log' => $log,
            'old' => $old,
            'new' => $new,
        ]);
    }

    public function showNumerisationGetDataWithPost($vin)
    {
        // 🔵 1. Charger le dossier principal
        $dossier = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('id', $vin)
            ->first();

        if (!$dossier) {
            abort(404, 'Dossier non trouvé');
        }
        // dd($dossier);



        // 🔵 3. Récupération du dossier lié s'il existe
        $dossierLier = null;

        if (!empty($dossier->id_dossier_lier)) {

            $dossierLier = Dossier::with([
                'r_dossier_vehicule',
                'r_dossier_user',
                'r_dossier_client',
                'r_dossier_documents',
                'r_dossier_services',
                'r_dossier_services.r_service_types',
                'r_dossier_transactions',
            ])
                ->where('id', $dossier->id_dossier_lier)
                ->first();
        }

        // 🔵 2. Récupération du log de modification
        $log_id = $dossierLier->modification_log_id;
        $log = DB::table('modification_logs')->where('id', $log_id)->first();

        if ($log) {
            $old = json_decode($log->old_values, true);
            $new = json_decode($log->new_values, true);
        } else {
            $old = $new = [];
        }

        // return inertia('Numerisation/selectDossier', [
        //     'vin' => $vin,
        //     'dossier' => $dossier,
        //     'dossier_lier' => $dossierLier,   // 
        //     'log' => $log,
        //     'old' => $old,
        //     'new' => $new,
        // ]);
        return inertia('Numerisation/formWithPost', [
            'dossier' => $dossier,
            'dossier_lier' => $dossierLier,
            'oldData' => $old,
            'newData' => $new,
        ]);
    }

    // Formulaire de numerisation
    public function showFormNumerisation($dossier)
    {
        // 🔵 1. Charger le dossier principal
        $dossierPrincipal = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('id', $dossier)
            ->first();

        if (!$dossierPrincipal) {
            abort(404, 'Dossier non trouvé');
        }

        // 🔵 2. Vérifier l'existence d'un dossier lié
        if (!empty($dossierPrincipal->id_dossier_lier)) {

            // Charger le dossier lié
            $dossierLier = Dossier::with([
                'r_dossier_vehicule',
                'r_dossier_user',
                'r_dossier_client',
                'r_dossier_documents',
                'r_dossier_services',
                'r_dossier_services.r_service_types',
                'r_dossier_transactions',
            ])
                ->where('id', $dossierPrincipal->id_dossier_lier)
                ->first();

            // 🔴 3. Si dossier lié → redirection vers selectDossier
            // return inertia('Numerisation/selectDossier', [
            //     'dossier' => $dossierPrincipal,
            //     'dossier_lier' => $dossierLier,
            // ]);
        }

        // 🟢 4. Pas de dossier lié → afficher le formulaire normal
        return inertia('Numerisation/form', [
            'dossier' => $dossierPrincipal,
            // 'dossier_lier' => $dossierLier,
        ]);
    }
    public function showOpsFormNumerisation($dossier)
    {
        $dossierPrincipal = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('id', $dossier)
            ->first();

        if (!$dossierPrincipal) {
            abort(404, 'Dossier non trouvé');
        }
        return inertia('Numerisation/Opsform', [
            'dossier' => $dossierPrincipal,
        ]);
    }

    public function showPostFormNumerisation($id)
    {

        $dossierPrincipal = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('id', $id)
            ->first();

        if (!$dossierPrincipal) {
            abort(404, 'Dossier non trouvé');
        }
        // dd($dossierPrincipal);
        $log_id = $dossierPrincipal->modification_log_id;
        $log = DB::table('modification_logs')->where('id', $log_id)->first();

        if ($log) {
            $old = json_decode($log->old_values, true);
            $new = json_decode($log->new_values, true);
        } else {
            $old = $new = [];
        }


        return inertia('Numerisation/Postform', [
            'dossier' => $dossierPrincipal,
            'oldData' => $old,
            'newData' => $new,
        ]);
    }

    public function showDupliPostFormNumerisation($dossier)
    {
        // 🔵 1. Charger le dossier principal
        $dossierPrincipal = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions',
        ])
            ->where('id', $dossier)
            ->first();

        if (!$dossierPrincipal) {
            abort(404, 'Dossier non trouvé');
        }

        $dossierLier = null;
        // 🔵 2. Vérifier l'existence d'un dossier lié
        if (!empty($dossierPrincipal->id_dossier_lier)) {

            // Charger le dossier lié
            $dossierLier = Dossier::with([
                'r_dossier_vehicule',
                'r_dossier_user',
                'r_dossier_client',
                'r_dossier_documents',
                'r_dossier_services',
                'r_dossier_services.r_service_types',
                'r_dossier_transactions',
            ])
                ->where('id', $dossierPrincipal->id_dossier_lier)
                ->first();

            // 🔴 3. Si dossier lié → redirection vers selectDossier
            // return inertia('Numerisation/selectDossier', [
            //     'dossier' => $dossierPrincipal,
            //     'dossier_lier' => $dossierLier,
            // ]);
        }

        // 🟢 4. Pas de dossier lié → afficher le formulaire normal
        return inertia('Numerisation/formWithPost', [
            'dossier' => $dossierPrincipal,
            'dossier_lier' => $dossierLier,
        ]);
    }


    // recupérer tous les document d'un dosssier

    public function getDocumentsByDossierId($id_dossier)
    {
        try {
            // Charger le dossier avec ses documents
            $dossier = Dossier::with(['r_dossier_documents'])->find($id_dossier);

            if (!$dossier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dossier introuvable'
                ], 404);
            }

            // Vérifier que la relation existe et retourner les documents
            $documents = $dossier->r_dossier_documents;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'dossier' => $dossier,
                    'documents' => $documents
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Erreur interne lors de la récupération des documents", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'server_error',
                'message' => 'Une erreur est survenue lors de la récupération des documents.'
            ], 500);
        }
    }


    //duplicata / post-imatriculation
    public function saveNumerisation(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'id_dossier' => 'required|integer|exists:dossiers,id',
            // 'dossier_lier_id' => 'nullable|integer|exists:dossiers,id',
            'carte_grise' => 'nullable|file|mimes:jpg,jpeg,png',
            'piece_identite_en_cours_de_validite' => 'nullable|file|mimes:jpg,jpeg,png',
            'vignette' => 'nullable|file|mimes:jpg,jpeg,png',
            'assurance_en_cours_de_validite' => 'nullable|file|mimes:jpg,jpeg,png',
            'declaration_de_perte' => 'nullable|file|mimes:jpg,jpeg,png',
            'rti_chute_plaque' => 'nullable|file|mimes:jpg,jpeg,png',
            'rit_reimmat' => 'nullable|file|mimes:jpg,jpeg,png',
            'rti_modification' => 'nullable|file|mimes:jpg,jpeg,png',
            'fiche_mutation' => 'nullable|file|mimes:jpg,jpeg,png',
            'fiche_mutation_cgi' => 'nullable|file|mimes:jpg,jpeg,png',
            'piece_ancien_proprietaire' => 'nullable|file|mimes:jpg,jpeg,png',
            'facture_cie_sodeci' => 'nullable|file|mimes:jpg,jpeg,png',
            'registre_de_commerce' => 'nullable|file|mimes:jpg,jpeg,png',
            'dfe' => 'nullable|file|mimes:jpg,jpeg,png',
            'autorisation_societe_credit' => 'nullable|file|mimes:jpg,jpeg,png',
            'extrait_carte_grise' => 'nullable|file|mimes:jpg,jpeg,png',
            'registre_de_commerce_nouvelle_entreprise' => 'nullable|file|mimes:jpg,jpeg,png',
            'dfe_nouvelle_entreprise' => 'nullable|file|mimes:jpg,jpeg,png',
        ]);

        $paths = [];
        $fileFields = [
            'carte_grise',
            'piece_identite_en_cours_de_validite',
            'vignette',
            'assurance_en_cours_de_validite',
            'declaration_de_perte',
            'rti_chute_plaque',
            'rit_reimmat',
            'rti_modification',
            'fiche_mutation',
            'fiche_mutation_cgi',
            'piece_ancien_proprietaire',
            'facture_cie_sodeci',
            'registre_de_commerce',
            'dfe',
            'autorisation_societe_credit',
            'extrait_carte_grise',
            'registre_de_commerce_nouvelle_entreprise',
            'dfe_nouvelle_entreprise',
        ];

        // Stocker les fichiers et obtenir les chemins
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $paths[$field] = $request->file($field)->store('numerisations', 'public');
            }
        }

        // Sauvegarder ou mettre à jour le document pour le dossier principal
        $existingDocument = Document::where('id_dossier', $validated['id_dossier'])->first();
        if ($existingDocument) {
            $existingDocument->update(array_merge($validated, $paths));
        } else {
            Document::create(array_merge($validated, $paths, ['id_dossier' => $validated['id_dossier']]));
        }

        // Sauvegarder ou mettre à jour le document pour le dossier lié (si dossier_lier_id est fourni)
        if ($request->filled('dossier_lier_id')) {
            $existingLierDocument = Document::where('id_dossier', $request->dossier_lier_id)->first();
            if ($existingLierDocument) {
                $existingLierDocument->update(array_merge($validated, $paths));
            } else {
                Document::create(array_merge($validated, $paths, ['id_dossier' => $request->dossier_lier_id]));
            }
        }

        // Mettre à jour le statut des deux dossiers
        $dossier = Dossier::findOrFail($validated['id_dossier']);
        $dossier->statut_numerisation = 2;
        $dossier->save();

        if ($request->filled('dossier_lier_id')) {
            $dossierLier = Dossier::findOrFail($request->dossier_lier_id);
            $dossierLier->statut_numerisation = 2;
            $dossierLier->save();
        }

        return response()->json(['message' => 'Documents sauvegardés avec succès pour les deux dossiers !']);
    }

    //save immatriculation speciale numerisation
    public function saveOpsNumerisation(Request $request)
    {
        // Augmenter la limite mémoire pour le traitement PDF
        ini_set('memory_limit', '512M');
        Log::info('--- Début saveOpsNumerisation ID_Dossier: ' . $request->id_dossier . ' ---');

        // 🚨 NOUVELLE PROTECTION CONTRE LES ERREURS DE TAILLE D'UPLOAD (NGINX/PHP) 🚨
        if (!$request->hasFile('global_scan')) {
            Log::error("ERREUR UPLOAD: Le fichier 'global_scan' n'est pas présent dans la requête HTTP reçue par PHP.");
            return response()->json([
                'message' => "Le fichier n'a pas pu être uploadé (peut-être trop lourd pour les limites du serveur Nginx/PHP)."
            ], 422);
        }

        if (!$request->file('global_scan')->isValid()) {
            Log::error("ERREUR UPLOAD INVALID: " . $request->file('global_scan')->getErrorMessage());
            return response()->json([
                'message' => "Le téléchargement du fichier est invalide ou corrompu: " . $request->file('global_scan')->getErrorMessage()
            ], 422);
        }

        // Si le fichier existe et est valide, la validation Laravel ne crashera pas !
        $request->validate([
            'id_dossier' => 'required|exists:dossiers,id',
            'global_scan' => 'required|file|mimes:pdf',
        ]);

        $id_dossier = $request->id_dossier;
        $file = $request->file('global_scan');
        $tempPath = $file->getRealPath();

        $dossier = Dossier::with([
            'r_dossier_vehicule',
        ])->where('id', $id_dossier)->first();

        if (!$dossier) {
            Log::error("Dossier non trouvé pour ID: $id_dossier");
            return response()->json(['message' => 'Dossier non trouvé'], 404);
        }

        $vehicule = $dossier->r_dossier_vehicule;
        if (!$vehicule) {
            Log::error("Véhicule non trouvé pour le dossier ID: $id_dossier");
            return response()->json(['message' => 'Véhicule non trouvé pour ce dossier'], 404);
        }

        $vin = $vehicule->vin;

        Log::info("Fichier source: " . $file->getClientOriginalName() . " | Taille: " . $file->getSize() . " octets");

        // Configuration (16 pages pour 14 fichiers)
        $fieldsConfig = [
            ['field' => 'fiche_rti', 'filename' => 'rti', 'pages' => 1], // Page 1
            ['field' => 'fiche_civio', 'filename' => 'civio', 'pages' => 2], // Pages 2, 3
            ['field' => 'visite_technique', 'filename' => 'vtp', 'pages' => 1], // Page 4
            ['field' => 'formulaire_recensement', 'filename' => 'fr', 'pages' => 1], // Page 5
            ['field' => 'cni', 'filename' => 'pip', 'pages' => 1], // Page 6
            ['field' => 'carte_professionnelle', 'filename' => 'cp', 'pages' => 1], // Page 7
            ['field' => 'permis_conduire', 'filename' => 'pc', 'pages' => 1], // Page 8
            ['field' => 'd3', 'filename' => 'd3', 'pages' => 2], // Pages 9, 10
            ['field' => 'assurance', 'filename' => 'Ass', 'pages' => 1], // Page 11
            ['field' => 'fiche_demande_carte_grise', 'filename' => 'dcg', 'pages' => 1], // Page 12
            ['field' => 'recepisse_depot', 'filename' => 'rdd', 'pages' => 1], // Page 13
            ['field' => 'recu_dgttc', 'filename' => 'rdgttc', 'pages' => 1], // Page 14
            ['field' => 'recu_emuci', 'filename' => 'remuci', 'pages' => 1], // Page 15
            ['field' => 'recu_quipux', 'filename' => 'rquipux', 'pages' => 1], // Page 16
        ];

        $id_site = getIdSite();
        $folderMapping = [
            3 => 'BAE',
            2 => 'AKOUEDO',
            1 => 'AGBAN',
        ];
        $folder = $folderMapping[$id_site] ?? 'OTHERS';

        $paths = [];
        $zipDirectory = 'downloads/' . $folder;

        // Création du sous-répertoire si nécessaire
        if (!Storage::disk('public')->exists($zipDirectory)) {
            Log::info("Création sous-répertoire: $zipDirectory");
            Storage::disk('public')->makeDirectory($zipDirectory);
        }

        $zipFileName = $vin . '.zip';
        $zipFilePath = Storage::disk('public')->path($zipDirectory . '/' . $zipFileName);

        $zip = new \ZipArchive();
        $zipOpened = $zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($zipOpened !== TRUE) {
            Log::error("Erreur ZipArchive : Impossible d'ouvrir le fichier à " . $zipFilePath);
        }

        // Variable pour stocker le chemin FTP final
        $ftpPathForDb = null;

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tempPath);
            Log::info("Nombre de pages total dans le PDF : " . $pageCount);

            $currentPage = 1;
            foreach ($fieldsConfig as $config) {
                if ($currentPage > $pageCount)
                    break;

                $field = $config['field'];
                $pagesToExtract = $config['pages'];
                $filenameAlias = $config['filename'];

                Log::info("Traitement page $currentPage pour le champ : $field");

                $newPdf = new Fpdi();

                for ($i = 0; $i < $pagesToExtract; $i++) {
                    if ($currentPage > $pageCount)
                        break;

                    $newPdf->setSourceFile($tempPath);
                    $template = $newPdf->importPage($currentPage);
                    $size = $newPdf->getTemplateSize($template);
                    $newPdf->addPage($size['orientation'], [$size['width'], $size['height']]);
                    $newPdf->useTemplate($template);

                    $currentPage++;
                }

                $output = $newPdf->Output('S');

                $fileName = 'numerisations/' . $vin . '/' . $filenameAlias . '.pdf';
                Storage::disk('public')->put($fileName, $output);
                $paths[$field] = $fileName;

                if ($zipOpened === TRUE) {
                    $zip->addFromString($filenameAlias . '.pdf', $output);
                }
            }

            if ($zipOpened === TRUE) {
                $zip->close();
                Log::info("Fichier ZIP fermé avec succès.");

                // ========== UPLOAD FTP AVEC STRUCTURE DE DOSSIERS ==========
                try {
                    $zipContent = file_get_contents($zipFilePath);
                    $ftpDisk = Storage::disk('ftp_scandfs');

                    // Format de date : 19042026
                    $dateFolder = now()->format('dmY');

                    // Construction des chemins
                    $basePath = $folder; // BAE, AKOUEDO ou AGBAN
                    $carteGrisePath = $basePath . '/CARTE GRISE EDITEE';
                    $docFormalitePath = $basePath . '/DOCUMENT DE FORMALITE';
                    $datePath = $docFormalitePath . '/' . $dateFolder;
                    $ftpFilePath = $datePath . '/' . $zipFileName;

                    // Création des dossiers permanents (une seule fois)
                    if (!$ftpDisk->exists($carteGrisePath)) {
                        $ftpDisk->makeDirectory($carteGrisePath);
                        Log::info("Création dossier FTP: $carteGrisePath");
                    }

                    if (!$ftpDisk->exists($docFormalitePath)) {
                        $ftpDisk->makeDirectory($docFormalitePath);
                        Log::info("Création dossier FTP: $docFormalitePath");
                    }

                    // Création du dossier daté (à chaque upload s'il n'existe pas)
                    if (!$ftpDisk->exists($datePath)) {
                        $ftpDisk->makeDirectory($datePath);
                        Log::info("Création dossier FTP daté: $datePath");
                    }

                    // Upload du fichier ZIP
                    $ftpDisk->put($ftpFilePath, $zipContent);
                    Log::info("Fichier ZIP uploadé sur FTP: $ftpFilePath");

                    // Stockage du chemin pour la base de données
                    $ftpPathForDb = 'ftp://' . $ftpFilePath;

                    // Suppression du fichier local
                    unlink($zipFilePath);
                    Log::info("Fichier ZIP local supprimé.");
                } catch (\Exception $ftpError) {
                    Log::error("Erreur lors de l'upload FTP : " . $ftpError->getMessage());
                    // Fallback sur l'ancien chemin si l'upload échoue
                    $ftpPathForDb = 'ftp://' . $folder . '/' . $zipFileName;
                }
                // ========== FIN UPLOAD FTP ==========
            }

            // Mise à jour base de données
            $dataToSave = array_merge($paths, [
                'id_dossier' => $id_dossier,
                'chemin_zip' => $ftpPathForDb ?? 'ftp://' . $folder . '/' . $zipFileName
            ]);

            Document::updateOrCreate(['id_dossier' => $id_dossier], $dataToSave);
            Dossier::where('id', $id_dossier)->update([
                'statut_numerisation' => 2,
                'id_site' => getIdSite(),
                'date_numerisation' => now(),
            ]);

            Log::info("Opération terminée avec succès pour le dossier : $id_dossier");
            $this->extractAndSaveEmuci($id_dossier, $tempPath, $dossier->r_dossier_vehicule->vin, $dossier->num_chrono);
            $this->sendDocumentDataRequest($id_dossier);

            return response()->json(['message' => 'Traitement réussi !']);
        } catch (\Throwable $e) {
            Log::error("CRITIQUE - Erreur traitement PDF : " . $e->getMessage());
            Log::error("Trace : " . $e->getTraceAsString());
            return response()->json(['message' => 'Erreur technique: ' . $e->getMessage()], 500);
        } finally {
            // Nettoyage du fichier normalisé temporaire
            if (isset($normalizedPath) && $normalizedPath && file_exists($normalizedPath)) {
                unlink($normalizedPath);
            }
        }
    }

    //save immatriculation speciale numerisation via api
    public function saveOpsNumerisationApi(Request $request)
    {
        $request->validate([
            'id_dossier' => 'required|integer|exists:dossiers,id',
            'global_scan' => 'required|file|mimes:pdf',
        ]);

        $id_dossier = $request->id_dossier;
        $file = $request->file('global_scan');
        $tempPath = $file->getRealPath();

        $dossier = Dossier::with(['r_dossier_vehicule'])
            ->where('id', $id_dossier)
            ->first();

        if (!$dossier) {
            return response()->json(['message' => 'Dossier non trouvé'], 404);
        }

        $vehicule = $dossier->r_dossier_vehicule;
        if (!$vehicule) {
            return response()->json(['message' => 'Véhicule non trouvé pour ce dossier'], 404);
        }

        $vin = $vehicule->vin;

        // Configuration (16 pages pour 14 fichiers)
        $fieldsConfig = [
            ['field' => 'fiche_rti', 'filename' => 'rti', 'pages' => 1],
            ['field' => 'fiche_civio', 'filename' => 'civio', 'pages' => 2],
            ['field' => 'visite_technique', 'filename' => 'vtp', 'pages' => 1],
            ['field' => 'formulaire_recensement', 'filename' => 'fr', 'pages' => 1],
            ['field' => 'cni', 'filename' => 'pip', 'pages' => 1],
            ['field' => 'carte_professionnelle', 'filename' => 'cp', 'pages' => 1],
            ['field' => 'permis_conduire', 'filename' => 'pc', 'pages' => 1],
            ['field' => 'd3', 'filename' => 'd3', 'pages' => 2],
            ['field' => 'assurance', 'filename' => 'Ass', 'pages' => 1],
            ['field' => 'fiche_demande_carte_grise', 'filename' => 'dcg', 'pages' => 1],
            ['field' => 'recepisse_depot', 'filename' => 'rdd', 'pages' => 1],
            ['field' => 'recu_dgttc', 'filename' => 'rdgttc', 'pages' => 1],
            ['field' => 'recu_emuci', 'filename' => 'remuci', 'pages' => 1],
            ['field' => 'recu_quipux', 'filename' => 'rquipux', 'pages' => 1],
        ];

        $id_site = getIdSite();
        $folderMapping = [
            3 => 'BAE',
            2 => 'AKOUEDO',
            1 => 'AGBAN',
        ];
        $folder = $folderMapping[$id_site] ?? 'OTHERS';

        $paths = [];
        $zipDirectory = 'downloads/' . $folder;

        if (!Storage::disk('public')->exists($zipDirectory)) {
            Storage::disk('public')->makeDirectory($zipDirectory);
        }

        $zipFileName = $vin . '.zip';
        $zipFilePath = Storage::disk('public')->path($zipDirectory . '/' . $zipFileName);
        $zip = new \ZipArchive();
        $zipOpened = $zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tempPath);
            $currentPage = 1;

            foreach ($fieldsConfig as $config) {
                if ($currentPage > $pageCount) {
                    break;
                }

                $field = $config['field'];
                $pagesToExtract = $config['pages'];
                $filenameAlias = $config['filename'];
                $newPdf = new Fpdi();

                for ($i = 0; $i < $pagesToExtract; $i++) {
                    if ($currentPage > $pageCount) {
                        break;
                    }

                    $newPdf->setSourceFile($tempPath);
                    $template = $newPdf->importPage($currentPage);
                    $size = $newPdf->getTemplateSize($template);
                    $newPdf->addPage($size['orientation'], [$size['width'], $size['height']]);
                    $newPdf->useTemplate($template);
                    $currentPage++;
                }

                $fileName = 'numerisations/' . $vin . '/' . $filenameAlias . '.pdf';
                $output = $newPdf->Output('S');
                Storage::disk('public')->put($fileName, $output);
                $paths[$field] = $fileName;

                if ($zipOpened === TRUE) {
                    $zip->addFromString($filenameAlias . '.pdf', $output);
                }
            }

            if ($zipOpened === TRUE) {
                $zip->close();

                // ========== DÉBUT MODIFICATION FTP ==========
                try {
                    $zipContent = file_get_contents($zipFilePath);
                    $ftpDisk = Storage::disk('ftp_scandfs');

                    // Format de date : 13042026
                    $dateFolder = now()->format('dmY');

                    // Construction des chemins
                    $basePath = $folder; // BAE, AKOUEDO ou AGBAN
                    $carteGrisePath = $basePath . '/CARTE GRISE EDITEE';
                    $docFormalitePath = $basePath . '/DOCUMENT DE FORMALITE';
                    $datePath = $docFormalitePath . '/' . $dateFolder;
                    $ftpFilePath = $datePath . '/' . $zipFileName;

                    // Création des dossiers permanents (une seule fois)
                    if (!$ftpDisk->exists($carteGrisePath)) {
                        $ftpDisk->makeDirectory($carteGrisePath);
                    }

                    if (!$ftpDisk->exists($docFormalitePath)) {
                        $ftpDisk->makeDirectory($docFormalitePath);
                    }

                    // Création du dossier daté (à chaque upload s'il n'existe pas)
                    if (!$ftpDisk->exists($datePath)) {
                        $ftpDisk->makeDirectory($datePath);
                    }

                    // Upload du fichier ZIP
                    $ftpDisk->put($ftpFilePath, $zipContent);
                    Log::info("API - Fichier ZIP uploadé sur FTP: $ftpFilePath");

                    // Suppression du fichier local
                    unlink($zipFilePath);

                    // Mise à jour du chemin stocké en base
                    $ftpPathForDb = 'ftp://' . $ftpFilePath;
                } catch (\Exception $ftpError) {
                    Log::error("API - Erreur lors de l'upload FTP : " . $ftpError->getMessage());
                    $ftpPathForDb = null;
                }
                // ========== FIN MODIFICATION FTP ==========
            }

            // Sauvegarde en base
            $existingDocument = Document::where('id_dossier', $id_dossier)->first();
            $dataToSave = array_merge($paths, [
                'id_dossier' => $id_dossier,
                'chemin_zip' => $ftpPathForDb ?? 'ftp://' . $folder . '/' . $zipFileName
            ]);

            if ($existingDocument) {
                $existingDocument->update($dataToSave);
            } else {
                Document::create($dataToSave);
            }

            // Mettre à jour le statut du dossier
            $dossier = Dossier::findOrFail($id_dossier);
            $dossier->statut_numerisation = 2;
            $dossier->date_numerisation = now();
            $dossier->id_site = getIdSite();
            $dossier->save();

            $this->extractAndSaveEmuci($id_dossier, $tempPath, $vehicule->vin, $dossier->num_chrono);
            $this->sendDocumentDataRequest($id_dossier);

            return response()->json(['message' => 'Numérisation traitée et scindée (16 pages) avec succès !']);
        } catch (\Exception $e) {
            Log::error("Erreur lors du découpage PDF (16 pages) : " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors du traitement du fichier PDF - ' . $e->getMessage()], 500);
        } finally {
            if (isset($normalizedPath) && $normalizedPath && file_exists($normalizedPath)) {
                unlink($normalizedPath);
            }
        }
    }

    public function savePostNumerisation(Request $request)
    {
        // Validation des champs obligatoires pour "Post-immatriculation"
        $rules = [
            'id_dossier' => 'required|integer|exists:dossiers,id',
            'vignette' => 'required|file|mimes:jpg,jpeg,png',
            'assurance_en_cours_de_validite' => 'required|file|mimes:jpg,jpeg,png',
            'carte_grise' => 'required|file|mimes:jpg,jpeg,png',
            'rti_modification' => 'required|file|mimes:jpg,jpeg,png',
        ];

        // Messages d'erreur personnalisés
        $messages = [
            'vignette.required' => 'La vignette est obligatoire.',
            'assurance_en_cours_de_validite.required' => 'L\'assurance en cours de validité est obligatoire.',
            'carte_grise.required' => 'La carte grise est obligatoire.',
            'rti_modification.required' => 'Le RTI signalant la modification est obligatoire.',
        ];

        // Si le dossier est une mutation (changement de propriétaire)
        if ($request->has('fiche_mutation')) {
            $rules['fiche_mutation'] = 'required|file|mimes:jpg,jpeg,png';
            $messages['fiche_mutation.required'] = 'La fiche de mutation CGI est obligatoire.';

            // Validation des documents pour l'ancien propriétaire
            if ($request->has('type_piece_ancien_proprietaire')) {
                $rules['type_piece_ancien_proprietaire'] = 'required|string';
                $rules['piece_ancien_proprietaire'] = 'required|file|mimes:jpg,jpeg,png';
                $messages['type_piece_ancien_proprietaire.required'] = 'Le type de pièce de l\'ancien propriétaire est obligatoire.';
                $messages['piece_ancien_proprietaire.required'] = 'La pièce de l\'ancien propriétaire est obligatoire.';
            } else {
                $rules['registre_de_commerce'] = 'required|file|mimes:jpg,jpeg,png';
                $rules['dfe'] = 'required|file|mimes:jpg,jpeg,png';
                $messages['registre_de_commerce.required'] = 'Le registre de commerce de l\'ancien propriétaire est obligatoire.';
                $messages['dfe.required'] = 'Le DFE de l\'ancien propriétaire est obligatoire.';
            }

            // Validation des documents pour le nouveau propriétaire
            if ($request->has('type_piece_nouveau_proprietaire')) {
                $rules['type_piece_nouveau_proprietaire'] = 'required|string';
                $rules['piece'] = 'required|file|mimes:jpg,jpeg,png';
                $messages['type_piece_nouveau_proprietaire.required'] = 'Le type de pièce du nouveau propriétaire est obligatoire.';
                $messages['piece.required'] = 'La pièce du nouveau propriétaire est obligatoire.';
            } else {
                $rules['registre_de_commerce_nouvelle_entreprise'] = 'required|file|mimes:jpg,jpeg,png';
                $rules['dfe_nouvelle_entreprise'] = 'required|file|mimes:jpg,jpeg,png';
                $messages['registre_de_commerce_nouvelle_entreprise.required'] = 'Le registre de commerce du nouveau propriétaire est obligatoire.';
                $messages['dfe_nouvelle_entreprise.required'] = 'Le DFE du nouveau propriétaire est obligatoire.';
            }
        }

        // Validation des données
        $validated = $request->validate($rules, $messages);

        // Liste des champs de fichiers à traiter
        $fileFields = [
            'vignette',
            'assurance_en_cours_de_validite',
            'carte_grise',
            'rti_modification',
            'fiche_mutation',
            'piece_ancien_proprietaire',
            'registre_de_commerce',
            'dfe',
            'piece',
            'registre_de_commerce_nouvelle_entreprise',
            'dfe_nouvelle_entreprise',
        ];

        // Chemins des fichiers téléchargés
        $paths = [];

        // Vérifier si un document existe déjà pour ce dossier
        $existingDocument = Document::where('id_dossier', $validated['id_dossier'])->first();

        if ($existingDocument) {
            // Mettre à jour uniquement les champs fournis
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $paths[$field] = $request->file($field)->store('numerisations', 'public');
                }
            }
            $existingDocument->update(array_merge($validated, $paths));
        } else {
            // Créer un nouveau document avec tous les fichiers
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $paths[$field] = $request->file($field)->store('numerisations', 'public');
                }
            }
            Document::create(array_merge($validated, $paths));
        }

        // Mettre à jour le statut du dossier
        $dossier = Dossier::findOrFail($request->id_dossier);
        $dossier->statut_numerisation = 2;
        $dossier->save();

        return response()->json(['message' => 'Dossier sauvegardé avec succès !']);
    }

    public function updateNumerisation(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id',
            'field' => 'required|string',
            'file' => 'required|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $doc = Document::findOrFail($request->document_id);
        try {
            // Sauvegarde du fichier
            $path = $request->file('file')->store('documents', 'public');
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de sauvegarder le fichier.'
                ], 500);
            }

            // Mise à jour du champ spécifique
            $doc->{$request->field} = $path;
            $doc->save();

            return response()->json([
                'success' => true,
                'path' => $path
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    // modifier document de l'anumerisation
    public function updateDocument(Request $request, $id)
    {
        try {
            // 1. Récupérer le document par ID
            $document = Document::findOrFail($id);

            // 2. Validation (optionnelle – à toi de décider si tu veux la garder ou non)
            $validated = $request->validate([
                'id_dossier' => ['sometimes', 'exists:dossiers,id'],
                'type_document' => ['sometimes', 'in:Re-immatriculation Ordinaire,piece_identite,certificat_residence'],
                'carte_grise' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                'piece' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'], // remplace 'piece_identite'
                'certificat_de_visite_technique' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                'assurance' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                'declaration_perte' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                'certificat_residence' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                // 'registre_commerce' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                // 'piece_entreprise_commerce' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                // 'autorisation_societe_credit' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
                // 'extrait_carte_grise' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg,webp', 'max:2048'],
            ]);

            $disk = 'public';

            // Fonction utilitaire pour conserver l'existant si aucun nouveau fichier
            $getFile = function ($fieldName) use ($request, $document, $disk) {
                if ($request->hasFile($fieldName)) {
                    return $request->file($fieldName)->store("documents", $disk);
                }

                return $document->{$fieldName} ?? null;
            };

            // Mise à jour conditionnelle : uniquement si le champ est présent
            $documentData = [];

            if ($request->filled('id_dossier')) {
                $documentData['id_dossier'] = $request->input('id_dossier');
            }

            if ($request->filled('type_document')) {
                $documentData['type_document'] = $request->input('type_document');
            }

            // Liste des champs fichier
            $fieldsWithFiles = [
                'carte_grise',
                'piece', // renommé depuis 'piece_identite'
                'certifica_visite_technique',
                'assurance_en_cours_de_validite',
                'declaration_de_perte',
                'certificat_de_residence',
                'registre_de_commerce',
                'piece_identite_en_cours_de_validite',
                'autorisation_de_la_societe_de_credit',
                'extrait_de_carte_grise',
            ];

            foreach ($fieldsWithFiles as $field) {
                // Récupère la valeur du champ (texte, checkbox, select, etc.)
                $value = $request->input($field);

                // Si la valeur existe, on l'ajoute à $documentData
                if ($value !== null) {
                    $documentData[$field] = $value;
                }
            }


            $now = now(); // ou \Carbon\Carbon::now();
            $documentData['updated_at'] = $now;

            // Mettre à jour seulement si des données sont présentes
            if (!empty(array_filter($documentData))) {
                $document->update($documentData);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Document mis à jour.',
                'data' => $documentData,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Document introuvable.'
            ], 404);
        } catch (\Throwable $e) {
            Log::error("Erreur interne lors de la mise à jour", [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace()
            ]);

            return response()->json([
                'status' => 'server_error',
                'message' => 'Une erreur est survenue lors de la mise à jour.' . $e
            ], 500);
        }
    }

    public function updateSingleField(Request $request)
    {
        try {
            // Récupérer les données
            $documentId = $request->input('document_id');
            $fieldName = $request->input('field_name'); // ex: 'carte_grise'
            $fileUrl = $request->input($fieldName); // URL Cloudinary

            // Vérification des données
            if (!$documentId || !$fieldName || !$fileUrl) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Données manquantes.'
                ], 400);
            }

            // Trouver le document
            $document = Document::findOrFail($documentId);

            // Mettre à jour le champ avec l'URL Cloudinary
            $document->{$fieldName} = $fileUrl;

            // Sauvegarder les modifications
            $document->save();

            // Réponse de succès
            return response()->json([
                'status' => 'success',
                'message' => 'Champ mis à jour avec succès.',
                'url' => $fileUrl,
                'field' => $fieldName
            ]);
        } catch (ModelNotFoundException $e) {
            // Si le document n'existe pas
            return response()->json([
                'status' => 'error',
                'message' => 'Document non trouvé.'
            ], 404);
        } catch (\Exception $e) {
            // Erreur générale
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la mise à jour.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showModificationGetData($vin)
    {
        $dossiers = Dossier::with([
            'r_dossier_vehicule',
            'r_dossier_user',
            'r_dossier_client',
            'r_dossier_documents',
            'r_dossier_services',
            'r_dossier_services.r_service_types',
            'r_dossier_transactions'
        ])->where('num_chrono', $vin)->first();

        if (!$dossiers) {
            abort(404, 'Dossier non trouvé');
        }

        return inertia('Numerisation/components/Modification', [
            'chrono' => $vin,
            'dossier' => $dossiers,
        ]);
    }

    protected function extractAndSaveEmuci($id_dossier, $tempPath, $vin, $num_chrono)
    {
        ini_set('memory_limit', '512M');
        // Mapping EMUCI (16 pages)
        $fieldsConfig = [
            ['field' => 'rti', 'filename' => 'rti', 'pages' => 3], // P1-3 (rti+civio)
            ['field' => 'rcil', 'filename' => 'rcil', 'pages' => 3], // P4-6 (receipts)
            ['field' => 'pir', 'filename' => 'Pir', 'pages' => 3], // P7-9 (identities)
            ['field' => 'dcg', 'filename' => 'dcg', 'pages' => 2], // P10-11 (Dcg+Assurance)
            ['field' => 'd3', 'filename' => 'd3', 'pages' => 3], // P12-14 (fr+d3)
            ['field' => 'cvt', 'filename' => 'cvt', 'pages' => 1], // P15 (vtp)
            ['field' => 'cmc', 'filename' => 'cmc', 'pages' => 1], // P16 (rdd)
        ];

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tempPath);
            $currentPage = 1;

            $paths = [];
            foreach ($fieldsConfig as $config) {
                if ($currentPage > $pageCount)
                    break;

                $newPdf = new Fpdi();
                for ($i = 0; $i < $config['pages']; $i++) {
                    if ($currentPage > $pageCount)
                        break;
                    $newPdf->setSourceFile($tempPath);
                    $template = $newPdf->importPage($currentPage);
                    $size = $newPdf->getTemplateSize($template);
                    $newPdf->addPage($size['orientation'], [$size['width'], $size['height']]);
                    $newPdf->useTemplate($template);
                    $currentPage++;
                }

                $output = $newPdf->Output('S');
                $fileName = 'numerisations/emuci/' . $num_chrono . '/' . $config['filename'] . '.pdf';
                Storage::disk('public')->put($fileName, $output);
                $paths[$config['field']] = $fileName;
            }

            EmuciDocument::updateOrCreate(['id_dossier' => $id_dossier], $paths);
            return true;
        } catch (\Throwable $e) {
            Log::error("Erreur extraction EMUCI pour dossier $id_dossier : " . $e->getMessage());
            return false;
        }
    }


    protected function sendDocumentDataRequest($id_dossier)
    {
        //récupérer document_emuci
        $emuciDocument = EmuciDocument::where('id_dossier', $id_dossier)->first();
        if (!$emuciDocument) {
            Log::error("Aucun document EMUCI trouvé pour le dossier ID: $id_dossier");
            return response()->json(['error' => 'Aucun document EMUCI trouvé pour ce dossier'], 404);
        }

        //récupérer le num_chrono dans dossier, le numero_chassis dans r_dossier_vehicule et le num_immatriculation dans r_dossier_vehicule
        $dossier = Dossier::with([
            'r_dossier_vehicule',
        ])->find($id_dossier);

        if (!$dossier) {
            Log::error("Dossier non trouvé pour ID: $id_dossier");
            return response()->json(['error' => 'Dossier non trouvé'], 404);
        }

        $numChronoCil = $dossier->num_chrono;
        $numeroChassisVehicule = $dossier->r_dossier_vehicule?->vin ?? '';
        $numImmat = $dossier->r_dossier_vehicule?->num_immatriculation ?? '';

        $listeDocuments = [
            "Liste_documents" => [
                [
                    "Code_document" => "rti",
                    "Libelle_document" => "réception à titre isolé",
                    "Url_document" => $emuciDocument->rti ? 'https://placenett.net/storage/' . $emuciDocument->rti : ''
                ],
                [
                    "Code_document" => "rcil",
                    "Libelle_document" => "reçu CIL",
                    "Url_document" => $emuciDocument->rcil ? 'https://placenett.net/storage/' . $emuciDocument->rcil : ''
                ],
                [
                    "Code_document" => "pir",
                    "Libelle_document" => "Pièce d'identité recto ; Pièce d'identité recto du gérant",
                    "Url_document" => $emuciDocument->pir ? 'https://placenett.net/storage/' . $emuciDocument->pir : ''
                ],
                [
                    "Code_document" => "dcg",
                    "Libelle_document" => "demande de carte grise",
                    "Url_document" => $emuciDocument->dcg ? 'https://placenett.net/storage/' . $emuciDocument->dcg : ''
                ],
                [
                    "Code_document" => "d3",
                    "Libelle_document" => "déclaration D3",
                    "Url_document" => $emuciDocument->d3 ? 'https://placenett.net/storage/' . $emuciDocument->d3 : ''
                ],
                [
                    "Code_document" => "cvt",
                    "Libelle_document" => "contrôle visite technique",
                    "Url_document" => $emuciDocument->cvt ? 'https://placenett.net/storage/' . $emuciDocument->cvt : ''
                ],
                [
                    "Code_document" => "cmc",
                    "Libelle_document" => "certificat de mise à la consommation",
                    "Url_document" => $emuciDocument->cmc ? 'https://placenett.net/storage/' . $emuciDocument->cmc : ''
                ],
            ]
        ];

        try {
            $result = sendDocumentData($numChronoCil, $numeroChassisVehicule, $numImmat, $listeDocuments);
            Log::info("Réponse de sendDocumentData pour dossier $id_dossier", ['result' => $result]);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error("Erreur lors de l'envoi des documents pour dossier $id_dossier : " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
