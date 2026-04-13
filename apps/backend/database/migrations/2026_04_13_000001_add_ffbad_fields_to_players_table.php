<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('ffbad_license_number')->nullable()->unique()->after('phone');
            $table->date('birth_date')->nullable()->after('ffbad_license_number');
            $table->string('ffbad_category')->nullable()->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropUnique(['ffbad_license_number']);
            $table->dropColumn(['ffbad_license_number', 'birth_date', 'ffbad_category']);
        });
    }
};
