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
}
