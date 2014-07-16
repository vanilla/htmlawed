<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license LGPL-3.0
 */

namespace Htmlawed\Tests;

use Htmlawed;

/**
 * Unit tests for valid html.
 */
class ValidTest extends \PHPUnit_Framework_TestCase {
    /**
     * Test that valid html does not change when filtered.
     *
     * @param $html The html to test.
     * @dataProvider provideValid
     */
    public function testValidNoChange($html) {
        $filtered = Htmlawed::filter($html);
        $this->assertEquals($html, $filtered);
    }

    public function testSpanStrip() {
        $html = <<<HTML
<span><h1>Don't strip this h1!</h1></span>
HTML;

        $filtered = Htmlawed::filter($html);
        $expected = '<h1>Don\'t strip this h1!</h1>';
        $this->assertSame($expected, $filtered);
    }

    public function provideValid() {
        $paths = glob(__DIR__.'/fixtures/valid/*.html');

        $result = [];
        foreach ($paths as $path) {
            $result[basename($path)] = [file_get_contents($path)];
        }
        return $result;
    }
}
 