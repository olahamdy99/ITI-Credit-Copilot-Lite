<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_chunks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('policy_document_id')
                ->constrained('policy_documents')
                ->cascadeOnDelete();

            $table->foreignId('policy_edition_id')
                ->nullable()
                ->constrained('policy_editions')
                ->nullOnDelete();

            $table->unsignedInteger('chunk_index');

            $table->string('section_id')->nullable();
            $table->string('section_title')->nullable();
            $table->string('clause_id')->nullable();

            $table->unsignedInteger('page_number')->nullable();

            $table->text('content');

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['policy_document_id', 'chunk_index'],
                'policy_chunks_document_chunk_unique'
            );

            $table->index('policy_edition_id');
            $table->index('section_id');
            $table->index('clause_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_chunks');
    }
};
