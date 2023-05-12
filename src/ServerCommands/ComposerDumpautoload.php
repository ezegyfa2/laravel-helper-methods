<?php

namespace Ezegyfa\LaravelHelperMethods\ServerCommands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class ComposerDumpautoload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'composer-dumpautoload';

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
        $client = new Client();
        $res = $client->get('http://dynamic-web-consulting.com/composer-dumpautoload?password=' . ServerCommandMethods::getPassword());
        echo $res->getStatusCode(); // 200
        echo $res->getBody();
        return 0;
    }
}
