<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('type_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nom_type_service', 100);
            $table->string('montant', 255)->default('0');
            $table->unsignedBigInteger('id_service')->nullable();
            $table->timestamps();

            $table->foreign('id_service')->references('id')->on('services')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('type_services');
    }
};