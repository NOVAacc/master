<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('client_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_chat_id', 32)->unique();
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('client_account_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->foreignId('client_account_id')->nullable()->constrained('client_accounts')->nullOnDelete();
            $table->char('client_telegram_browser_claim_hash', 64)->nullable();
            $table->index('client_telegram_browser_claim_hash', 'appointments_browser_claim_idx');
        });
    }
    public function down(): void {
        Schema::table('master_appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_browser_claim_idx');
            $table->dropConstrainedForeignId('client_account_id');
            $table->dropColumn('client_telegram_browser_claim_hash');
        });
        Schema::dropIfExists('client_account_sessions');
        Schema::dropIfExists('client_accounts');
    }
};