<?php

namespace Ezegyfa\LaravelHelperMethods;

class HttpMethods
{
    public static function registerDesignerRoute() {
        Route::post('/designer', function() {
            $templateName = request()->input('template_name');
            file_put_contents(
                static::getTemplatePath($templateName), 
                request()->input()['designedTemplate'], 
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
                'data' => (object) [],
            ];
            if ($templateName) {
                $template->data->template = json_decode(static::getTemplatePath($templateName));
            }
            return view('layouts.dynamicPage', compact('template'));
        });
        Route::get('/designer-result', function () {
            $templatePath = static::getTemplatePath(request()->get('template-name'));
            $template = file_get_contents($templatePath);
            return view('layouts.dynamicPage', compact('template'));
        });
        /*Route::get('/translated-designer-result', function () use($templatePath) {
            $templateParams = DynamicTemplateMethods::getTranslatedTemplateParamsFromFile($templatePath);
            return DynamicTemplateMethods::getTemplateDynamicPage('web-designer-designed-page', $templateParams);
        });*/
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
        });
        Route::post('/designer/create-template', function() {
            try {
                $templateContent = request()->input()['template'];
                $templateName = request()->input()['templateName'];
                $componentFolderPath = FolderMethods::combinePaths(
                    config('app.node_modules_folder_path'), 
                    'Vue',
                    'src',
                    'Templates'
                );
                
    
                return response()->json([
                    'message' => 'Success',
                ]);
            }
            catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    public static function getTemplatePath(string $templateName) {
        return base_path('app/templates/' . $templateName . '.json');
    }
}