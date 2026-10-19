<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_group_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('master_profile_id');
            $table->unsignedBigInteger('master_service_id');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('reserved_seats')->default(0);
            $table->string('status', 20)->default('open');
            $table->string('service_name_snapshot', 150);
            $table->unsignedInteger('price_som');
            $table->timestamps();
            $table->foreign('master_profile_id')->references('id')->on('master_profiles')->onDelete('cascade');
            $table->foreign('master_service_id')->references('id')->on('master_services')->onDelete('cascade');
            $table->index(['master_profile_id', 'starts_at', 'ends_at'], 'group_session_master_time_idx');
        });
        Schema::create('master_group_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('master_group_session_id');
            $table->string('client_name', 100);
            $table->string('client_phone', 30);
            $table->string('phone_digits', 20);
            $table->string('status', 20)->default('confirmed');
            $table->timestamps();
            $table->foreign('master_group_session_id')->references('id')->on('master_group_sessions')->onDelete('cascade');
            $table->index(['master_group_session_id', 'status'], 'group_participants_status_idx');
            // Repeat submissions by the same person must not consume another seat; family members may share a phone.
            $table->unique(['master_group_session_id', 'phone_digits', 'client_name'], 'group_participant_unique_person');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_group_participants');
        Schema::dropIfExists('master_group_sessions');
    }
};
