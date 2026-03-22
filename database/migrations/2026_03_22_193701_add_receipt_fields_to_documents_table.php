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
        Schema::table('documents', function (Blueprint $table) {
            $table->string('recepisse_depot', 255)->nullable();
            $table->string('recu_dgttc', 255)->nullable();
            $table->string('recu_emuci', 255)->nullable();
            $table->string('recu_quipux', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'recepisse_depot',
                'recu_dgttc',
                'recu_emuci',
                'recu_quipux'
            ]);
        });
    }
};
