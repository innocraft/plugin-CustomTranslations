<?php
/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


namespace Piwik\Plugins\CustomTranslations\tests\System;

use Piwik\Cache;
use Piwik\Container\StaticContainer;
use Piwik\Filesystem;
use Piwik\Plugins\CustomTranslations\tests\Fixtures\CustomTranslationsFixture;
use Piwik\Plugins\CustomTranslations\TranslationTypes\TranslationTypeProvider;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tests\Framework\TestingEnvironmentManipulator;
use Piwik\Plugin;
use Piwik\Version;

/**
 * @group CustomTranslations
 * @group APITest
 * @group Plugins
 */
class APITest extends SystemTestCase
{
    /**
     * @var CustomTranslationsFixture
     */
    public static $fixture = null; // initialized below class definition

    public static function setUpBeforeClass(): void
    {
        $pluginsToLoad = array();

        if (CustomTranslationsFixture::hasCustomReports()) {
            $pluginsToLoad[] = 'CustomReports';
            TestingEnvironmentManipulator::$extraPluginsToLoad = $pluginsToLoad;
            self::$fixture->extraPluginsToLoad = $pluginsToLoad;
        }


        parent::setUpBeforeClass();

        $pluginManager = Plugin\Manager::getInstance();
        foreach ($pluginsToLoad as $extraPluginToLoad) {
            $pluginManager->loadPlugin($extraPluginToLoad);
            $pluginManager->installLoadedPlugins();
            $pluginManager->activatePlugin($extraPluginToLoad);
        }
    }

    /**
     * @dataProvider getTestsToRunWithAndWithoutCustomReports
     */
    public function test_getTranslatableTypes($api)
    {
        $params = array(
            'idSite' => 1,
            'date' => substr(self::$fixture->dateTime,0 ,10),
            'period' => 'day',
        );

        if (CustomTranslationsFixture::hasCustomReports()) {
            Plugin\Manager::getInstance()->deactivatePlugin('CustomReports');
            Plugin\Manager::getInstance()->unloadPlugin('CustomReports');
            $this->clearCaches();
        }

        $apiOutputIsMissingMetricTypes = version_compare(Version::VERSION, '4.13.4-b1', '<');
        $testSuffix = in_array($api, ['API.getReportMetadata', 'API.getWidgetMetadata', 'API.getReportPagesMetadata']) && $apiOutputIsMissingMetricTypes ? '_Old' : '';

        $this->runAnyApiTest($api, '', $params, array('testSuffix' => $testSuffix, 'xmlFieldsToRemove' => array('imageGraphUrl', 'imageGraphEvolutionUrl')));
    }

    public function getTestsToRunWithAndWithoutCustomReports()
    {
        if (version_compare(Version::VERSION, '4.4.0-b1', '<')) {
            return array(
                array('CustomTranslations.getTranslatableTypes')
            );
        }

        $tests = array(
            array('CustomTranslations.getTranslatableTypes'),
            array('API.getReportMetadata'),
            array('API.getWidgetMetadata'),
            array('API.getReportPagesMetadata'),
        );
        if (version_compare(Version::VERSION, '4.11.0', '<=')) {
            $tests = array(
                array('CustomTranslations.getTranslatableTypes'),
            );
        }

        return $tests;
    }

    /**
     * @dataProvider getTestsToRunWithAndWithoutCustomReports
     */
    public function test_getTranslatableTypes_withCustomReports($api)
    {
        if (!CustomTranslationsFixture::hasCustomReports()) {
            $this->markTestSkipped('Custom reports plugin is not available, we skip it');
            return;
        }

        $apiOutputIsMissingMetricTypes = version_compare(Version::VERSION, '4.13.4-b1', '<');

        $this->clearCaches();
        $this->makeSureToLoadCustomReports();

        $params = array(
            'idSite' => 1,
            'date' => substr(self::$fixture->dateTime,0 ,10),
            'period' => 'day',
        );

        $testSuffix = 'withCustomReports' . (in_array($api, ['API.getReportMetadata', 'API.getWidgetMetadata', 'API.getReportPagesMetadata']) && $apiOutputIsMissingMetricTypes ? '_Old' : '');
        $this->runAnyApiTest($api, '', $params, array('testSuffix' => $testSuffix, 'xmlFieldsToRemove' => array('imageGraphUrl', 'imageGraphEvolutionUrl')));
    }

    private function makeSureToLoadCustomReports()
    {
        if (CustomTranslationsFixture::hasCustomReports()) {
            Plugin\Manager::getInstance()->loadPlugin('CustomReports');
            Plugin\Manager::getInstance()->activatePlugin('CustomReports');
            $this->clearCaches();
            $env = self::$fixture->getTestEnvironment();
            if (empty($env->pluginsToLoad)) {
                $env->pluginsToLoad = array('CustomReports');
            } elseif (is_array($env->pluginsToLoad) && !in_array('CustomReports', $env->pluginsToLoad)) {
                $env->pluginsToLoad[] = array('CustomReports');
            }
            $env->save();
        }
    }

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->clearCaches();
        $this->makeSureToLoadCustomReports();
        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        $apiOutputIsMissingMetricTypes = version_compare(Version::VERSION, '4.13.4-b1', '<');
        $apiToTest = array();

        foreach (range(1,4) as $idDimension) {
            $apiToTest[] = array(
                array('CustomDimensions.getCustomDimension'),
                array(
                    'idSite' => 1,
                    'date' => self::$fixture->dateTime,
                    'period' => 'day',
                    'otherRequestParameters' => array(
                        'idDimension' => $idDimension
                    ),
                    'testSuffix' => '_dim' . $idDimension,
                )
            );
        }

        $apiToTest[] = array(array('API.getProcessedReport'), array(
            'idSite' => self::$fixture->idSite,
            'testSuffix' => '_getCustomDimensionProcessedReport' . ($apiOutputIsMissingMetricTypes ? '_Old' : ''),
            'otherRequestParameters' => array(
                'apiModule' => 'CustomDimensions',
                'apiAction' => 'getCustomDimension',
                'idDimension' => 1
            ),
            'date'       => self::$fixture->dateTime,
            'period' => 'day',
        ));

        $apiToTest[] = array(
            array('Events.getAction', 'Events.getName', 'Events.getCategory'),
            array(
                'idSite' => 1,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
            )
        );

        $apiToTest[] = array(
            array('Events.getName'),
            array(
                'idSite' => 1,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
                'otherRequestParameters' => array(
                    'filter_pattern' => 'RenamedEvent1',
                ),
                'testSuffix' => '_filterPattern',
            )
        );
        $apiToTest[] = array(
            array('Events.getCategory'),
            array(
                'idSite' => 1,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
                'otherRequestParameters' => array(
                    'secondaryDimension' => 'eventAction',
                    'pivotBy' => 'Events.EventAction',
                    'pivotByColumn' => 'nb_events'
                ),
                'testSuffix' => '_pivoted',
            )
        );

        $apiToTest[] = array(
            array('Dashboard.getDashboards'),
            array(
                'idSite' => 1,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
            )
        );

        $apiToTest[] = array(array('API.getProcessedReport'), array(
            'idSite' => self::$fixture->idSite,
            'testSuffix' => '_eventsProcessedReport' . ($apiOutputIsMissingMetricTypes ? '_Old' : ''),
            'otherRequestParameters' => array(
                'apiModule' => 'Events',
                'apiAction' => 'getAction',
            ),
            'date'       => self::$fixture->dateTime,
            'periods'    => array('day'),));

        $apiToTest[] = array(
            array('Events.getAction', 'Events.getName', 'Events.getCategory'),
            array(
                'idSite' => 1,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
                'otherRequestParameters' => array('flat' => '1'),
                'testSuffix' => '_flat',
            )
        );

        if (CustomTranslationsFixture::hasCustomReports()) {
            foreach (range(1,7) as $idCustomReport) {
                $apiToTest[] = array(
                    array('CustomReports.getCustomReport'),
                    array(
                        'idSite' => 1,
                        'date' => self::$fixture->dateTime,
                        'periods' => array('day'),
                        'otherRequestParameters' => array(
                            'idCustomReport' => $idCustomReport
                        ),
                        'testSuffix' => '_report' . $idCustomReport,
                    )
                );
                $apiToTest[] = array(
                    array('CustomReports.getCustomReport'),
                    array(
                        'idSite' => 1,
                        'date' => self::$fixture->dateTime,
                        'periods' => array('day'),
                        'otherRequestParameters' => array(
                            'idCustomReport' => $idCustomReport,
                            'flat' => '1'
                        ),
                        'testSuffix' => '_report' . $idCustomReport . '_flat',
                    )
                );
            }
        }
        return $apiToTest;
    }

    private function clearCaches()
    {
        StaticContainer::get(TranslationTypeProvider::class)->clearCache();
        Cache::flushAll();
        Filesystem::deleteAllCacheOnUpdate();
    }

    public static function getOutputPrefix()
    {
        return '';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }

}

APITest::$fixture = new CustomTranslationsFixture();