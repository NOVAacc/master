<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->json('services_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->dropColumn('services_snapshot');
        });
    }
};
