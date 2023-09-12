<?php

namespace Ezegyfa\LaravelHelperMethods\Database;

use Illuminate\Console\Command;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;

class CreateFakeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fake {tableName} {dataCount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DatabaseInfos::getTableInfos(['id'])[$this->argument('tableName')]->createFakeData($this->argument('dataCount'));
        return 0;
    }
}
