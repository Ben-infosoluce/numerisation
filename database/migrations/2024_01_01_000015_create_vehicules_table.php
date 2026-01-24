<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('annee_production', 100)->nullable();
            $table->string('vin', 50)->unique();
            $table->string('marque', 50)->nullable();
            $table->string('modele', 50)->nullable();
            $table->string('couleur', 255)->nullable();
            $table->string('source_energie', 255)->nullable();
            $table->string('genre_vehicule', 255)->nullable();
            $table->string('poids_total_charge', 255)->nullable();
            $table->string('poids_utile', 255)->nullable();
            $table->string('poids_vide', 255)->nullable();
            $table->string('puissance_administrative', 255)->nullable();
            $table->integer('places_assises')->nullable();
            $table->integer('nombre_essieux')->nullable();
            $table->string('date_mise_circulation', 255)->nullable();
            $table->unsignedBigInteger('id_site')->nullable();
            $table->string('num_immatriculation', 50)->nullable();
            $table->string('num_immatriculation_precedant', 255)->nullable();
            $table->date('date_immatriculation')->nullable();
            $table->date('date_immatriculation_precedant')->nullable();
            $table->string('nb_plaque', 100)->nullable();
            $table->string('carrosserie', 100)->nullable();
            $table->string('type_technique', 100)->nullable();
            $table->string('controleur_technique', 255)->nullable();
            $table->string('usage_vehicule', 100)->nullable();
            $table->string('code_de_region', 225)->nullable();
            $table->string('physique_morale', 225)->nullable();
            $table->unsignedInteger('entreprise_id')->nullable();
            $table->unsignedBigInteger('id_client')->nullable();
            $table->unsignedInteger('model_id')->nullable();
            $table->unsignedInteger('marque_id')->nullable();
            $table->string('cylindree', 255)->nullable();
            $table->string('gage', 20)->nullable();
            $table->timestamps();

            $table->foreign('id_site')->references('id')->on('sites')->onDelete('set null');
            $table->foreign('id_client')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicules');
    }
};