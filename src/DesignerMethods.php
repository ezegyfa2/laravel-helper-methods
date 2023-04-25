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
            \Log::debug($templateName);
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
            $templateName = request()->get('template-name');
            $template = (object) [
                'type' => 'web-designer-page-designer',
                'data' => new stdClass,
            ];
            if ($templateName) {
                $template->data->template = json_decode(file_get_contents(static::getTemplatePath($templateName)));
                $template->data->saved_template_names = array_map(function ($fileName) {
                    return pathinfo($fileName, PATHINFO_FILENAME);
                }, FolderMethods::getFolderFilesRecoursively(static::getTemplatesFolderPath()));
            }
            return view('layouts.dynamicPage', compact('template'));
        });
        Route::get('designer/template', function() {
            $template = static::getCurrentTemplate();
            if ($template) {
                return response()->json(json_decode($template));
            }
            else {
                return response()->json([
                    'massage' => 'Invalid template-name, template not found'
                ]);
            }
        });
        Route::post('/designer/template', function() {
            CheckingMethods::checkConfigValueIsSet('app.node_modules_folder_path');
            CheckingMethods::checkConfigValueIsSet('app.designer_node_module_name');
            $templateContent = 'export default ' . json_encode(request()->input()['template'], JSON_PRETTY_PRINT);
            $templateName = request()->input()['template_name'];
            $componentFolderPath = static::getTemplatesFolderPath();
            FolderMethods::createIfNotExists($componentFolderPath);
            file_put_contents(FolderMethods::combinePaths($componentFolderPath, $templateName . '.js'), $templateContent);

            return response()->json([
                'message' => 'Success',
            ]);
        });
        Route::get('/designer/result', function () {
            $templatePath = static::getTemplatePath(request()->get('template-name'));
            $template = file_get_contents($templatePath);
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

    public static function getTemplatesFolderPath() {
        return FolderMethods::combinePaths(
            config('app.node_modules_folder_path'), 
            'Vue',
            config('app.designer_node_module_name'), 
            'src',
            'Templates'
        );
    }

    public static function getCurrentTemplate() {
        $templateName = request()->get('template-name');
        $templatePath = static::getTemplatePath($templateName);
        $templateText = file_get_contents($templatePath);
        if ($templateText) {
            return json_decode($templateText);
        }
        else {
            return null;
        }
    }

    public static function getTemplatePath(string $templateName) {
        return base_path('app/Templates/' . $templateName . '.json');
    }
}