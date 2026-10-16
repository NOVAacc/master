<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // ISO 1=Monday ... 7=Sunday
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
            $table->index(['master_profile_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_working_hours');
    }
};
