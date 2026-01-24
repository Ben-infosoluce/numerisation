<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('controlleur_caisses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('montant_caisse', 15, 2)->nullable();
            $table->decimal('montant_controlleur', 15, 2)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('status_raf')->default(0);
            $table->unsignedBigInteger('caisse_id')->nullable();
            $table->string('code_caisse', 50)->nullable();
            $table->unsignedBigInteger('id_site')->nullable();
            $table->timestamp('date_fermeture_controlleur')->nullable();
            $table->timestamp('date_ouverture_raf')->nullable();
            $table->timestamp('date_fermeture_raf')->nullable();
            $table->timestamp('date_ouverture_controlleur')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('controlleur_caisses');
    }
};