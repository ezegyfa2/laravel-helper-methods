<?php

namespace Ezegyfa\LaravelHelperMethods\Crm;

use Exception;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\DynamicTemplateMethods;
use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

trait CrmControllerRouteFunctions
{
    public function index()
    {
        return $this->getIndexView(static::getTableName());
    }

    /*public function query()
    {
        return static::getQuery(static::getTableName());
    }

    public function create()
    {
        return static::getCreateView(static::getTableName()); 
    }

    public function store(Request $request)
    {
        return static::processStore($request, static::getTableName()); 
    }

    public function show($id)
    {
        return static::getShowView($id, static::getTableName()); 
    }*/


    public function edit($id)
    {
        return $this->getEditView($id, static::getTableName()); 
    }

    public function update($id, Request $request)
    {
        return $this->processUpdate($id, $request, static::getTableName());      
    }

    public function destroy($id)
    {
        return $this->processDestroy($id, static::getTableName());
    }

    public function getIndexView(string $tableName)
    {
        $templateParams = $this->getLayoutTemplateParams($tableName);
        $templateParams->table_data = $this->getTableData($tableName);
        return DynamicTemplateMethods::getTemplateDynamicPage($this->indexTemplateName, $templateParams);
    }

    public function getTableData(string $tableName)
    {
        $selectedRowToShowCount = intval(request()->get('row-count', 10));
        $selectedPageNumber = intval(request()->get('page-number', 1));
        return (object)[
            'title' => $tableName,
            'row_to_show_counts' => [ 10, 25, 50 ],
            'selected_row_to_show_count' => $selectedRowToShowCount,
            'selected_page_number' => $selectedPageNumber
        ];
    }

    public function getCreateView(string $tableName)
    {
        $templateParams = $this->getLayoutTemplateParams($tableName);
        $formItems = DatabaseInfos::getSpecificTableInfos($tableName, 'create')->getFormInfos('admin.' . $tableName);
        $templateParams->form_data = (object) [
            'title' => 'Create new ' . str_replace('_', ' ', Str::singular($tableName)),
            'url' => str_replace('/create', '', \Request::url()),
            'button_title' => 'Create',
            'form_item_sections' => $formItems,
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage($this->editTemplateName, $templateParams);
    }

    public function processStore(Request $request, string $tableName)
    {
        return HttpMethods::getStoreRequest($request, $tableName, 'Model was successfully added!', route($tableName . '.index'));
    }

    public function getData(string $tableName)
    {
        $rowToShowCount = intval(request()->get('row-count', 10));
        $tableInfos = DatabaseInfos::getSpecificTableInfos($tableName, 'index');
        $columnNames = $tableInfos->getColumnNamesWithTableName();
        array_push($columnNames, 'id');
        $selectedPageNumber = intval(request()->get('page-number', 1));
        $totalRowCount = \DB::table($tableName)->count();
        $rows = \DB::table($tableName)
            ->select($columnNames)
            ->limit($rowToShowCount)
            ->offset(($selectedPageNumber - 1) * $rowToShowCount)
            ->get()->toArray();
        foreach ($tableInfos->relationInfos as $relationInfo) {
            $renderValues = $relationInfo->getRenderValues($selectedPageNumber, $rowToShowCount);
            for ($i = 0; $i < count($rows); ++$i) {
                $columnName = $relationInfo->referenceColumnName;
                $rows[$i]->$columnName = $renderValues[$i];
            }
        }
        return response()->json((object) [
            'total_row_count' => $totalRowCount,
            'column_names' => $columnNames,
            'rows' => $rows
        ]);
    }

    public function getEditView($id, string $tableName)
    {
        $templateParams = $this->getLayoutTemplateParams($tableName);
        $templateParams->form_data = (object) [
            'title' => 'Edit ' . str_replace('_', ' ', Str::singular($tableName)),
            'url' => str_replace('/edit', '', \Request::url()),
            'button_title' => 'Edit',
            'form_item_sections' => DatabaseInfos::getSpecificTableInfos($tableName, 'edit')->getFormInfos('admin.' . $tableName, $id),
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage($this->editTemplateName, $templateParams);
    }

    public static function processUpdate($id, Request $request, string $tableName)
    {
        return HttpMethods::getUpdateRequest($request, $id, $tableName, 'Model was successfully updated!', route($tableName . '.index'));   
    }

    public static function processDestroy($id, string $tableName)
    {
        try {
            if (!\DB::table($tableName)->find($id)) {
                throw new \Exception('Item with id: ' . $id . 'doesn\'t exists in table ' . $tableName);
            }
            \DB::table($tableName)->delete($id);
            return redirect()->route($tableName . '.index')
                             ->with('success_messages', ['Model was successfully deleted!']);
        } catch (\Exception $exception) {
            return back()->withInput()
                         ->with('error_messages', ['Unexpected error occurred while trying to process your request!']);
        }
    }

    public function getLayoutTemplateParams(string $tableName)
    {
        $templateParams = DynamicTemplateMethods::getTranslatedTemplateParamsFromFile($this->getCompiledTemplatePath($tableName, "index"));
        $templateParams->sidebar_sections = $this->getSidebarSections();
        $this->addSessionDataToTemplateParams($templateParams, 'success_messages');
        $this->addSessionDataToTemplateParams($templateParams, 'error_messages');
        $this->addSessionDataToTemplateParams($templateParams, 'warning_messages');
        $this->addSessionDataToTemplateParams($templateParams, 'info_messages');
        return $templateParams;
    }

    public function addSessionDataToTemplateParams($templateParams, $sessionDataName)
    {
        if (Session::has($sessionDataName)) {
            $templateParams->$sessionDataName = Session::get($sessionDataName);
        }
    }

    /*public static function getQuery(string $tableName)
    {
        $modelTypeNamespaceUrl = getTableModelTypeNamespaceUrl($tableName);
        return $modelTypeNamespaceUrl::getDataTableQuery();
    }

    public static function getShowView($id, string $tableName)
    {
        $modelTypeNamespaceUrl = getTableModelTypeNamespaceUrl($tableName);
        $modelToShow = $modelTypeNamespaceUrl::withRelationsQuery()->get();

        return view('data.show', compact('modelToShow'));
    }

    public static function getTableNamesToRender()
    {
        $tableNames = DatabaseInfos::getTableNames();
        unset($tableNames[2]);
        unset($tableNames[10]);
        unset($tableNames[21]);
        unset($tableNames[23]);
        return $tableNames;
    }*/

    public function getCompiledTemplatePath(string $tableName, string $templateName)
    {
        return $this->templateFolderPath . '/' . $templateName . '_compiled.json';
    }

    public function getSidebarSections()
    {
        return array_map(function($tableName) {
            return (object)[
                'type' => 'blue-admin-icon-dropdown-item',
                'data' => (object)[
                    'url' => '',
                    'icon_class' => 'fas fa-file-alt text-white',
                    'sub_content' => 'December 12 2019',
                    'content' => 'A new monthly report is ready to download!'
                ]
            ];
        }, array_values(DatabaseInfos::getTableNames()));
    }
}
