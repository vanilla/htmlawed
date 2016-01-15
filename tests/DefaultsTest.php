<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Htmlawed\Tests;

use Htmlawed;

/**
 * Test the default config and spec for {@link \Htmlawed::filter()}.
 *
 * Calling the HTML filter without overriding the config should offer reasonable protection.
 */
class DefaultsTest extends \PHPUnit_Framework_TestCase {
    protected function assertFiltered($expected, $html, $message = '') {
        $filtered = Htmlawed::filter($html);
        $this->assertSame($expected, $filtered, $message);
    }


    /**
     * Provide data for {@link testBalance()}
     *
     * @return array Returns a test array.
     */
    public function provideBalanceTests() {
        return [
            ['Hi <b>there', 'Hi <b>there</b>'],
            ['<i>What <b>me</i> worry</b>', '<i>What <b>me</b></i> worry'],
        ];
    }

    /**
     * Test the **balance** config setting.
     *
     * @param string $html The HTML to filter.
     * @param string $expected The expected filtered HTML.
     * @dataProvider provideBalanceTests
     */
    public function testBalance($html, $expected) {
        $this->assertFiltered($expected, $html);
    }

    /**
     * Provide data for {@link testCommentRemoval()}
     *
     * @return array Returns a test array.
     */
    public function provideCommentRemovalTests() {
        return [
            'normal' => ["<!-- comment -->\nNormal", "\nNormal"],
            'inline' => ["This is<!-- not --> it", "This is it"],
            'multiline' => ["<!-- Do\n  it -->now", "now"]
        ];
    }

    /**
     * Test the **comment** config setting when it is set to remove comments.
     *
     * @param string $html The HTML to filter.
     * @param string $expected The expected filtered HTML.
     * @dataProvider provideCommentRemovalTests
     */
    public function testCommentRemoval($html, $expected) {
        $filtered = Htmlawed::filter($html);
        $this->assertSame($expected, $filtered);
    }

    /**
     * CSS expressions should be stripped by default.
     */
    public function testCssExpressionStripping() {
        $html = '<span style="expression(alert(\'XSS\'))">foo</span>';
        $expected = '<span style=" (alert(\'XSS\'))">foo</span>';

        $filtered = Htmlawed::filter($html);
        $this->assertSame($expected, $filtered);
    }

    /**
     * Make sure that **deny_attribute** defaults to `on*`.
     */
    public function testDenyAttribute() {
        $this->assertFiltered(
            '<div>...</div>',
            '<div onload="alert(\'XSS\')" onclick="die()">...</div>'
        );
    }
}
