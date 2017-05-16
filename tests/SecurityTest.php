<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Htmlawed\Tests;

use Htmlawed;

/**
 * Test issues that come up on Github.
 */
class SecurityTest extends \PHPUnit_Framework_TestCase {

    /**
     * Test deprecated attribute.
     *
     */
    public function testDeprecatedAttributeInjection() {
        $html = '<div align="center;display:block;"></div>';
        $expected = '<div style="text-align: centerdisplayblock;"></div>';

        $filtered = Htmlawed::filter($html);
        $this->assertSame($expected, $filtered);
    }
}
