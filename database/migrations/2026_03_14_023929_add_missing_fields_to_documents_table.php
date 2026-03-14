<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = [
            'bon_a_enlever',
            'declaration_d3',
            'quittance_douane',
            'visite_technique',
            'chemin_zip',
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
            'piece',
            'type_document',
            'certifica_visite_technique',
            'certificat_de_residence',
            'autorisation_de_la_societe_de_credit',
            'extrait_de_carte_grise',
            'certificat_de_visite_technique'
        ];

        Schema::table('documents', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (!Schema::hasColumn('documents', $column)) {
                    $table->string($column, 255)->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = [
            'bon_a_enlever',
            'declaration_d3',
            'quittance_douane',
            'visite_technique',
            'chemin_zip',
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
            'piece',
            'type_document',
            'certifica_visite_technique',
            'certificat_de_residence',
            'autorisation_de_la_societe_de_credit',
            'extrait_de_carte_grise',
            'certificat_de_visite_technique'
        ];

        Schema::table('documents', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
