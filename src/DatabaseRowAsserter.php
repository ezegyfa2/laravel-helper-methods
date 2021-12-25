<?php

namespace Helpers;

use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\assertEquals;

class DatabaseRowAsserter
{
    public $tableName;
    public $rowId;
    public $expectedRow;

    public function __construct(string $tableName, int $rowId, array $expectedRow)
    {
        $this->tableName = $tableName;
        $this->rowId = $rowId;
        $this->expectedRow = $expectedRow;
    }

    public function assert()
    {
        $row = DB::table($this->tableName)->find($this->rowId);
        $row = ArrayMethods::convert($row);
        $correctRowValues = array_intersect_assoc($row, $this->expectedRow);
        assertEquals(count($this->expectedRow), count($correctRowValues),
            'Unexpected values in row: ' . json_encode($this->getDatabaseRowCurrentValues($row))
            . '. Expected: ' . json_encode($this->expectedRow));
    }

    protected function getDatabaseRowCurrentValues(array $row): array
    {
        $rowKeys = array_filter(array_keys($row), function ($rowKey) {
            return array_key_exists($rowKey, $this->expectedRow);
        });
        $currentRowValues = [];
        foreach ($rowKeys as $rowKey) {
            $currentRowValues[$rowKey] = $row[$rowKey];
        }
        return $currentRowValues;
    }
}
