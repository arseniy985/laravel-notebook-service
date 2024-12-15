<?php

namespace App\Console\Commands;

use App\Models\Note;
use Illuminate\Console\Command;

class CheckAllNotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-all-notes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        echo Note::all();
    }
}
