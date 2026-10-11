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
        Schema::create('policy_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('policy_edition_id')
                ->nullable()
                ->constrained('policy_editions')
                ->nullOnDelete();

            $table->foreignId('supersedes_document_id')
                ->nullable()
                ->constrained('policy_documents')
                ->nullOnDelete();

            $table->string('type');
            $table->string('title');

            $table->string('source_file');
            $table->string('format');

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->string('processing_status')
                ->default('pending');

            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_documents');
    }
};
