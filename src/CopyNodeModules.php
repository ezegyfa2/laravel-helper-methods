<?php

namespace Ezegyfa\LaravelHelperMethods;

use Illuminate\Console\Command;

class CopyNodeModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cnm {--nonpm}';

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
        $this->copyNodeModules('D:\Projektek\Sajat\Sablonok\Node modulok');
        $this->copyModule('D:\Projektek\Sajat\laravel-helper-methods', 'vendor\ezegyfa');
        return 0;
    }

    public function copyNodeModules($nodeRootFolderPath) {
        $vueFolderPath = $nodeRootFolderPath . '\Vue';
        $this->copyModules($vueFolderPath, 'node_modules');
        $this->copyModule($nodeRootFolderPath . '\js-helper-methods', 'node_modules');
        if (!$this->option('nonpm')) {
            shell_exec('npm run dev');
        }
    }

    public function copyModules(string $sourceFolderPath, string $moduleFolderName) {
        $subFolderNames = FolderMethods::getFolderSubFolders($sourceFolderPath);
        foreach ($subFolderNames as $subFolderName) {
            $this->copyModule($sourceFolderPath . '\\' . $subFolderName, 'node_modules');
        }
    }

    public function copyModule(string $modulePath, string $moduleFolderName) {
        $targetNodeRootFolderPath = base_path() . '\\' . $moduleFolderName;
        $nodeFolder = basename($modulePath);
        $targetNodeFolderPath = $targetNodeRootFolderPath . '\\' . $nodeFolder;
        if (file_exists($targetNodeFolderPath)) {
            FolderMethods::deleteFolder($targetNodeFolderPath);
        }
        FolderMethods::copyFolder($modulePath, $targetNodeFolderPath, [ '.git', '.gitignore' ]);
        $gitFilePath = $targetNodeFolderPath . '\\.git';
        if (file_exists($gitFilePath)) {
            FolderMethods::deleteFolder($gitFilePath);
        }
        $gitignoreFilePath = $targetNodeFolderPath . '\\.gitignore';
        if (file_exists($gitignoreFilePath)) {
            chmod($gitignoreFilePath, 0777);
            unlink($gitignoreFilePath);
        }
    }
}
