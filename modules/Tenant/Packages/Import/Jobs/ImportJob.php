<?php

namespace Modules\Tenant\Packages\Import\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Tenant\Models\Import;
use Modules\Tenant\Packages\Import\Repositories\ImportRepository;

class ImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Import $import;
    protected string $key;

    public function __construct(Import $import, string $key)
    {
        $this->import = $import;
        $this->key = $key;
    }

    public function handle(): void
    {
        try {
            ImportRepository::executeJob($this->import, $this->key);
        } catch (Exception $e) {
            logger($e->getMessage());
        }
    }
}
