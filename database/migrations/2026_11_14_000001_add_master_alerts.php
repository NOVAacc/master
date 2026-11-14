<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('master_profiles', function(Blueprint $t) {
   $t->string('telegram_chat_id',32)->nullable();
   $t->string('telegram_link_hash',64)->nullable();
   $t->timestamp('telegram_link_expires_at')->nullable();
   $t->boolean('telegram_alerts_enabled')->default(true);
  });
  Schema::create('master_alerts', function(Blueprint $t) {
   $t->id();
   $t->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
   $t->foreignId('master_appointment_id')->nullable()->constrained()->nullOnDelete();
   $t->string('event_key',128)->unique();
   $t->string('kind',32); $t->string('title',120); $t->text('message');
   $t->timestamp('read_at')->nullable(); $t->timestamps();
   $t->index(['master_profile_id','created_at']);
  });
 }
 public function down(): void {
  Schema::dropIfExists('master_alerts');
  Schema::table('master_profiles',fn(Blueprint $t)=>$t->dropColumn(['telegram_chat_id','telegram_link_hash','telegram_link_expires_at','telegram_alerts_enabled']));
 }
};