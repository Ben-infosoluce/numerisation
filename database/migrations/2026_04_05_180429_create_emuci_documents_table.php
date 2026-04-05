<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emuci_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_dossier')->index();
            $table->string('rti')->nullable();
            $table->string('rcil')->nullable();
            $table->string('pir')->nullable();
            $table->string('dcg')->nullable();
            $table->string('d3')->nullable();
            $table->string('cvt')->nullable();
            $table->string('cmc')->nullable();
            $table->timestamps();

            $table->foreign('id_dossier')->references('id')->on('dossiers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emuci_documents');
    }
};
