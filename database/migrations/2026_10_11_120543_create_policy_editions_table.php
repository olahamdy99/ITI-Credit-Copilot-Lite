<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('policy_editions', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();

            $table->index([
                'effective_from',
                'effective_to',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_editions');
    }
};
