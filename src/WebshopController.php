<?php

namespace Ezegyfa\LaravelHelperMethods;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebshopController extends Controller
{
    protected $tableName = 'products';

    public function getBasicData() {
        $tableInfos = DatabaseInfos::getTableInfos()[$this->tableName];
        $selectedRowToShowCount = intval(request()->get('row-count', 10));
        $selectedPageNumber = intval(request()->get('page-number', 1));
        return $tableInfos->getRawData($selectedRowToShowCount, $selectedPageNumber, []);
    }
}
