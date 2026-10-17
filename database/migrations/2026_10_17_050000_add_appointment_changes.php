<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedInteger('notification_revision')->default(1);
        });
        Schema::table('master_notifications', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1);
            $table->unique(['master_appointment_id', 'recipient', 'revision'], 'master_notification_revision_unique');
            $table->dropUnique(['master_appointment_id', 'recipient']);
        });
        Schema::create('master_appointment_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('before');
            $table->json('after');
            $table->string('reason', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_appointment_changes');
        DB::table('master_notifications')->where('revision', '>', 1)->delete();
        Schema::table('master_notifications', function (Blueprint $table) {
            $table->unique(['master_appointment_id', 'recipient']);
            $table->dropUnique('master_notification_revision_unique');
            $table->dropColumn('revision');
        });
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->dropColumn(['revision', 'notification_revision']);
        });
    }
};
