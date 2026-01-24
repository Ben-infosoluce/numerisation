<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_dossier')->nullable();
            $table->string('formulaire_recensement', 255)->nullable();
            $table->string('fiche_rti', 255)->nullable();
            $table->string('fiche_civio', 255)->nullable();
            $table->string('cni', 255)->nullable();
            $table->string('carte_professionnelle', 255)->nullable();
            $table->string('permis_conduire', 255)->nullable();
            $table->string('documents_douane', 255)->nullable();
            $table->string('fiche_demande_carte_grise', 255)->nullable();
            $table->string('assurance', 255)->nullable();
            $table->timestamps();

            $table->foreign('id_dossier')->references('id')->on('dossiers')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};