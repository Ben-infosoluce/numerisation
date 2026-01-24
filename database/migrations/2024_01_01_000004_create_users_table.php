<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nom', 100);
            $table->string('prenom', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->unsignedBigInteger('id_role')->nullable();
            $table->unsignedBigInteger('id_site')->nullable();
            $table->integer('statut')->default(1);
            $table->timestamps();

            $table->foreign('id_role')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('id_site')->references('id')->on('sites')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};