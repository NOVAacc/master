<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_reminders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('master_appointment_id');
            $table->unsignedInteger('notification_revision');
            $table->string('channel', 24)->default('whatsapp');
            $table->string('recipient', 24)->default('client');
            $table->string('phone', 20);
            $table->dateTime('due_at');
            $table->string('status', 24)->default('scheduled');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('provider_id', 255)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamps();
            $table->unique(
                ['master_appointment_id', 'notification_revision', 'channel', 'recipient'],
                'master_reminder_once_per_revision'
            );
            $table->index(['status', 'due_at'], 'master_reminder_dispatch_idx');
            $table->foreign('master_appointment_id')
                ->references('id')->on('master_appointments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_reminders');
    }
};
