<?php
/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTranslation;

use Piwik\API\Request;
use Piwik\Piwik;
use Piwik\Plugins\CustomTranslation\Dao\TranslationsDao;
use Piwik\Plugins\CustomTranslation\TranslationTypes\TranslationType;
use Piwik\Plugins\CustomTranslation\TranslationTypes\TranslationTypeProvider;

class API extends \Piwik\Plugin\API
{
    /**
     * @var TranslationsDao
     */
    private $storage;
    /**
     * @var TranslationTypeProvider
     */
    private $provider;

    public function __construct(TranslationsDao $storage, TranslationTypeProvider $provider)
    {
        $this->storage = $storage;
        $this->provider = $provider;
    }

    public function updateTranslations($idType, $languageCode, $translations)
    {
        Piwik::checkUserHasSuperUserAccess();

        $this->provider->checkTypeExists($idType);
        $this->checkLanguageAvailable($languageCode);

        $this->storage->set($idType, $languageCode, $translations);
    }

    public function getTranslationsForType($idType, $languageCode)
    {
        Piwik::checkUserHasSuperUserAccess();

        $this->provider->checkTypeExists($idType);
        $this->checkLanguageAvailable($languageCode);

        return $this->storage->get($idType, $languageCode);
    }

    private function checkLanguageAvailable($languageCode)
    {
        $params = array('languageCode' => $languageCode);
        $languageAvailable = Request::processRequest('LanguagesManager.isLanguageAvailable', $params);

        if (!$languageAvailable) {
            throw new \Exception('Invalid language code');
        }
    }

    public function getTranslatableTypes()
    {
        Piwik::checkUserHasSuperUserAccess();

        $types = $this->provider->getAllTranslationTypes();
        usort($types, function ($a, $b) {
            /** @var TranslationType $a */
            /** @var TranslationType $b */
            return strcmp($a->getName(), $b->getName());
        });

        $metadata = array();
        foreach ($types as $type) {
            $metadata[] = array(
                'id' => $type->getId(),
                'name' => $type->getName(),
                'description' => $type->getDescription(),
                'translationKeys' => $type->getTranslationKeys(),
            );
        }

        return $metadata;
    }

}
