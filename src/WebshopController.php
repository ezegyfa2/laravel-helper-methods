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
        return $tableInfos->getRequestDataResponse('products');
    }
}
