<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Htmlawed\Tests;

use Htmlawed;
use PHPUnit\Framework\TestCase;

/**
 * Test issues that come up on Github.
 */
class SecurityTest extends TestCase {

    /**
     * Test deprecated attribute.
     *
     */
    public function testDeprecatedAttributeInjection() {
        $html = '<div align="1&#x3b; background&#x3a; red">dd</div>';
        $expected = '<div style="text-align: 1x3b backgroundx3a red;">dd</div>';

        $filtered = Htmlawed::filter($html);
        $this->assertSame($expected, $filtered);
    }

    /**
     * Test that data attributes are properly sanitized.
     *
     * @link https://higherlogic.atlassian.net/browse/VNLA-5166
     *
     * @return void
     */
    public function testOddlyNestedDataAttribute() {
        $html = <<<HTML
<a data-<a  <a data-%a0id='z <b onmouseover=self[&apos;con&apos;+&apos;firm&apos;](&apos;hehe&apos;) style=position:fixed;top:0;right:0;bottom:0;left:0;background:rgba(0, 0, 0, 0.0);z-index: 5000;'href="#xss">click here</a>
HTML;
        $expected = "a data-click here";

        $filtered = Htmlawed::filter($html);
        $this->assertSame($expected, $filtered);
    }
}
