<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('telegram_scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 100)->unique();
            $table->foreignId('master_appointment_id')->nullable()->constrained('master_appointments')->cascadeOnDelete();
            $table->foreignId('master_profile_id')->nullable()->constrained('master_profiles')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('status', 24)->default('claimed');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['kind', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('telegram_scheduled_notifications'); }
};
