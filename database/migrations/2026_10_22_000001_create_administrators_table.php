<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        // One-time bootstrap hash. Never overwrite credentials during a later deployment.
        DB::table('administrators')->insert([
            'username' => 'admin',
            'password' => '$2y$12$yJ0yFkY.Ei3F/4kypKMEPu5FjLdiIusODrUaorRlzirB8qH1NiTay',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('administrators');
    }
};
