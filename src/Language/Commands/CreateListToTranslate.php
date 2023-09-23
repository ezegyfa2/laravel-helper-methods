<?php

namespace Ezegyfa\LaravelHelperMethods\Language\Commands;

use Ezegyfa\LaravelHelperMethods\FolderMethods;
use Illuminate\Console\Command;
use Symfony\Component\VarExporter\VarExporter;

class CreateListToTranslate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctl';

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
        $folderPath = config('app.translation_folder_path');
        $filePathToTranslate = FolderMethods::combinePaths($folderPath, "texts.php");
        $textsToTranslate = $this->getTextsToTranslate($filePathToTranslate);
        $translateValuesFileName = pathinfo($filePathToTranslate)['filename'] . '_values.json';
        file_put_contents($folderPath . '/' . $translateValuesFileName, json_encode($textsToTranslate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function getTextsToTranslate($filePath) {
        $object = require($filePath);
        $values = [];
        $this->collectTextsToTranslate($object, $values);
        return $values;
    }

    public static function collectTextsToTranslate($object, &$collectedTexts) {
        if (is_array($object)) {
            foreach ($object as $objectValue) {
                static::collectTextsToTranslate($objectValue, $collectedTexts);
            }
        }
        else if (gettype($object) == 'object') {
            foreach (array_keys(get_object_vars($object)) as $key) {
                static::collectTextsToTranslate($object->$key, $collectedTexts);
            }
        }
        else {
            $collectedTexts[] = $object;
        }
    }
}
