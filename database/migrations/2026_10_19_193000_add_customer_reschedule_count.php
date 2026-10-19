<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->unsignedTinyInteger('customer_reschedule_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->dropColumn('customer_reschedule_count');
        });
    }
};
