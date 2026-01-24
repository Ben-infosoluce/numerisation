<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nom', 100);
            $table->string('prenom', 255);
            $table->string('civilite', 255)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('district_client', 255)->nullable();
            $table->string('prefecture_client', 255)->nullable();
            $table->string('sous_prefecture_client', 255)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('password', 255);
            $table->string('date_naissance', 255)->nullable();
            $table->string('ville_naissance', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->integer('statut')->default(1);
            $table->string('validite', 100)->nullable();
            $table->unsignedInteger('entreprise_id')->nullable();
            $table->timestamps();

            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};