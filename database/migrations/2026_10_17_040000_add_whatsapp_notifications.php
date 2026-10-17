<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', fn (Blueprint $table) => $table->boolean('whatsapp_notifications')->default(false));
        Schema::table('master_appointments', fn (Blueprint $table) => $table->boolean('whatsapp_consent')->default(false));
        Schema::create('master_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_appointment_id')->constrained()->cascadeOnDelete();
            $table->string('recipient', 10);
            $table->string('phone', 20);
            $table->string('status', 30)->default('not_configured');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_id')->nullable();
            $table->string('error_code', 40)->nullable();
            $table->timestamps();
            $table->unique(['master_appointment_id', 'recipient']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_notifications');
        Schema::table('master_appointments', fn (Blueprint $table) => $table->dropColumn('whatsapp_consent'));
        Schema::table('master_profiles', fn (Blueprint $table) => $table->dropColumn('whatsapp_notifications'));
    }
};
