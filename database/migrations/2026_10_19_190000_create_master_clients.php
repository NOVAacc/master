<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('master_profile_id');
            $table->string('phone_digits', 20);
            $table->string('name', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['master_profile_id', 'phone_digits'], 'master_clients_master_phone_unique');
            $table->foreign('master_profile_id')->references('id')->on('master_profiles')->onDelete('cascade');
        });

        Schema::table('master_appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('master_client_id')->nullable()->after('master_profile_id');
            $table->index(['master_profile_id', 'master_client_id'], 'appointments_master_client_idx');
            $table->foreign('master_client_id')->references('id')->on('master_clients')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->dropForeign(['master_client_id']);
            $table->dropIndex('appointments_master_client_idx');
            $table->dropColumn('master_client_id');
        });
        Schema::dropIfExists('master_clients');
    }
};
