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
    var hasCustomReports = fs.existsSync(customReportsPath);

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

    async function captureSelector(screenshotName, test, selector)
    {
        await test();
        await page.waitForNetworkIdle();
        expect(await page.screenshotSelector(selector)).to.matchImage(screenshotName);
    }

    async function captureMenu(screenshotName, test)
    {
        await captureSelector(screenshotName, test, '#secondNavBar .menuTab.active');
    }

    async function capturePageTable(screenshotName, test)
    {
        await captureSelector(screenshotName, test, '#content');
    }

    async function captureDialog(screenshotName, test)
    {
        await captureSelector(screenshotName, test, '.ui-dialog');
    }

    async function captureModal(screenshotName, test)
    {
        await test();
        await page.waitForNetworkIdle();

        pageWrap = await page.$('.modal.open');
        expect(await pageWrap.screenshot()).to.matchImage(screenshotName);
    }

    async function captureWidget(screenshotName, test)
    {
        await captureSelector(screenshotName, test, 'body');
    }

    /**
     * DASHBOARD + MENU
     */

    async function openMenuItem(page, menuItem)
    {
        var selector = '#secondNavBar .navbar a:contains('+ menuItem + '):first';
        await (await page.jQuery(selector)).click();
        await page.waitForTimeout(150);
        if (menuItem === 'Custom Reports') {
            await page.click('#secondNavBar .navbar .menuTab.active .menuDropdown .title');
        }
        await page.waitForTimeout(150);
        await page.mouse.move(0, 0);
    }

    it('should load the dashboard menu correctly with translations', async function () {
        await captureMenu('menu_loaded_dashboards', async function () {
            await page.goto(reportBase + "category=Dashboard_Dashboard&subcategory=1");
            await openMenuItem(page, 'Behaviour');
            await openMenuItem(page, 'Dashboard');// we make sure dashboard is selected, even though it should be by default
        });
    });

    it('should load the visitors menu correctly with translations', async function () {
        await captureMenu('menu_loaded_visitors', async function () {
            await openMenuItem(page, 'Visitors');
        });
    });

    it('should load the behaviour menu correctly with translations', async function () {
        await captureMenu('menu_loaded_behaviour', async function () {
            await openMenuItem(page, 'Behaviour');
        });
    });

    if (hasCustomReports) {
        it('should load the custom reports menu correctly with translations', async function () {
            await captureSelector('menu_loaded_customreports', async function () {
                await openMenuItem(page, 'Custom Reports');
            }, '#secondNavBar .menuTab.active,#secondNavBar .navbar .menuTab.active .menuDropdown .items');
        });
    }

    it("should show the original dashboard name when trying to rename dashboard", async function () {
        await captureModal('dashboard_rename', async function () {
            await page.click('.dashboard-manager .title');
            await page.click('button[data-action=renameDashboard]');
            await page.waitForTimeout(300);
        });
    });

    /**
     * MANAGE SCREENS SHOULD SHOW ORIGINAL VALUE, NOT TRANSLATION
     */
    if (hasCustomReports) {
        it('should load the custom reports with their original name in the admin manage screen', async function () {
            await capturePageTable('manage_custom_reports_admin', async function () {
                await page.goto(manageBase + "module=CustomReports&action=manage");
            });
        });
        it('should load the custom reports with their original name in the report manage screen', async function () {
            await capturePageTable('manage_custom_reports_admin2', async function () {
                await page.goto(reportBase + "category=CustomReports_CustomReports&subcategory=CustomReports_ManageReports");
            });
        });
    }

    it('should load the custom dimensions with their original name in the admin manage screen', async function () {
        await capturePageTable('manage_custom_dimensions_admin', async function () {
            await page.goto(manageBase + "module=CustomDimensions&action=manage");
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

            it(`should load table report ${reportName}`, async function () {
                await captureWidget('report_' + reportName, async function () {
                    await page.goto(url);
                });
            });

            if (!onlyBasicCheck) {
                var row = 'tr:first-child';
                if (reportName === 'actionDimension3' || reportName === 'eventActionName') {
                    row = 'tr:nth-child(2)';
                } else if (reportName === 'customReports3') {
                    row = 'tr:nth-child(3)';
                }

                it(`should show row evolution for renamed label for report ${reportName}`, async function () {
                    await captureDialog('report_' + reportName + '_row_evolution', async function () {
                        await page.hover('tbody ' + row);
                        await (await page.jQuery('a.actionRowEvolution:visible')).hover(); // necessary to get popover to display
                        await (await page.jQuery('a.actionRowEvolution:visible')).click();
                        await page.waitForNetworkIdle();
                    });
                });

                it(`should show segmented visitor log for renamed label for report ${reportName}`, async function () {
                    await captureDialog('report_' + reportName + '_segmented_visitor_log', async function () {
                        await page.click('.ui-dialog .ui-dialog-titlebar-close');
                        await page.hover('table.dataTable tbody ' + row);
                        await page.evaluate(function(row) {
                            $('table.dataTable tbody ' + row + ' a.actionSegmentVisitorLog').click();
                        }, row);
                        await page.waitForNetworkIdle();
                        await page.mouse.move(0, 0);
                    });
                });

                it(`should be possible to search for renamed label for report ${reportName}`, async function () {
                    await captureWidget('report_' + reportName + '_search', async function () {
                        await page.click('.ui-dialog .ui-dialog-titlebar-close');
                        await page.type('.reportHeader__search .mtm-searchInput__input', 'ren');
                        // waits out the search debounce and the reload it triggers
                        await page.waitForNetworkIdle();
                    });
                });

                it(`should load bar report ${reportName}`, async function () {
                    await captureWidget('report_' + reportName + '_bar', async function () {
                        await page.goto(url + '&viewDataTable=graphVerticalBar');
                    });
                });
                it(`should load pie report ${reportName}`, async function () {
                    await captureWidget('report_' + reportName + '_pie', async function () {
                        await page.goto(url + '&viewDataTable=graphPie');
                    });
                });

                if (reportName === 'eventActionName') {
                    it(`should load pivot report ${reportName}`, async function () {
                        await captureWidget('report_' + reportName + '_pivoted', async function () {
                            await page.goto(url + '&pivotBy=Events.EventName&pivotByColumn=nb_events');
                        });
                    });
                }
            }

        })(widgetsToCheck[i]);
    }
});
