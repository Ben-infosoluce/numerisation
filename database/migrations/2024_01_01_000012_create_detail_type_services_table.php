<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('detail_type_services', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('id_type_services');
            $table->unsignedBigInteger('id_service');
            $table->unsignedBigInteger('id_site');
            $table->unsignedBigInteger('id_entite')->nullable();
            $table->string('element_facturation', 255);
            $table->bigInteger('montant');
            $table->integer('statut')->nullable()->comment('1: Activé; 2 : Désactivé');
            $table->timestamps();

            $table->foreign('id_type_services')
                ->references('id')
                ->on('type_services')
                ->onDelete('cascade');

            $table->foreign('id_service')
                ->references('id')
                ->on('services')
                ->onDelete('cascade');

            $table->foreign('id_site')
                ->references('id')
                ->on('sites')
                ->onDelete('cascade');

            $table->foreign('id_entite')
                ->references('id')
                ->on('entites')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_type_services');
    }
};
