<?php
/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTranslations\tests\Fixtures;

use Piwik\API\Request;
use Piwik\Date;
use Piwik\Filesystem;
use Piwik\Plugin;
use Piwik\Plugins\CustomTranslations\API;
use Piwik\Plugins\CustomTranslations\TranslationTypes\CustomDimensionEntity;
use Piwik\Plugins\CustomTranslations\TranslationTypes\CustomDimensionLabel;
use Piwik\Plugins\CustomTranslations\TranslationTypes\CustomReportEntity;
use Piwik\Plugins\CustomTranslations\TranslationTypes\DashboardEntity;
use Piwik\Plugins\CustomTranslations\TranslationTypes\EventLabel;
use Piwik\Tests\Framework\Fixture;

class CustomTranslationsFixture extends Fixture
{
    public $dateTime = '2013-01-23 01:23:45';
    public $idSite = 1;
    public $idSite2 = 2;
    public $idSite3 = 3;

    public function setUp()
    {
        Fixture::createSuperUser();

        if (self::hasCustomReports()) {
            Plugin\Manager::getInstance()->loadPlugin('CustomReports');
            Plugin\Manager::getInstance()->installLoadedPlugins();
            Plugin\Manager::getInstance()->activatePlugin('CustomReports');
        }

        $this->setUpWebsite();
        $this->setTranslations();
        $this->createDashboards();
        $this->createCustomDimensions();
        $this->createCustomReports();

        $this->trackFirstVisit();
    }

    public function tearDown()
    {
        // empty
    }

    private function setUpWebsite()
    {
        foreach (array($this->idSite, $this->idSite2, $this->idSite3) as $idSite) {
            if (!self::siteCreated($idSite)) {
                $idSiteCreated = self::createWebsite($this->dateTime, $ecommerce = 1);
                $this->assertSame($idSite, $idSiteCreated);
            }
        }
    }

    private function createDashboards()
    {
        foreach (array(Fixture::ADMIN_USER_LOGIN, 'foouser', 'baruser') as $login) {
            Request::processRequest('Dashboard.createNewDashboardForUser', array(
                'login' => $login, 'dashboardName' => 'Dashboard1',
            ));
            Request::processRequest('Dashboard.createNewDashboardForUser', array(
                'login' => $login, 'dashboardName' => 'Dashboard2',
            ));
            Request::processRequest('Dashboard.createNewDashboardForUser', array(
                'login' => $login, 'dashboardName' => 'Dashboard3',
            ));
        }
    }

    private function createCustomReports()
    {
        if (!self::hasCustomReports()) {
            return;
        }
        Filesystem::deleteAllCacheOnUpdate(); // make sure custom dimensions are not cached etc
        foreach (array($this->idSite, $this->idSite2) as $idSite) {
            $this->addCustomReportEvolution($idSite, 'CustomReport1');
            $this->addCustomReportTable($idSite, 'CustomReport2', ["Actions.VisitTotalActions","Events.EventName","CustomDimension.CustomDimension2"]);
            $this->addCustomReportTable($idSite, 'CustomReport3', ["Events.EventName","CustomDimension.CustomDimension2"]);
            $this->addCustomReportTable($idSite, 'CustomReport4', ["CustomDimension.CustomDimension2","Events.EventName"]);
            $this->addCustomReportTable($idSite, 'CustomReport5', ["CustomDimension.CustomDimension4","CustomDimension.CustomDimension2"]);
            $this->addCustomReportTable($idSite, 'CustomReport6', ["Events.EventName","CustomDimension.CustomDimension4"]);
            $this->addCustomReportTable($idSite, 'CustomReport7', $dimensions = array("Actions.VisitTotalActions"));
        }
    }

    private function addCustomReportEvolution($idSite, $name)
    {
        return Request::processRequest('CustomReports.addCustomReport', array(
            'idSite' => $idSite, 'name' => $name, 'reportType' => 'evolution', 'metricIds' => array('nb_visits')
        ));
    }

    private function addCustomReportTable($idSite, $name, $dimensions)
    {
        return Request::processRequest('CustomReports.addCustomReport', array(
            'idSite' => $idSite, 'name' => $name, 'reportType' => 'table', 'metricIds' => array('nb_visits'), 'dimensionIds' => $dimensions
        ));
    }

    private function createCustomDimensions()
    {
        foreach (array($this->idSite, $this->idSite2) as $idSite) {
            // we create entries for multiple sites to make sure the translation types fetch the names distinct
            Request::processRequest('CustomDimensions.configureNewCustomDimension', array(
                'idSite' => $idSite, 'name' => 'DimensionVisit1', 'scope' => 'visit', 'active' => '1'
            ));
            Request::processRequest('CustomDimensions.configureNewCustomDimension', array(
                'idSite' => $idSite, 'name' => 'DimensionVisit2', 'scope' => 'visit', 'active' => '1'
            ));
            Request::processRequest('CustomDimensions.configureNewCustomDimension', array(
                'idSite' => $idSite, 'name' => 'DimensionAction1', 'scope' => 'action', 'active' => '1'
            ));
            Request::processRequest('CustomDimensions.configureNewCustomDimension', array(
                'idSite' => $idSite, 'name' => 'DimensionAction2', 'scope' => 'action', 'active' => '1'
            ));
        }
    }

    private function setTranslations()
    {
        API::getInstance()->setTranslations(DashboardEntity::ID, 'en', array(
            'Dashboard1' => 'RenamedDash1',
            'Dashboard3' => 'RenamedDash3',
            'foobar' => 'baz'
        ));
        API::getInstance()->setTranslations(CustomDimensionLabel::ID, 'en', array(
            'genericValue1' => 'RenamedDimension1',
            'genericValue2' => 'RenameDimension2',
            'actionDim1' => 'RenameDimension3',
            'foobar' => 'baz'
        ));
        API::getInstance()->setTranslations(CustomDimensionEntity::ID, 'en', array(
            'DimensionVisit1' => 'RenamedDimVisit1',
            'DimensionAction2' => 'RenamedDimAction2',
            'foobar' => 'baz'
        ));
        API::getInstance()->setTranslations(EventLabel::ID, 'en', array(
            'genericValue1' => 'RenamedEvent1',
            'actionDim1111' => 'RenamedEvent11111111', // shouldn't be renamed cause not used in events
            'visitDim2' => 'RenamedVisitDim2', // shouldn't be renamed cause not used in events
            'MyAction' => 'RenamedAction',
            'MyCategory' => 'RenamedCategory',
            'foobar' => 'baz',
        ));
        if (self::hasCustomReports()) {
            API::getInstance()->setTranslations(CustomReportEntity::ID, 'en', array(
                'CustomReport1' => 'RenamedReport1',
                'CustomReport3' => 'RenamedReport3',
                'foobar' => 'baz'
            ));
        }
    }

    protected function trackFirstVisit()
    {
        $t = self::getTracker($this->idSite, $this->dateTime, $defaultInit = true);
        $t->setForceVisitDateTime(Date::factory($this->dateTime)->addHour(0.1)->getDatetime());
        $t->setUrl('http://example.com/');

        $t->setCustomTrackingParameter('dimension1', 'genericValue1');
        $t->setCustomTrackingParameter('dimension2', 'visitDim2');

        $t->setCustomTrackingParameter('dimension3', 'actionDim1');
        $t->setCustomTrackingParameter('dimension4', 'genericValue2');
        self::checkResponse($t->doTrackPageView('Viewing homepage'));

        $t->setCustomTrackingParameter('dimension3', 'actionDim1');
        self::checkResponse($t->doTrackPageView('Viewisimulng homepage 1'));

        $t->setCustomTrackingParameter('dimension3', 'actionDim1111');
        self::checkResponse($t->doTrackPageView('Viewing homepage 2'));

        $t->setCustomTrackingParameter('dimension4', 'actionDim222');
        self::checkResponse($t->doTrackPageView('Viewing homepage 3'));

        $t->setForceVisitDateTime(Date::factory($this->dateTime)->addHour(0.2)->getDatetime());
        $t->setUrl('http://example.com/sub/page');
        self::checkResponse($t->doTrackEvent('MyCategory', 'MyAction', 'genericValue1', 5));
        self::checkResponse($t->doTrackEvent('MyCategory2', 'genericValue2', 'MyName2', 2));
        self::checkResponse($t->doTrackEvent('MyCategory3', 'MyAction3', 'MyName3', 7));
        self::checkResponse($t->doTrackEvent('MyCategory3', 'MyAction3', 'MyName3', 7));
    }

    public static function hasCustomReports()
    {
        return file_exists(PIWIK_DOCUMENT_ROOT . '/plugins/CustomReports/CustomReports.php');
    }

}