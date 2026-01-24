<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_dossier');
            $table->unsignedInteger('caisse_ouverture_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('id_site')->nullable();
            $table->integer('caisse_id')->nullable();
            $table->integer('id_service')->nullable();
            $table->integer('id_vehicule')->nullable();
            $table->string('reference', 50);
            $table->decimal('montant', 10, 2);
            $table->string('mode_paiement', 100);
            $table->json('description')->nullable();
            $table->timestamps();

            $table->foreign('caisse_ouverture_id')->references('id')->on('caisse_ouvertures')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paiements');
    }
};