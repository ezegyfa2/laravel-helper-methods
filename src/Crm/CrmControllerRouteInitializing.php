<?php

namespace Ezegyfa\LaravelHelperMethods\Crm;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\FolderMethods;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

trait CrmControllerRouteInitializing
{
    public function initializeRoutes()
    {
        $controllerNames = static::getControllerNames();
        $controllerTableNames = [];
        foreach ($controllerNames as $controllerName) {
            $controllerTableNames[$controllerName] = lcfirst(Str::plural(Str::snake(str_replace('Controller', '', $controllerName))));
        }
        foreach (DatabaseInfos::getCrmTableNames() as $tableName) {
            if (in_array($tableName, $controllerTableNames)) {
                $controllerName = array_search($tableName, $controllerTableNames);
                $indexFunction = $controllerName . '@index';
                $filterFunction = $controllerName . '@filter';
                $createFunction = $controllerName . '@create';
                $storeFunction = $controllerName . '@store';
                $editFunction = $controllerName . '@edit';
                $updateFunction = $controllerName . '@update';
                $getDataFunction = $controllerName . '@getData';
                $destroyFunction = $controllerName . '@destroy';
                /*$queryFunction = $controllerName . '@query';
                $showFunction = $controllerName . '@show';*/
            }
            else {
                $indexFunction = function() use($tableName) {
                    return $this->getIndexView($tableName);
                };
                $filterFunction = function() use($tableName) {
                    return $this->getIndexView($tableName);
                };
                $createFunction = function() use($tableName) {
                    return $this->getCreateView($tableName);
                };
                $storeFunction = function(Request $request) use($tableName) {
                    return $this->processStore($request, $tableName);
                };
                $editFunction = function($id) use($tableName) {
                    return $this->getEditView($id, $tableName);
                };
                $updateFunction = function($id, Request $request) use($tableName) {
                    return static::processUpdate($id, $request, $tableName);
                };
                $getDataFunction = function() use($tableName) {
                    return $this->getData($tableName);
                };
                $destroyFunction = function($id) use($tableName) {
                    return $this->processDestroy($id, $tableName);
                };
                /*$queryFunction = function() use($tableName) {
                    return static::getQuery($tableName);
                };
                $showFunction = function() use($tableName) {
                    return static::getShowView($tableName);
                };*/
            }
            Route::group([
                'prefix' => 'admin/' . str_replace('_', '-', $tableName)
            ], function () use($tableName, $indexFunction, $filterFunction, $createFunction, $storeFunction, $editFunction, $updateFunction, $destroyFunction, $getDataFunction) {
                Route::get('/', $indexFunction)
                    ->name($tableName . '.index');
                Route::post('/', $filterFunction)
                    ->name($tableName . '.filter');
                Route::get('/create', $createFunction)
                    ->name($tableName . '.create');
                Route::post('/store', $storeFunction)
                    ->name($tableName . '.store');
                Route::get('/edit/{id}', $editFunction)
                    ->name($tableName . '.edit')
                    ->where('id', '[0-9]+');
                Route::post('/update/{id}', $updateFunction)
                    ->name($tableName . '.update')
                    ->where('id', '[0-9]+');
                Route::post('/get-data', $getDataFunction)
                    ->name($tableName . '.get_data');
                Route::delete('/{id}', $destroyFunction)
                    ->name($tableName . '.destroy')
                    ->where('id', '[0-9]+');
                /*Route::get('/getQuery', $queryFunction)
                    ->name($tableName . '.index.query');
                Route::get('/show/{id}', $showFunction)
                    ->name($tableName . '.show')
                    ->where('id', '[0-9]+');*/
            });
        }
    }

    public static function getControllerNames()
    {
        $controllerFileNames = FolderMethods::getFolderFilesRecoursively(app_path('Http/Controllers'));
        return array_map(function($controllerFileName) {
            return str_replace('.php', '', $controllerFileName);
        }, $controllerFileNames);
    }
}
