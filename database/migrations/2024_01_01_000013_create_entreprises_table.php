<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nom_entreprise', 255);
            $table->string('nom_representant_legal', 255);
            $table->string('prenom_representant_legal', 255)->nullable();
            $table->string('date_de_naissance_representant_legal', 255)->nullable();
            $table->string('profession_representant_legal', 255)->nullable();
            $table->string('telephone_representant_legal', 20)->nullable();
            $table->string('registre_commerce', 255);
            $table->string('compte_contribuale', 255);
            $table->string('prefecture', 255)->nullable();
            $table->string('sous_prefecture', 255)->nullable();
            $table->string('region', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('entreprises');
    }
};