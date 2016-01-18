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

    /**
     * Allow lists to be nested by default.
     */
    public function testDirectNestList() {
        $html = <<<HTML
<ul>
  <li>one</li>
  <ol>
    <li>two</li>
  </ol>
</ul>
HTML;
        $this->assertFiltered($html, $html);
    }

    /**
     * Provide the elements for {@link testElements()}.
     *
     * @return array Returns an array for testing.
     */
    public function provideInvalidElements() {
        $elements = explode('-', 'applet-button-form-input-textarea-iframe-script-style-embed-object');
        $result = [];
        foreach ($elements as $element) {
            $result[$element] = [$element];
        }
        return $result;
    }

    /**
     * Test that default invalid elements are removed.
     *
     * @param string $element The element that should be removed.
     * @dataProvider provideInvalidElements
     */
    public function testInvalidElements($element) {
        $html = "<div><$element>hi</$element></div>";
        $this->assertFiltered('<div>hi</div>', $html);
    }

    /**
     * Test to make sure `javascript:` isn't allowed in an href.
     */
    public function testBadScheme() {
        $this->assertFiltered(
            '<a rel="nofollow" href="denied:javascript:alert(\'xss\')">click</a>',
            '<a href="javascript:alert(\'xss\')">click</a>'
        );
    }

    /**
     * Make sure duplicate ID checks aren't being done.
     */
    public function testAllowDuplicateIDs() {
        $this->assertFiltered(
            '<b id="x">one</b><i id="x">two</i>',
            '<b id="x">one</b><i id="x">two</i>'
        );
    }
}
