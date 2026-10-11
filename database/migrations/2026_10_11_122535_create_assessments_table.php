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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_application_id')
                ->constrained('loan_applications')
                ->cascadeOnDelete();

            $table->foreignId('policy_edition_id')
                ->constrained('policy_editions');

            $table->decimal('annual_rate_percent', 8, 4)->nullable();
            $table->decimal('monthly_installment', 15, 2)->nullable();
            $table->decimal('debt_burden_ratio', 10, 6)->nullable();
            $table->decimal('maximum_eligible_amount', 15, 2)->nullable();

            $table->string('recommendation')->nullable();
            $table->decimal('recommended_amount', 15, 2)->nullable();
            $table->string('approval_required_from')->nullable();

            $table->string('status')->default('processing');

            $table->uuid('run_id')->unique();

            $table->timestamps();

            $table->index('status');
            $table->index('recommendation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
