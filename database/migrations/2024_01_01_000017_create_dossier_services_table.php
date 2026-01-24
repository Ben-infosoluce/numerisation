<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dossier_services', function (Blueprint $table) {
            $table->unsignedBigInteger('id_dossier');
            $table->unsignedBigInteger('id_service');

            $table->foreign('id_dossier')->references('id')->on('dossiers')->onDelete('cascade');
            $table->foreign('id_service')->references('id')->on('services')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dossier_services');
    }
};