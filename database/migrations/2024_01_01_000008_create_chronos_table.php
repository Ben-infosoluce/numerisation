<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chronos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service', 10)->nullable();
            $table->char('year', 2)->nullable();
            $table->char('month', 2)->nullable();
            $table->integer('counter')->default(0);

            $table->unique(['service', 'year', 'month']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chronos');
    }
};