<?php

/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTranslations\DataTable\Filter;

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
     * @param int $level
     */
    private function renameLabels($table, $level)
    {
        if (!$this->renameMap) {
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
