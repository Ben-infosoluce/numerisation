<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_transaction')->nullable();
            $table->dateTime('date_emission')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('Format', 50);
            $table->timestamps();

            $table->foreign('id_transaction')->references('id')->on('transactions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recus');
    }
};