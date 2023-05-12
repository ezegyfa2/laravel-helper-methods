<?php

namespace Ezegyfa\LaravelHelperMethods;

use Illuminate\Support\Facades\Route;
use stdClass;

class DesignerMethods
{
    public static function registerDesignerRoute() {
        Route::post('/designer', function($request) {
            $request->validate([
                'template_name' => 'required|string',
            ]);
            $templateName = $request->get('template_name');
            file_put_contents(
                static::getTemplatePath($templateName), 
                $request->get('template'), 
                JSON_PRETTY_PRINT
            );
        });
        /*Route::post('/designer-with-translate', function() use($templatePath) {
            $translateTemplatePath = base_path('node_modules/web-page-designer-vue-components/src/Templates/Designed/designed.js');
            file_put_contents($translateTemplatePath, 'export default ' . request()->input()['designedTemplate']);
            file_put_contents($templatePath, request()->input()['designedTemplate'], JSON_PRETTY_PRINT);
            shell_exec('npm run dev');
        });*/
        Route::get('/designer', function() {
            $template = static::getBaseTemplateObject();
            $template->data->saved_templates = static::getSavedTemplates();
            $templateName = request()->get('template-name');
            if ($templateName) {
                $loadedTemplate = json_decode(file_get_contents(static::getTemplatePath($templateName)));
                array_push($template->data->template->data->base_item_sections, $loadedTemplate);
            }
            return view('layouts.dynamicPage', compact('template'));
        });
        Route::get('designer/template', function() {
            $templateName = request()->get('template-name');
            if ($templateName) {
                $template = static::getTemplate($templateName);
                return response()->json($template);
            }
            else {
                return response()->json(static::getSavedTemplates());
            }
        });
        Route::post('designer/template', function() {
            //CheckingMethods::checkConfigValueIsSet('app.node_modules_folder_path');
            //CheckingMethods::checkConfigValueIsSet('app.designer_node_module_name');
            if (request()->has('template') && request()->has('template_name')) {
                $templateContent = json_encode(json_decode(request()->input()['template']), JSON_PRETTY_PRINT);
                $templateName = request()->input()['template_name'];
                $componentFolderPath = static::getTemplatesFolderPath();
                FolderMethods::createIfNotExists($componentFolderPath);
                file_put_contents(FolderMethods::combinePaths($componentFolderPath, $templateName . '.json'), $templateContent);
                return response()->json([
                    'message' => 'success',
                ]);
            }
            else {
                return response()->json([
                    'message' => 'Template is required',
                ]);
            }
        });
        Route::get('/designer/result', function () {
            $templateName = request()->get('template-name');
            if ($templateName) {
                $templatePath = static::getTemplatePath($templateName);
                if (file_exists($templatePath)) {
                    $template = file_get_contents($templatePath);
                }
                else {
                    $template = new stdClass;
                }
            }
            else {
                $template = new stdClass;
            }
            return view('layouts.dynamicPage', compact('template'));
        });
        /*Route::get('/translated-designer-result', function () use($templatePath) {
            $templateParams = DynamicTemplateMethods::getTranslatedTemplateParamsFromFile($templatePath);
            return DynamicTemplateMethods::getTemplateDynamicPage('web-designer-designed-page', $templateParams);
        });
        Route::post('/designer/copy-component', function() {
            try {
                $componentType = request()->input()['componentType'];
                $vueComponentFolderPaths = json_decode(file_get_contents(getTemplatePath('componentFolderPaths.json')));
                $componentFolderPath = $vueComponentFolderPaths[$componentType];
                $componentModuleName = explode('/', $componentFolderPath)[0];
                $componentFolderPath = FolderMethods::combinePaths(
                    config('app.node_modules_folder_path'), 
                    'Vue',
                    'src',
                    $componentFolderPath
                );
                $newComponentFolderPath = str_replace(
                    $componentModuleName, 
                    config('app.vue_components_module_name'), 
                    $componentFolderPath
                );
                FolderMethods::copyFolder($componentFolderPath, $newComponentFolderPath);

                return response()->json([
                    'message' => 'success',
                ]);
            }
            catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ]);
            }
        });
        Route::post('/designer/refresh-vue-component-folder-paths', function () {
            try {
                $componentFolderPaths = request()->input()['componentFolderPaths'];
                file_put_contents(getTemplatePath('componentFolderPaths.json'), json_encode($componentFolderPaths));
    
                return response()->json([
                    'message' => 'Success',
                ]);
            }
            catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ]);
            }
        });*/
    }

    public static function getBaseTemplateObject() {
        return (object) [
            'type' => 'web-designer-page-designer',
            'data' => (object) [
                'template' => (object) [
                    'type' => 'web-designer-base-array',
                    'data' => (object) [
                        'base_item_sections' => []
                    ],
                ]
            ],
        ];
    }

    public static function getSavedTemplates() {
        $savedTemplatePaths = FolderMethods::getFolderFilePathsRecoursively(static::getTemplatesFolderPath());
        $savedTemplates = [];
        foreach ($savedTemplatePaths as $savedTemplatePath) {
            $fileName = pathinfo($savedTemplatePath, PATHINFO_FILENAME);
            $templateContent = str_replace('export default ', '', file_get_contents($savedTemplatePath));
            $savedTemplates[$fileName] = json_decode($templateContent);
        }
        return $savedTemplates;
    }

    public static function getTemplate($templateName) {
        $templatePath = static::getTemplatePath($templateName);
        $templateText = file_get_contents($templatePath);
        if ($templateText) {
            return json_decode($templateText);
        }
        else {
            return null;
        }
    }

    public static function getTemplatesFolderPath() {
        /*return FolderMethods::combinePaths(
            config('app.node_modules_folder_path'), 
            'Vue',
            config('app.designer_node_module_name'), 
            'src',
            'Templates'
        );*/
        return base_path('app/Templates');
    }

    public static function getTemplatePath(string $templateName) {
        return base_path('app/Templates/' . $templateName . '.json');
    }
}