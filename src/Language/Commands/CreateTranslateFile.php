<?php

namespace Ezegyfa\LaravelHelperMethods\Language\Commands;

use Ezegyfa\LaravelHelperMethods\FolderMethods;
use Illuminate\Console\Command;
use Symfony\Component\VarExporter\VarExporter;

class CreateTranslateFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $replacedValueCount;

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
    public function handle() {
        $folderPath = config('app.translation_folder_path');
        $filePathToTranslate = FolderMethods::combinePaths($folderPath, "texts.php");
        $translateObject = require($filePathToTranslate);
        $this->replacedValueCount = 0;
        $translatedObject = $this->replaceTextsToTranslate($translateObject, $this->getTranslateValues($filePathToTranslate, $folderPath));

        $translatedFileName = pathinfo($filePathToTranslate)['filename'] . '_translated.' . pathinfo($filePathToTranslate)['extension'];
        file_put_contents($folderPath . '/' . $translatedFileName, "<?php\n\nreturn " . VarExporter::export($translatedObject) . ';');
    }

    public function getTranslateValues($filePathToTranslate, $folderPath) {
        $translateValuesFileName = pathinfo($filePathToTranslate)['filename'] . '_translated_values.json';
        return json_decode(file_get_contents(FolderMethods::combinePaths($folderPath, $translateValuesFileName)));
    }

    public function replaceTextsToTranslate($object, $translatedTexts) {
        if (is_array($object)) {
            $arrayValues = [];
            foreach ($object as $key => $value) {
                $arrayValues[$key] = static::replaceTextsToTranslate($value, $translatedTexts);
            }
            return $arrayValues;
        }
        else if (gettype($object) == 'object') {
            $replaceObject = new \stdClass();
            foreach (array_keys(get_object_vars($object)) as $key) {
                $replaceObject->$key = static::replaceTextsToTranslate($object->$key, $translatedTexts);
            }
            return $replaceObject;
        }
        else {
            return $translatedTexts[$this->replacedValueCount++];
        }
    }
}
