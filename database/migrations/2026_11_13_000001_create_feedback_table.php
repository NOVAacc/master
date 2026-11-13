<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20);
            $table->text('message');
            $table->string('page', 255)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('feedback'); }
};
