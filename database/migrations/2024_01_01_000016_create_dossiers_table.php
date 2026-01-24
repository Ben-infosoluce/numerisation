<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_vehicule')->nullable();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->unsignedBigInteger('id_client')->nullable();
            $table->unsignedBigInteger('id_service')->nullable();
            $table->integer('id_type_service')->nullable();
            $table->integer('id_site')->nullable();
            $table->json('detail')->nullable();
            $table->string('reserverNumero', 255)->nullable();
            $table->string('numeroDiplomatique', 255)->nullable();
            $table->integer('modification_log_id')->nullable();
            $table->integer('statut')->default(1);
            $table->integer('statut_paiement')->default(1);
            $table->dateTime('date_paiement')->nullable();
            $table->integer('paiement_validated_by')->nullable();
            $table->integer('statut_numerisation')->default(1);
            $table->text('motif_rejet')->nullable();
            $table->integer('dossier_rejeted_by')->nullable();
            $table->dateTime('date_rejet')->nullable();
            $table->string('num_chrono', 255)->nullable();
            $table->string('date_creation', 255)->nullable();
            $table->string('date_validation', 255)->nullable();
            $table->integer('status_pose_plaque')->nullable();
            $table->integer('id_dossier_lier')->nullable();
            $table->timestamps();

            $table->foreign('id_vehicule')->references('id')->on('vehicules')->onDelete('set null');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
            $table->foreign('id_client')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('id_service')->references('id')->on('services')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dossiers');
    }
};