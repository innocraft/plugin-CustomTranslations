<?php
/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */
namespace Piwik\Plugins\CustomTranslation\DataTable\Filter;

use Piwik\Columns\Dimension;
use Piwik\DataTable\BaseFilter;
use Piwik\DataTable;

class RenameLabelFilter extends BaseFilter
{
    /** @var array */
    private $renameMap;

    public function __construct($table, $renameMap)
    {
        parent::__construct($table);
        $this->renameMap = $renameMap;
    }

    /**
     * @param DataTable $table
     */
    public function filter($table)
    {
        $this->renameLabels($table, $level = 1);
    }

    /**
     * @param DataTable $table
     * @param Dimension $dimension
     * @param Dimension[] $dimension
     */
    private function renameLabels($table, $level)
    {
        if (!$this->renameMap || !is_array($this->renameMap)) {
            return;
        }

        $map = array();
        if (isset($this->renameMap['all'])) {
            $map = $this->renameMap['all']; // apply this translation map to all levels (root table + subtables)
        }

        if (isset($this->renameMap[$level])) {
            $map = array_merge($map, $this->renameMap[$level]); // only apply this to a specific table level eg 1===only root table... 2 === only first subtable level
        }

        foreach ($table->getRowsWithoutSummaryRow() as $row) {

            $label = $row->getColumn('label');
            if ($label && isset($map[$label])) {
                $row->setColumn('label', $map[$label]);
                $table->setLabelsHaveChanged();
            }

            $subtable = $row->getSubtable();
            if ($subtable) {
                $this->renameLabels($subtable, ++$level);
            }
        }
    }
}