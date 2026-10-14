<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupDemo extends Command
{
    protected $signature = 'demo:setup';

    protected $description = 'Prepare the complete demo environment';

    public function handle(): int
    {
        $this->info('Starting demo setup...');

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            $this->error('Migration failed.');

            return self::FAILURE;
        }

        if ($this->call('db:seed', ['--force' => true]) !== self::SUCCESS) {
            $this->error('Database seeding failed.');

            return self::FAILURE;
        }

        if ($this->call('demo:import-corpus') !== self::SUCCESS) {
            $this->error('Demo corpus import failed.');

            return self::FAILURE;
        }

        if ($this->call('policy:ingest') !== self::SUCCESS) {
            $this->error('Policy ingestion failed.');

            return self::FAILURE;
        }

        if ($this->call('policy:embed') !== self::SUCCESS) {
            $this->error('Policy embedding failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Demo setup completed successfully.');

        return self::SUCCESS;
    }
}
