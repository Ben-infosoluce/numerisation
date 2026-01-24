<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rejets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('motif', 255);
            $table->string('service_id', 100);
            $table->string('id_type_services', 100);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rejets');
    }
};