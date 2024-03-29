<?php

namespace Ezegyfa\LaravelHelperMethods\Crm;

use Exception;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\DynamicTemplateMethods;
use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use stdClass;

trait CrmControllerRouteFunctions
{
    public function getIndexView(string $tableName) {
        $templateParams = $this->getLayoutTemplateParams($tableName);
        $templateParams->table_data = $this->getTableData($tableName);
        return DynamicTemplateMethods::getTemplateDynamicPage($this->indexTemplateName, $templateParams);
    }

    public function getTableData(string $tableName) {
        $tableInfos = DatabaseInfos::getAdminTableInfos($tableName, 'index', []);
        $selectedRowToShowCount = intval(request()->get('row-count', 10));
        $selectedPageNumber = intval(request()->get('page-number', 1));
        //dd($tableInfos->getFilterFormInfos());
        $filterSections = $tableInfos->getFilterFormInfos('admin.' . $tableName);
        //dd($filterSections[0]);
        return (object) [
            'title' => __('admin.' . $tableName . '.title'),
            'row_to_show_counts' => [ 10, 25, 50 ],
            'selected_row_to_show_count' => $selectedRowToShowCount,
            'selected_page_number' => $selectedPageNumber,
            'filter_form_item_type_prefix' => $this->filterFormItemPrefix,
            'filter_sections' => $filterSections,
            'rows' => []
        ];
    }

    public function getCreateView(string $tableName) {
        $templateParams = $this->getLayoutTemplateParams($tableName);
        $formItems = DatabaseInfos::getAdminTableInfos($tableName, 'create')->getFormInfos('admin.' . $tableName);
        $templateParams->form_data = (object) [
            'title' => 'Create new ' . str_replace('_', ' ', Str::singular($tableName)),
            'url' => route($tableName . '.store'),
            'button_title' => 'Create',
            'form_item_sections' => $formItems,
        ];
        //dd($templateParams);
        return DynamicTemplateMethods::getTemplateDynamicPage($this->editTemplateName, $templateParams);
    }

    public function processStore(Request $request, string $tableName) {
        return HttpMethods::getStoreRequest($request, $tableName, 'Model was successfully added!', route($tableName . '.index'));
    }

    public function getData(string $tableName) {
        return DatabaseInfos::getAdminTableInfos($tableName, 'index', [])
            ->getRequestDataResponse('admin.' . $tableName);
    }

    public function getSelectOptions(string $tableName) {
        $columnName = request()->get('column-name');
        $tableInfos = DatabaseInfos::getAdminTableInfos($tableName, 'index');
        $relationInfos = $tableInfos->getColumnRelation($tableInfos->columnInfos[$columnName]);
        return $relationInfos->getOptions(request()->get('searched-text', 10));
    }

    public function getEditView($id, string $tableName) {
        $templateParams = $this->getLayoutTemplateParams($tableName);
        $templateParams->form_data = (object) [
            'title' => 'Edit ' . str_replace('_', ' ', Str::singular($tableName)),
            'url' => route($tableName . '.update', [ 'id' => $id ]),
            'button_title' => 'Edit',
            'form_item_sections' => DatabaseInfos::getAdminTableInfos($tableName, 'edit')->getFormInfos('admin.' . $tableName, $id),
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage($this->editTemplateName, $templateParams);
    }

    public static function processUpdate($id, Request $request, string $tableName) {
        return HttpMethods::getUpdateRequest($request, $id, $tableName, 'Model was successfully updated!', route($tableName . '.index'));   
    }

    public static function processDestroy($id, string $tableName) {
        try {
            if (!\DB::table($tableName)->find($id)) {
                throw new \Exception('Item with id: ' . $id . 'doesn\'t exists in table ' . $tableName);
            }
            \DB::table($tableName)->delete($id);
            return redirect()->route($tableName . '.index')
                             ->with('success_messages', [ 'Model was successfully deleted!' ]);
        } catch (\Exception $exception) {
            return back()->withInput()
                         ->with('error_messages', [ 'Unexpected error occurred while trying to process your request!' ]);
        }
    }

    public function getLayoutTemplateParams(string $tableName) {
        $templateParams = DynamicTemplateMethods::getTranslatedTemplateParamsFromFile($this->getCompiledTemplatePath($tableName, "index"));
        //$templateParams = new stdClass;
        $templateParams->sidebar_sections = $this->getSidebarSections();
        $this->addSessionDataToTemplateParams($templateParams, 'success_messages');
        $this->addSessionDataToTemplateParams($templateParams, 'error_messages');
        $this->addSessionDataToTemplateParams($templateParams, 'warning_messages');
        $this->addSessionDataToTemplateParams($templateParams, 'info_messages');
        return $templateParams;
    }

    public function addSessionDataToTemplateParams($templateParams, $sessionDataName) {
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

    public function getCompiledTemplatePath(string $tableName, string $templateName) {
        return $this->templateFolderPath . '\\' . $templateName . '_compiled.json';
    }
}
