<?php

namespace Modules\FasterSearch\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class PerformIndexing extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:fastersearch-perform-indexing';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Index new, updated and deleted threads.';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \Adldap\Models\ModelNotFoundException
     */
    public function handle()
    {
        \FasterSearch::performIndexing();
    }
}
