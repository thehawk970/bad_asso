<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->boolean('payment_confirmed')->default(false)->after('status');
            $table->boolean('health_form_filled')->default(false)->after('payment_confirmed');
            $table->boolean('info_form_filled')->default(false)->after('health_form_filled');
            $table->boolean('rules_signed')->default(false)->after('info_form_filled');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['payment_confirmed', 'health_form_filled', 'info_form_filled', 'rules_signed']);
        });
    }
};
