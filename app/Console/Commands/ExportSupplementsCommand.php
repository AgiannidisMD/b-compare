<?php

namespace App\Console\Commands;

use App\Models\Supplement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportSupplementsCommand extends Command
{
    protected $signature = 'supplements:export {--path=database/exports/supplements.json}';
    protected $description = 'Export all supplements to JSON for production import';

    public function handle()
    {
        $path = $this->option('path');
        $dir = dirname($path);

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $this->info('Exporting supplements...');

        $supplements = Supplement::all()->toArray();
        $count = count($supplements);

        // Export in chunks to handle large data
        File::put($path, json_encode($supplements, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $size = round(filesize($path) / 1024 / 1024, 2);

        $this->info("Exported {$count} supplements to {$path} ({$size} MB)");
        $this->info("Commit this file to git for production import.");

        return Command::SUCCESS;
    }
}
