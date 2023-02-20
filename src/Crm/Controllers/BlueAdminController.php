<?php

namespace Ezegyfa\LaravelHelperMethods\Crm\Controllers;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\Crm\CrmControllerFunctions;
use Illuminate\Support\Facades\Auth;

class BlueAdminController
{
    use CrmControllerFunctions;
    
    public $indexTemplateName = 'blue-admin-index-template';
    public $editTemplateName = 'blue-admin-edit-template';
    public $templateFolderPath = __DIR__ . '/../Templates';

    public function getSidebarSections() {
        return array_values(array_map(function($tableName) {
            return (object)[
                'type' => 'blue-admin-sidebar-navigation-link',
                'data' => (object)[
                    'url' => '/admin/' . $tableName,
                    'icon_class' => $this->getIconClass($tableName),
                    'content' => str_replace('_', ' ', $tableName)
                ]
            ];
        }, DatabaseInfos::getCrmTableNames()));
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
