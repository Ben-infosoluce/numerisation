<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('Nom_Permission', 50);
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('type_service_id');
            $table->timestamps();

            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->onDelete('cascade');

            $table->foreign('type_service_id')
                ->references('id')
                ->on('type_services')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};
