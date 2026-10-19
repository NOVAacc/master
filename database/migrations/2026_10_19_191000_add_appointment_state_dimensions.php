<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            // Null represents an older record whose historical state has not
            // been separately classified; do not presume an unpaid balance.
            $table->string('attendance_status', 24)->nullable();
            $table->string('payment_status', 24)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->dropColumn(['attendance_status', 'payment_status']);
        });
    }
};
