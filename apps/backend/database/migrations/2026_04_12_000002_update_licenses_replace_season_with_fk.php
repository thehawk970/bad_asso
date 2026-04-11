<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            // Ajout de la FK nullable (pour ne pas bloquer si des licences existent)
            $table->foreignId('season_id')
                ->nullable()
                ->after('player_id')
                ->constrained()
                ->nullOnDelete();

            // Suppression de l'ancienne colonne string
            $table->dropColumn('season');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
            $table->string('season', 20)->after('player_id');
        });
    }
};
