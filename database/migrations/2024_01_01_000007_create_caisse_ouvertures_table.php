<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('caisse_ouvertures', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('caisse_id');
            $table->dateTime('date_ouverture');
            $table->dateTime('date_fermeture')->nullable();
            $table->decimal('montant_ouverture', 10, 2)->nullable();
            $table->decimal('montant_fermeture', 10, 2)->nullable();
            $table->string('montant_saisie_caisse', 255)->nullable();
            $table->enum('statut', ['ouvert', 'fermé'])->default('ouvert');
            $table->integer('id_controlleur')->nullable();
            $table->string('montant_controlleur', 255)->nullable();
            $table->date('date_fermeture_controlleur')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('caisse_id')->references('id')->on('caisses')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('caisse_ouvertures');
    }
};