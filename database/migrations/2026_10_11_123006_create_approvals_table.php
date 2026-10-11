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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_id')
                ->constrained('assessments')
                ->cascadeOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('decision')->default('pending');

            $table->text('comment')->nullable();

            $table->timestamp('decided_at')->nullable();

            $table->decimal('authority_limit', 15, 2)->nullable();

            $table->timestamps();

            $table->index([
                'assessment_id',
                'decision',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
