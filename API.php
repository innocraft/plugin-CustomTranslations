<?php

/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTranslations;

use Piwik\API\Request;
use Piwik\Http\BadRequestException;
use Piwik\Piwik;
use Piwik\Plugins\CustomTranslations\Dao\TranslationsDao;
use Piwik\Plugins\CustomTranslations\TranslationTypes\TranslationType;
use Piwik\Plugins\CustomTranslations\TranslationTypes\TranslationTypeProvider;
use Piwik\Validators\Exception as ValidatorException;

/**
 * Exposes Super User endpoints for listing translatable entities and storing custom label overrides.
 * Saved translations are applied to supported dashboard, event, custom dimension, and custom report outputs.
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
     * Stores the full translation map for a specific type and language.
     * Pass an empty array to remove any saved translations for that type and language.
     *
     * @param string $idType Translation type identifier returned by getTranslatableTypes().
     * @param string $languageCode Language code to store translations for.
     * @param array<string, string> $translations Map of original labels to replacement labels.
     * @return void
     */
    public function setTranslations($idType, $languageCode, $translations = array())
    {
        Piwik::checkUserHasSuperUserAccess();

        $this->checkTypeExists($idType);
        $this->checkLanguageAvailable($languageCode);

        if (!is_array($translations)) {
            throw new BadRequestException('$translations needs to be an array');
        }

        $this->storage->set($idType, $languageCode, $translations);
    }

    /**
     * Returns the saved translations for a specific type and language.
     *
     * @param string $idType Translation type identifier returned by getTranslatableTypes().
     * @param string $languageCode Language code to load translations for.
     * @return array<string, string> Translation map keyed by the original label for the requested type and language.
     */
    public function getTranslationsForType($idType, $languageCode)
    {
        Piwik::checkUserHasSuperUserAccess();

        $this->checkTypeExists($idType);
        $this->checkLanguageAvailable($languageCode);

        return $this->storage->get($idType, $languageCode);
    }

    private function checkTypeExists($idType): void
    {
        try {
            $this->provider->checkTypeExists($idType);
        } catch (ValidatorException $e) {
            throw new BadRequestException($e->getMessage());
        }
    }

    private function checkLanguageAvailable($languageCode)
    {
        $params = array('languageCode' => $languageCode);
        $languageAvailable = Request::processRequest('LanguagesManager.isLanguageAvailable', $params);

        if (!$languageAvailable) {
            throw new BadRequestException('Invalid language code');
        }
    }

    /**
     * Lists the translation types that can be configured through this plugin.
     *
     * @return array<int, array{id: string, name: string, description: string, translationKeys: array<int, string>}>
     *         Available translation type metadata, sorted by name.
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
