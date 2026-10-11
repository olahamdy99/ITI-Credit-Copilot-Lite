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
        Schema::create('application_extractions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_application_id')
                ->constrained('loan_applications')
                ->cascadeOnDelete();

            $table->decimal('net_monthly_income', 15, 2)->nullable();
            $table->decimal('existing_monthly_obligations', 15, 2)->nullable();
            $table->date('employment_start_date')->nullable();
            $table->unsignedInteger('bureau_score')->nullable();

            $table->json('evidence')->nullable();

            $table->string('status')->default('pending');
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->unique('loan_application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_extractions');
    }
};
