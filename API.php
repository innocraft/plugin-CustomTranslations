<?php

/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTranslations;

use Piwik\API\Request;
use Piwik\Piwik;
use Piwik\Plugins\CustomTranslations\Dao\TranslationsDao;
use Piwik\Plugins\CustomTranslations\TranslationTypes\TranslationType;
use Piwik\Plugins\CustomTranslations\TranslationTypes\TranslationTypeProvider;

/**
 * Public API for managing CustomTranslations translation values and metadata.
 *
 * All endpoints exposed by this API require Super User access.
 *
 * @method static \Piwik\Plugins\CustomTranslations\API getInstance()
 */
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

    /**
     * Sets (overwrites) the translations for a specific type. Make sure to pass all translations for the given type
     * / language.
     * @param string $idType
     * @param string $languageCode
     * @param array $translations  An array where (original value => translation)
     * @throws \Exception If type, language, or translations is not valid
     */
    public function setTranslations($idType, $languageCode, $translations = array())
    {
        Piwik::checkUserHasSuperUserAccess();

        $this->provider->checkTypeExists($idType);
        $this->checkLanguageAvailable($languageCode);

        $this->storage->set($idType, $languageCode, $translations);
    }

    /**
     * Get all existing translations for a specific type and language.
     * @param string $idType
     * @param string $languageCode
     * @throws \Exception If type, language, or translations is not valid
     */
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

    /**
     * Get a list of all translatable types.
     * @return array[]
     */
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
