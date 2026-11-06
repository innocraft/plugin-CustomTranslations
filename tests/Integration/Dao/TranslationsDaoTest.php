<?php

/**
 * InnoCraft - the company of the makers of Matomo Analytics, the free/libre analytics platform
 *
 * @link https://www.innocraft.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTranslations\tests\Integration;

use Piwik\Common;
use Piwik\Plugins\CustomTranslations\Dao\TranslationsDao;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group CustomTranslations
 * @group TranslationsDaoTest
 * @group Plugins
 */
class TranslationsDaoTest extends IntegrationTestCase
{
    private $typeId = 'exampleId';

    /**
     * @var TranslationsDao
     */
    private $dao;

    public function setUp(): void
    {
        parent::setUp();

        $this->dao = new TranslationsDao();
        $this->dao->set($this->typeId, 'en', array('foo' => 'bar', 'baz' => 'buzz'));
    }

    public function test_get_returnsEmptyArrayWhenNoValueStored()
    {
        $translations = $this->dao->get('foo', 'en');
        $this->assertSame(array(), $translations);

        $translations = $this->dao->get($this->typeId, 'nz');
        $this->assertSame(array(), $translations);
    }

    public function test_get_returnsStoredValue()
    {
        $translations = $this->dao->get($this->typeId, 'en');
        $this->assertSame(array('foo' => 'bar', 'baz' => 'buzz'), $translations);
    }

    public function test_set_canStoreAnyKindOfKey()
    {
        $example = array('こんにちは.?#2!\'/<">' => 'bar');
        $this->dao->set($this->typeId, 'nz', $example);
        $translations = $this->dao->get($this->typeId, 'nz');
        $this->assertSame($example, $translations);
    }

    public function test_set_differentTypesAndLanguagesDifferentStorages()
    {
        $nz = array('nz1' => 'nz2');
        $de = array('de1' => 'de2');
        $de2 = array('de10' => 'de20');
        $this->dao->set($this->typeId, 'nz', $nz);
        $this->dao->set($this->typeId, 'de', $de);
        $this->dao->set('type2', 'de', $de2);

        $this->assertSame($nz, $this->dao->get($this->typeId, 'nz'));
        $this->assertSame($de, $this->dao->get($this->typeId, 'de'));
        $this->assertSame($de2, $this->dao->get('type2', 'de'));
    }


    public function test_set_overwritesValue()
    {
        $nz = array('nz1' => 'nz2');
        $de = array('de1' => 'de2');

        $this->dao->set($this->typeId, 'nz', $nz);
        $this->assertSame($nz, $this->dao->get($this->typeId, 'nz'));

        $this->dao->set($this->typeId, 'nz', $de);
        $this->assertSame($de, $this->dao->get($this->typeId, 'nz'));
    }

    public function test_set_canClearValueWithEmptyArray()
    {
        $nz = array('nz1' => 'nz2');

        $this->dao->set($this->typeId, 'nz', $nz);
        $this->assertSame($nz, $this->dao->get($this->typeId, 'nz'));

        // clear value with empty array
        $this->dao->set($this->typeId, 'nz', array());
        $this->assertSame(array(), $this->dao->get($this->typeId, 'nz'));
    }

    public function test_set_canClearValueWithEmptyArrayFalse()
    {
        $nz = array('nz1' => 'nz2');

        $this->dao->set($this->typeId, 'nz', $nz);
        $this->dao->set($this->typeId, 'fr', $nz);
        $this->assertNotEmpty($this->dao->get($this->typeId, 'nz'));
        $this->assertNotEmpty($this->dao->get($this->typeId, 'fr'));

        $this->dao->set($this->typeId, 'nz', false);
        $this->assertSame(array(), $this->dao->get($this->typeId, 'nz'));
        // others still exist
        $this->assertNotEmpty($this->dao->get($this->typeId, 'fr'));
    }

    public function test_set_throwsExceptionWhenInvalidValues()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('$translations needs to be an array');
        $this->dao->set($this->typeId, 'nz', 'test');
    }

    public function test_set_allowsTranslationContainingHtml()
    {
        $example = array('foo' => '<b>Hello</b><br /><i>World</i>');

        $this->dao->set($this->typeId, 'nz', $example);

        $this->assertSame($example, $this->dao->get($this->typeId, 'nz'));
    }

    public function test_set_allowsTranslationContainingAngleBracketsAsText()
    {
        $example = array('foo' => '1 < 2 and 3 > 2');

        $this->dao->set($this->typeId, 'nz', $example);

        $this->assertSame($example, $this->dao->get($this->typeId, 'nz'));
    }

    public function test_set_throwsExceptionWhenTranslationContainsDisallowedHtmlTag()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Translation values can only contain a small set of formatting tags.');

        $this->dao->set($this->typeId, 'nz', array('foo' => '<script>alert(1)</script>'));
    }

    public function test_set_throwsExceptionWhenTranslationContainsHtmlAttributes()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Translation values cannot contain HTML attributes.');

        $this->dao->set($this->typeId, 'nz', array('foo' => '<b onclick="alert(1)">Hello</b>'));
    }

    public function test_set_throwsExceptionWhenTranslationContainsUnclosedHtmlTag()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Translation values can only contain a small set of formatting tags.');

        $this->dao->set($this->typeId, 'nz', array('foo' => '<img src=x onerror=alert(1)'));
    }

    public function test_set_allowsSanitizedTranslationContainingHtml()
    {
        // values passed through the API are sanitized before they reach the dao
        $example = array('foo' => Common::sanitizeInputValue('<b>Hello</b><br /><i>World</i>'));

        $this->dao->set($this->typeId, 'nz', $example);

        $this->assertSame($example, $this->dao->get($this->typeId, 'nz'));
    }

    public function test_set_allowsSanitizedTranslationContainingAngleBracketsAsText()
    {
        $example = array('foo' => Common::sanitizeInputValue('1 < 2 and 3 > 2'));

        $this->dao->set($this->typeId, 'nz', $example);

        $this->assertSame($example, $this->dao->get($this->typeId, 'nz'));
    }

    public function test_set_throwsExceptionWhenSanitizedTranslationContainsDisallowedHtmlTag()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Translation values can only contain a small set of formatting tags.');

        $this->dao->set($this->typeId, 'nz', array('foo' => Common::sanitizeInputValue('<script>alert(1)</script>')));
    }

    public function test_set_throwsExceptionWhenSanitizedTranslationContainsHtmlAttributes()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Translation values cannot contain HTML attributes.');

        $this->dao->set($this->typeId, 'nz', array('foo' => Common::sanitizeInputValue('<b onclick="alert(1)">Hello</b>')));
    }
}
