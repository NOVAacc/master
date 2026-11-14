<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->string('client_telegram_chat_id', 32)->nullable();
            $table->string('client_telegram_link_hash', 64)->nullable();
            $table->timestamp('client_telegram_link_expires_at')->nullable();
            $table->index('client_telegram_link_hash');
        });
        Schema::create('client_booking_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_appointment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('kind', 32);
            $table->text('message');
            $table->string('delivery_status', 32)->default('pending_manual');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('manual_at')->nullable();
            $table->timestamps();
            $table->unique(['master_appointment_id', 'revision', 'kind'], 'client_booking_alert_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('client_booking_alerts');
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->dropIndex(['client_telegram_link_hash']);
            $table->dropColumn(['client_telegram_chat_id', 'client_telegram_link_hash', 'client_telegram_link_expires_at']);
        });
    }
};