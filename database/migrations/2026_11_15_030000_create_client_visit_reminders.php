<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_visit_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_appointment_id')->constrained('master_appointments')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->dateTime('visit_starts_at');
            $table->string('status', 24)->default('claimed');
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['master_appointment_id', 'revision'], 'client_reminder_once_per_revision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_visit_reminders');
    }
};
