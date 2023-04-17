<?php

namespace Ezegyfa\LaravelHelperMethods\ServerCommands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class GitPull extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git-pull';

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
        $res = $client->get('http://dynamic-web-consulting.com/git-pull?password=' . ServerCommandMethods::getPassword());
        echo $res->getStatusCode(); // 200
        echo $res->getBody();
        return 0;
    }
}
