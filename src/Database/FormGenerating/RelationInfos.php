<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

class RelationInfos {
    public $referencedTableInfos;
    public $referencedColumnInfos;
    public $columnInfos;

    public function __construct($referencedTableInfos, $referencedColumnInfos, $columnInfos) {
        $this->referencedTableInfos = $referencedTableInfos;
        $this->referencedColumnInfos = $referencedColumnInfos;
        $this->columnInfos = $columnInfos;
    }

    public function getFormInfos($optionsCreator = null) {
        return (object)[
            'type' => 'select',
            'options' => $this->getOptions($optionsCreator),
        ];
    }

    public function getOptions($optionsCreator = null) {

    }
}
