<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{

    // Get stats grouped by service for the current day
    public function getStatsByDay()
    {
        $today = Carbon::today();

        $results = DB::table('dossiers')
            ->selectRaw('
            id_service,
            COUNT(CASE WHEN statut = 2 THEN 1 END) AS `Terminer`,
            COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente`,
            COUNT(CASE WHEN statut = 3 THEN 1 END) AS `Rejeter`,
            COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`
        ')
            ->whereDate('date_creation', $today)
            ->groupBy('id_service')
            ->get();

        return response()->json($results);
    }

    // Get stats grouped by service for the current week
    public function getStatsByWeek()
    {
        $startOfWeek = Carbon::now()->startOfWeek(); // lundi par défaut
        $endOfWeek = Carbon::now()->endOfWeek();

        $results = DB::table('dossiers')
            ->selectRaw('
            id_service,
            COUNT(CASE WHEN statut = 2 THEN 1 END) AS `Terminer`,
            COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente`,
            COUNT(CASE WHEN statut = 3 THEN 1 END) AS `Rejeter`,
            COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`
        ')
            ->whereBetween('date_creation', [$startOfWeek, $endOfWeek])
            ->groupBy('id_service')
            ->get();

        return response()->json($results);
    }

    // Get stats grouped by service for the current month
    public function getStatsByMonth()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $results = DB::table('dossiers')
            ->selectRaw('
            id_service,
            COUNT(CASE WHEN statut = 2 THEN 1 END) AS `Terminer`,
            COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente`,
            COUNT(CASE WHEN statut = 3 THEN 1 END) AS `Rejeter`,
            COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`
        ')
            ->whereBetween('date_creation', [$startOfMonth, $endOfMonth])
            ->groupBy('id_service')
            ->get();

        return response()->json($results);
    }

    public function getStats()
    {
        // Forcer la langue française pour les noms de mois
        DB::statement("SET lc_time_names = 'fr_FR'");

        $results = DB::table('dossiers')
            ->selectRaw('
        DATE_FORMAT(STR_TO_DATE(date_creation, "%Y-%m-%d"), "%M") AS name,
        COUNT(*) as Total,
        COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`,
        COUNT(CASE WHEN statut_numerisation = 2 THEN 1 END) AS `Dossier numérisé`,
        COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente de traitement`
    ')
            ->whereNotNull('date_creation')
            ->groupBy('name')
            ->orderByRaw("STR_TO_DATE(CONCAT('2025-', name, '-01'), '%Y-%M-%d')")
            ->get();

        return response()->json($results);
    }


    public function getStatsByDate(Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        if (!$start || !$end) {
            return response()->json(['error' => 'start_date et end_date sont requis'], 422);
        }

        // Langue française pour les mois
        DB::statement("SET lc_time_names = 'fr_FR'");

        $results = DB::table('dossiers')
            ->selectRaw('
            DATE_FORMAT(STR_TO_DATE(date_creation, "%Y-%m-%d"), "%Y-%m") as month,
            DATE_FORMAT(STR_TO_DATE(date_creation, "%Y-%m-%d"), "%M") as name,
            COUNT(*) as Total,
            COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`,
            COUNT(CASE WHEN statut_numerisation = 2 THEN 1 END) AS `Dossier numérisé`,
            COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente de traitement`
        ')
            ->whereBetween(
            DB::raw('STR_TO_DATE(date_creation, "%Y-%m-%d")'),
        [$start, $end]
        )
            ->groupBy('month', 'name')
            ->orderBy('month')
            ->get();

        return response()->json($results);
    }


    public function getGlobalStats()
    {
        // Forcer la langue française pour les noms de mois
        DB::statement("SET lc_time_names = 'fr_FR'");

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        // 📌 Stats par mois
        $monthlyStats = DB::table('dossiers')
            ->selectRaw('
            DATE_FORMAT(STR_TO_DATE(date_creation, "%Y-%m-%d"), "%M") AS month,
            COUNT(*) as Total,
            COUNT(CASE WHEN statut = 2 THEN 1 END) AS `Terminer`,
            COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente`,
            COUNT(CASE WHEN statut = 3 THEN 1 END) AS `Rejeter`,
            COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`,
            COUNT(CASE WHEN id_service = 1 THEN 1 END) AS `Immatriculation-Special`,
            COUNT(CASE WHEN id_service = 2 THEN 1 END) AS `Re-immatriculation`,
            COUNT(CASE WHEN id_service = 3 THEN 1 END) AS `Post-immatriculation`,
            COUNT(CASE WHEN id_service = 4 THEN 1 END) AS `Duplicata`
        ')
            ->whereNotNull('date_creation')
            ->whereBetween('date_creation', [$startOfMonth, $endOfMonth])
            ->groupBy('month')
            // ->orderByRaw("STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', name, '-01'), '%Y-%M-%d')")
            ->get();

        $startOfWeek = Carbon::now()->startOfWeek(); // lundi par défaut
        $endOfWeek = Carbon::now()->endOfWeek();

        // 📌 Stats par semaine en cours
        $weeklyStats = DB::table('dossiers')
            ->selectRaw('
            WEEK(date_creation, 1) AS week_number,
            COUNT(*) as Total,
            COUNT(CASE WHEN statut = 2 THEN 1 END) AS `Terminer`,
            COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente`,
            COUNT(CASE WHEN statut = 3 THEN 1 END) AS `Rejeter`,
            COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`,
            COUNT(CASE WHEN id_service = 1 THEN 1 END) AS `Immatriculation-Special`,
            COUNT(CASE WHEN id_service = 2 THEN 1 END) AS `Re-immatriculation`,
            COUNT(CASE WHEN id_service = 3 THEN 1 END) AS `Post-immatriculation`,
            COUNT(CASE WHEN id_service = 4 THEN 1 END) AS `Duplicata`
        ')
            ->whereBetween('date_creation', [$startOfWeek, $endOfWeek])
            // ->groupBy('id_service')
            // ->get();
            ->groupBy('week_number')
            ->get();

        // 📌 Stats du jour
        $dailyStats = DB::table('dossiers')
            ->selectRaw('
            DATE(date_creation) AS day,
            COUNT(*) as Total,
            COUNT(CASE WHEN statut = 2 THEN 1 END) AS `Terminer`,
            COUNT(CASE WHEN statut = 1 THEN 1 END) AS `En attente`,
            COUNT(CASE WHEN statut = 3 THEN 1 END) AS `Rejeter`,
            COUNT(CASE WHEN statut = 4 THEN 1 END) AS `En cours de traitement`,
            COUNT(CASE WHEN id_service = 1 THEN 1 END) AS `Immatriculation-Special`,
            COUNT(CASE WHEN id_service = 2 THEN 1 END) AS `Re-immatriculation`,
            COUNT(CASE WHEN id_service = 3 THEN 1 END) AS `Post-immatriculation`,
            COUNT(CASE WHEN id_service = 4 THEN 1 END) AS `Duplicata`
        ')
            ->whereDate('date_creation', now()->toDateString())
            ->groupBy('day')
            ->get();

        return response()->json([
            'par_mois' => $monthlyStats,
            'par_semaine' => $weeklyStats,
            'par_jour' => $dailyStats,
        ]);
    }


    public function getAdminGlobalStats(Request $request)
    {
        // Période
        $periode = $request->input('periode', 'today');

        switch ($periode) {
            case 'today':
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            default:
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
        }

        /**
         * 1️⃣ TOTAL ENRÔLEMENT (date_creation)
         */
        $totalSites = DB::table('dossiers')
            ->join('sites', function ($join) {
            $join->on('dossiers.id_site', '=', 'sites.id')
                ->orOn('dossiers.id_site', '=', DB::raw(0));
        })
            ->whereBetween('dossiers.date_creation', [$startDate, $endDate])
            ->select('sites.nom_site', DB::raw('COUNT(*) as total'))
            ->groupBy('sites.nom_site')
            ->get();

        // $totalSites = DB::table('dossiers')
        //     ->whereBetween('date_creation', [$startDate, $endDate])
        //     ->count();

        /**
         * 2️⃣ DOSSIERS NUMÉRISÉS (date_numerisation)
         */
        $dossiersNumerises = DB::table('dossiers')
            ->join('sites', 'dossiers.id_site', '=', 'sites.id')
            ->whereNotNull('dossiers.date_numerisation')
            ->whereBetween('dossiers.date_numerisation', [$startDate, $endDate])
            ->select('sites.nom_site', DB::raw('COUNT(*) as total'))
            ->groupBy('sites.nom_site')
            ->get();

        /**
         * 3️⃣ DOSSIERS EN ATTENTE (statut = 1)
         * basé sur date_creation (logique métier)
         */
        $attenteSites = DB::table('dossiers')
            ->join('sites', 'dossiers.id_site', '=', 'sites.id')
            ->where('dossiers.statut', 1)
            ->whereBetween('dossiers.date_creation', [$startDate, $endDate])
            ->select('sites.nom_site', DB::raw('COUNT(*) as total'))
            ->groupBy('sites.nom_site')
            ->get();

        return response()->json([
            'periode' => $periode,
            'total_sites' => $totalSites,
            'dossiers_numerises' => $dossiersNumerises,
            'attente_sites' => $attenteSites,
        ]);
    }
}