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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();

            $table->string('reference_code')->unique();

            $table->decimal('requested_amount', 15, 2);
            $table->unsignedInteger('requested_tenor_months');

            $table->date('date_of_birth');
            $table->date('application_date');

            $table->string('status')->default('submitted');

            $table->foreignId('policy_edition_id')
                ->nullable()
                ->constrained('policy_editions')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('application_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
