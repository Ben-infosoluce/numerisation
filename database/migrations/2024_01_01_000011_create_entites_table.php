<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('entites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nom', 255);
            $table->integer('statut')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('entites');
    }
};