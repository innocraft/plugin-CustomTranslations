<?php
/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTranslations\TranslationTypes;

use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Validators\BaseValidator;
use Piwik\Validators\WhitelistedValue;

class TranslationTypeProvider
{
    private $instances = [];

    public function checkTypeExists($idType)
    {
        $types = array_keys($this->getAllTranslationTypes());
        $params = [new WhitelistedValue($types)];
        BaseValidator::check(Piwik::translate('CustomTranslations_TranslationType'), $idType, $params);
    }

    /**
     * @ignore
     * @internal  tests only
     */
    public function clearCache()
    {
        $this->instances = [];
    }

    /**
     * @return TranslationType[]
     */
    public function getAllTranslationTypes()
    {
        if (empty($this->instances)) {
            $pluginManager = Manager::getInstance();
            $typeClassNames = $pluginManager->findMultipleComponents('TranslationTypes', TranslationType::class);

            foreach ($typeClassNames as $typeClassName) {
                $type = StaticContainer::getContainer()->make($typeClassName);
                $this->instances[$type->getId()] = $type;
            }

            if (!$pluginManager->isPluginInstalled('CustomReports') || !$pluginManager->isPluginActivated('CustomReports')) {
                // ideally we would move CustomReportEntity to the custom reports plugin, but this way it will be easier
                // testable for now as it needs to integrate with customdimensions and events etc
                // also for now we don't move dashboard entity to dashboards plugin etc
                unset($this->instances[CustomReportEntity::ID]);
            }
        }

        return $this->instances;
    }
}
