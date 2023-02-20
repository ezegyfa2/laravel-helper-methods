<?php

namespace Ezegyfa\LaravelHelperMethods\Crm;

use Ezegyfa\LaravelHelperMethods\Crm\CrmControllerRouteFunctions;
use Ezegyfa\LaravelHelperMethods\Crm\CrmControllerRouteInitializing;

trait CrmControllerFunctions
{
    use CrmControllerRouteFunctions, CrmControllerRouteInitializing;

    public function getTableIconClasses()
    {
        if (\Schema::hasTable('mytable')) {
            $rawIconClasses = \DB::table('table_icon_classes')->select(['table_name', 'icon_class'])->get();
            $iconClasses = [];
            foreach ($rawIconClasses as $rawIconClass) {
                $iconClasses[$rawIconClass->table_name] = $rawIconClass->icon_class;
            }
            return $iconClasses;
        }
        else {
            return [];
        }
    }

    private $iconClasses = null;
    public function getIconClass($tableName)
    {
        if ($this->iconClasses == null) {
            $this->iconClasses = $this->getTableIconClasses();
        }
        if (array_key_exists($tableName, $this->iconClasses)) {
            return $this->iconClasses[$tableName];
        }
        else {
            return "";
        }
    }

    /*protected $modelTypeNamespaceUrl;
    public function getModelTypeNamespaceUrl()
    {
        if (!isset($this->modelTypeNamespaceUrl))
            $this->setModelTypeNamespaceUrl();
        return $this->modelTypeNamespaceUrl;
    }
    public function setModelTypeNamespaceUrl()
    {
        $this->modelTypeNamespaceUrl = 'App\\Models\\' . $this->getModelTypeName();
    }

    protected $tableName;
    public function getTableName()
    {
        if (!isset($this->tableName))
            $this->setTableName();
        return $this->tableName;
    }
    public function setTableName()
    {
        $this->tableName = getModelTypeNameTableName($this->getModelTypeName());
    }

    protected $modelTypeName;
    public function getModelTypeName()
    {
        return $this->modelTypeName;
    }
    public function setModelTypeName(string $modelTypeName)
    {
        $this->modelTypeName = $modelTypeName;
        $this->setTableName();
        $this->setModelTypeNamespaceUrl();
    }

    public $requestValidationRules;*/
}
