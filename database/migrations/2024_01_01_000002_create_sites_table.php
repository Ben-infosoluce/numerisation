<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nom_site', 100);
            $table->string('code_site', 255)->nullable();
            $table->string('type_site', 50);
            $table->string('region', 50)->nullable();
            $table->time('heures_ouverture')->nullable();
            $table->time('heures_fermeture')->nullable();
            $table->integer('statut')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sites');
    }
};