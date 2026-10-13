<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_extractions', function (Blueprint $table) {
            $table->string('pricing_segment')
                ->nullable()
                ->after('bureau_score');
        });
    }

    public function down(): void
    {
        Schema::table('application_extractions', function (Blueprint $table) {
            $table->dropColumn('pricing_segment');
        });
    }
};