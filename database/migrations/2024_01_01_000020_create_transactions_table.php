<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_dossier')->nullable();
            $table->string('Montant', 255);
            $table->dateTime('date_transaction')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('type_transaction', 50);
            $table->unsignedBigInteger('id_user')->nullable();
            $table->integer('statut')->default(1);
            $table->timestamps();

            $table->foreign('id_dossier')->references('id')->on('dossiers')->onDelete('set null');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};