<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Htmlawed\Tests;

use Htmlawed;

/**
 * Test issues that come up on Github.
 */
class IssuesTest extends \PHPUnit_Framework_TestCase {
    /**
     * Test overly aggressive span stripping.
     *
     * @link https://github.com/vanilla/htmlawed/issues/1
     */
    public function testAggressiveSpanStripping() {
        $html = '<span style="expression(alert(\'XSS\')">foo</span>';
        $expected = 'foo';

        $config = ['deny_attribute' => 'on*,style'] + Htmlawed::$defaultConfig;
        $filtered = Htmlawed::filter($html, $config);
        $this->assertSame($expected, $filtered);
    }
}
