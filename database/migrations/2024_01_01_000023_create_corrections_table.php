<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('corrections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dossier_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('origine', 50);
            $table->integer('status')->nullable();
            $table->longText('corrections');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('dossier_id')->references('id')->on('dossiers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('corrections');
    }
};