<?php

namespace Ezegyfa\LaravelHelperMethods\Crm\Controllers;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\Crm\CrmControllerFunctions;
use Illuminate\Support\Facades\Auth;

class BlueAdminController
{
    use CrmControllerFunctions;
    
    public $indexTemplateName = 'blue_admin_index';
    public $editTemplateName = 'blue_admin_edit';
    public $templateFolderPath = __DIR__ . '\\..\\Templates';
    public $filterFormItemPrefix = 'data-collector-filter';

    public function getSidebarSections() {
        return array_values(array_map(function($tableInfo) {
            return (object) [
                'type' => 'blue-admin-sidebar-navigation-link',
                'data' => (object)[
                    'url' => '/admin/' . $tableInfo->getNameInUrlFormat(),
                    'icon_class' => $this->getIconClass($tableInfo->name),
                    'content' => $tableInfo->getNameInNormalFormat()
                ]
            ];
        }, DatabaseInfos::getTableInfos()));
    }

    public function getNotificationFormInfos() {
        if (Auth::check()) {
            return \DB::table('notifications')
                ->select(['title', 'icon_class', 'created_at'])
                ->where('checked', false)
                ->andWhere('user_id', Auth::user()->id)
                ->get();
        }
        else {
            return [];
        }
    }
}
