<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('genre', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nom', 100);
            $table->integer('nb_plaque');
        });
    }

    public function down()
    {
        Schema::dropIfExists('genre');
    }
};