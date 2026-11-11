<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hot_slot_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_service_id')->constrained('master_services')->cascadeOnDelete();
            $table->char('token', 40)->unique();
            $table->unsignedInteger('special_price_som')->nullable();
            $table->string('story_template', 50)->default('white');
            $table->unsignedInteger('clicks')->default(0);
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['master_profile_id', 'cancelled_at']);
        });

        Schema::create('hot_slot_offer_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hot_slot_offer_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('master_appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['hot_slot_offer_id', 'starts_at'], 'hot_offer_slot_unique');
            $table->index(['starts_at', 'master_appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hot_slot_offer_slots');
        Schema::dropIfExists('hot_slot_offers');
    }
};
