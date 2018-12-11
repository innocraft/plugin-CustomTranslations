/*!
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("CustomTranslationReporting", function () {
    this.timeout(0);

    this.fixture = "Piwik\\Plugins\\CustomTranslations\\tests\\Fixtures\\CustomTranslationsFixture";
    this.optionsOverride = {
        'persist-fixture-data': false
    };
    
    var fs = require('fs');
    var customReportsPath = PIWIK_INCLUDE_PATH + '/plugins/CustomReports/CustomReports.php';
    var hasCustomReports = fs.exists(customReportsPath);
    
    var generalParams = 'idSite=1&period=month&date=2013-01-23',
        urlBase = 'module=CoreHome&action=index&' + generalParams,
        manageBase = "?" + generalParams + '&',
        reportBase = "?" + urlBase + "#?" + generalParams + '&',
        widgetBase = '?module=Widgetize&action=iframe&widget=1&disableLink=1&' + generalParams;

    before(function () {
        testEnvironment.pluginsToLoad = ['CustomTranslations', 'CustomDimensions'];
        if (hasCustomReports) {
            testEnvironment.pluginsToLoad.push('CustomReports');
        }
        testEnvironment.save();
    });

    function captureSelector(done, screenshotName, test, selector)
    {
        if (!selector) {
            selector = pageSelector;
        }
        expect.screenshot(screenshotName).to.be.captureSelector(selector, test, done);
    }

    function captureMenu(done, screenshotName, test)
    {
        captureSelector(done, screenshotName, test, '#secondNavBar .menuTab.active');
    }

    function capturePageTable(done, screenshotName, test)
    {
        captureSelector(done, screenshotName, test, '#content table');
    }

    function captureDialog(done, screenshotName, test)
    {
        captureSelector(done, screenshotName, test, '.ui-dialog');
    }

    function captureModal(done, screenshotName, test)
    {
        captureSelector(done, screenshotName, test, '.modal.open');
    }

    function captureWidget(done, screenshotName, test)
    {
        captureSelector(done, screenshotName, test, 'body');
    }

    /**
     * DASHBOARD + MENU
     */

    function openMenuItem(page, menuItem)
    {
        var selector = '#secondNavBar .navbar a:contains('+ menuItem + '):first';
        page.click(selector);
        if (menuItem === 'Custom Reports') {
            page.click('#secondNavBar .navbar .menuTab.active .menuDropdown .title');
        }
    }

    it('should load the dashboard menu correctly with translations', function (done) {
        captureMenu(done, 'menu_loaded_dashboards', function (page) {
            page.load(reportBase + "category=Dashboard_Dashboard&subcategory=1");
            openMenuItem(page, 'Behaviour');
            openMenuItem(page, 'Dashboard');// we make sure dashboard is selected, even though it should be by default
        });
    });

    it('should load the visitors menu correctly with translations', function (done) {
        captureMenu(done, 'menu_loaded_visitors', function (page) {
            openMenuItem(page, 'Visitors');
        });
    });

    it('should load the behaviour menu correctly with translations', function (done) {
        captureMenu(done, 'menu_loaded_behaviour', function (page) {
            openMenuItem(page, 'Behaviour');
        });
    });

    if (hasCustomReports) {
        it('should load the custom reports menu correctly with translations', function (done) {
            captureSelector(done, 'menu_loaded_customreports', function (page) {
                openMenuItem(page, 'Custom Reports');
            }, '#secondNavBar .menuTab.active,#secondNavBar .navbar .menuTab.active .menuDropdown .items');
        });
    }

    it("should show the original dashboard name when trying to rename dashboard", function (done) {
        captureModal(done, 'dashboard_rename', function (page) {
            page.click('.dashboard-manager .title');
            page.click('li[data-action=renameDashboard]');
        });
    });

    /**
     * MANAGE SCREENS SHOULD SHOW ORIGINAL VALUE, NOT TRANSLATION
     */
    if (hasCustomReports) {
        it('should load the custom reports with their original name in the admin manage screen', function (done) {
            capturePageTable(done, 'manage_custom_reports_admin', function (page) {
                page.load(manageBase + "module=CustomReports&action=manage");
            });
        });
        it('should load the custom reports with their original name in the report manage screen', function (done) {
            capturePageTable(done, 'manage_custom_reports_admin', function (page) {
                page.load(reportBase + "category=CustomReports_CustomReports&subcategory=CustomReports_ManageReports");
            });
        });
    }

    it('should load the custom dimensions with their original name in the admin manage screen', function (done) {
        capturePageTable(done, 'manage_custom_dimensions_admin', function (page) {
            page.load(manageBase + "module=CustomDimensions&action=manage");
        });
    });

    /**
     * REPORTS SHOULD SHOW THE TRANSLATED VALUE
     */

    var widgetsToCheck = [
        {onlyBasicCheck: false, testName: 'eventActionName', 'moduleToWidgetize':'Events','actionToWidgetize':'getAction', 'secondaryDimension': 'eventName'},
        {onlyBasicCheck: true, testName: 'eventCategoryAction', 'moduleToWidgetize':'Events','actionToWidgetize':'getCategory', 'secondaryDimension': 'eventAction'},
        {onlyBasicCheck: false, testName: 'visitDimension1', 'moduleToWidgetize':'CustomDimensions','actionToWidgetize':'getCustomDimension', 'idDimension': '1'},
        {onlyBasicCheck: true, testName: 'visitDimension2', 'moduleToWidgetize':'CustomDimensions','actionToWidgetize':'getCustomDimension', 'idDimension': '2'},
        {onlyBasicCheck: false, testName: 'actionDimension3', 'moduleToWidgetize':'CustomDimensions','actionToWidgetize':'getCustomDimension', 'idDimension': '3'},
        {onlyBasicCheck: true, testName: 'actionDimension4', 'moduleToWidgetize':'CustomDimensions','actionToWidgetize':'getCustomDimension', 'idDimension': '4'},
        {onlyBasicCheck: true, testName: 'customReports1', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '1'},
        {onlyBasicCheck: true, testName: 'customReports2', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '2', 'flat': 1},
        {onlyBasicCheck: false, testName: 'customReports3', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '3'},
        {onlyBasicCheck: true, testName: 'customReports3_flat', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '3', 'flat': 1},
        {onlyBasicCheck: true, testName: 'customReports4', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '4', 'flat': 1},
        {onlyBasicCheck: true, testName: 'customReports5', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '5', 'flat': 1},
        {onlyBasicCheck: true, testName: 'customReports6', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '6', 'flat': 1},
        {onlyBasicCheck: true, testName: 'customReports7', 'moduleToWidgetize':'CustomReports','actionToWidgetize':'getCustomReport', 'idCustomReport': '7', 'flat': 1},
    ];

    var i = 0, widgetToCheck;
    for (i; i < widgetsToCheck.length; i++) {
        (function (widgetToCheck) {
            if (widgetToCheck.moduleToWidgetize === 'CustomReports' && !hasCustomReports) {
                return;
            }

            var reportName = widgetToCheck.testName;
            delete widgetToCheck.testName;

            // we do only one extensive check for each plugin (event, customdimension, custom reports, ...)
            var onlyBasicCheck = widgetToCheck.onlyBasicCheck;
            delete widgetToCheck.onlyBasicCheck;

            var url = widgetBase;
            for (var j in widgetToCheck) {
                url += '&' + j + '=' + widgetToCheck[j];
            }

            it('should load table report ' + reportName, function (done) {
                captureWidget(done, 'report_' + reportName, function (page) {
                    page.load(url);
                });
            });

            if (!onlyBasicCheck) {
                var row = 'tr:first-child';
                if (reportName === 'actionDimension3' || reportName === 'eventActionName') {
                    row = 'tr:nth-child(2)';
                } else if (reportName === 'customReports3') {
                    row = 'tr:nth-child(3)';
                }

                it('should show row evolution for renamed label', function (done) {
                    captureDialog(done, 'report_' + reportName + '_row_evolution', function (page) {
                        page.mouseMove('tbody ' + row);
                        page.mouseMove('a.actionRowEvolution:visible'); // necessary to get popover to display
                        page.click('a.actionRowEvolution:visible');
                    });
                });

                it('should show segmented visitor log for renamed label', function (done) {
                    captureDialog(done, 'report_' + reportName + '_segmented_visitor_log', function (page) {
                        page.click('.ui-dialog .ui-dialog-titlebar-close');
                        page.mouseMove('table.dataTable tbody ' + row);
                        page.execCallback(function () {
                            page.webpage.evaluate(function(row) {
                                $('table.dataTable tbody ' + row + ' a.actionSegmentVisitorLog').click();
                            }, row);
                        }, 2000);
                        page.mouseMove('body');
                    });
                });

                it('should be possible to search for renamed label', function (done) {
                    captureWidget(done, 'report_' + reportName + '_search', function (page) {
                        page.click('.ui-dialog .ui-dialog-titlebar-close');
                        page.click('.dataTableAction.searchAction');
                        page.sendKeys('.searchAction .dataTableSearchInput', 'ren');
                        page.click('.searchAction .icon-search');
                    });
                });

                it('should load bar report' + reportName, function (done) {
                    captureWidget(done, 'report_' + reportName + '_bar', function (page) {
                        page.load(url + '&viewDataTable=graphVerticalBar');
                    });
                });
                it('should load pie report' + reportName, function (done) {
                    captureWidget(done, 'report_' + reportName + '_pie', function (page) {
                        page.load(url + '&viewDataTable=graphPie');
                    });
                });

                if (reportName === 'eventActionName') {
                    it('should load pivot report ' + reportName, function (done) {
                        captureWidget(done, 'report_' + reportName + '_pivoted', function (page) {
                            page.load(url + '&pivotBy=Events.EventName&pivotByColumn=nb_events');
                        });
                    });
                }
            }

        })(widgetsToCheck[i]);
    }

});