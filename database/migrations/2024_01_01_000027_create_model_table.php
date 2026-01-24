<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('model', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nom', 100);
            $table->unsignedInteger('marque_id');
            $table->timestamps();

            $table->foreign('marque_id')->references('id')->on('marque')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('model');
    }
};