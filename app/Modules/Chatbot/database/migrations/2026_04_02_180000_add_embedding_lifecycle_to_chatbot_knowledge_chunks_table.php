<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_knowledge_chunks', function (Blueprint $table): void {
            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_status')) {
                $table->string('embedding_status')->default('pending')->after('metadata');
            }

            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_provider')) {
                $table->string('embedding_provider')->nullable()->after('embedding_status');
            }

            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_model')) {
                $table->string('embedding_model')->nullable()->after('embedding_provider');
            }

            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_source_hash')) {
                $table->string('embedding_source_hash', 64)->nullable()->after('embedding_model');
            }

            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_generated_at')) {
                $table->timestamp('embedding_generated_at')->nullable()->after('embedding_source_hash');
            }

            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_dimensions')) {
                $table->unsignedSmallInteger('embedding_dimensions')->nullable()->after('embedding_generated_at');
            }

            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_error')) {
                $table->text('embedding_error')->nullable()->after('embedding_dimensions');
            }

            if (!Schema::hasColumn('chatbot_knowledge_chunks', 'embedding_metadata')) {
                $table->json('embedding_metadata')->nullable()->after('embedding_error');
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

            if (!$this->postgresColumnExists('chatbot_knowledge_chunks', 'embedding')) {
                DB::statement('ALTER TABLE chatbot_knowledge_chunks ADD COLUMN embedding vector(1536)');
            }
        }

        Schema::table('chatbot_knowledge_chunks', function (Blueprint $table): void {
            $table->index(['chatbot_account_id', 'embedding_status'], 'chatbot_chunks_account_embedding_status_idx');
            $table->index('embedding_source_hash', 'chatbot_chunks_embedding_hash_idx');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql' && $this->postgresColumnExists('chatbot_knowledge_chunks', 'embedding')) {
            DB::statement('ALTER TABLE chatbot_knowledge_chunks DROP COLUMN embedding');
        }

        Schema::table('chatbot_knowledge_chunks', function (Blueprint $table): void {
            $table->dropIndex('chatbot_chunks_account_embedding_status_idx');
            $table->dropIndex('chatbot_chunks_embedding_hash_idx');

            foreach ([
                'embedding_metadata',
                'embedding_error',
                'embedding_dimensions',
                'embedding_generated_at',
                'embedding_source_hash',
                'embedding_model',
                'embedding_provider',
                'embedding_status',
            ] as $column) {
                if (Schema::hasColumn('chatbot_knowledge_chunks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function postgresColumnExists(string $table, string $column): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'public')
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }
};
