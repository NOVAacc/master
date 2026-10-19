<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->string('industry', 40)->nullable();
            $table->string('activity', 40)->nullable();
            $table->index(['industry', 'activity']);
        });
        $groups = [
            ['beauty', 'salon', ['demo-alina-makeup', 'demo-sofia-style', 'demo-camila-hair']],
            ['beauty', 'barber', ['demo-azamat-barber']],
            ['beauty', 'cosmetology', ['demo-aida-skincare']],
            ['beauty', 'nails', ['demo-diana-nails']],
            ['beauty', 'brows-lashes', ['demo-nurai-brows', 'aizada']],
            ['beauty', 'waxing', ['demo-elina-wax']],
            ['medicine', 'massage', ['demo-timur-massage']],
        ];
        foreach ($groups as [$industry, $activity, $slugs]) {
            DB::table('master_profiles')->whereIn('slug', $slugs)->update(compact('industry', 'activity'));
        }
    }

    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropIndex(['industry', 'activity']);
            $table->dropColumn(['industry', 'activity']);
        });
    }
};
