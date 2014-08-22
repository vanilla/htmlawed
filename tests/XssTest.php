<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license LGPL-3.0
 */

namespace Htmlawed\Tests;

use Htmlawed;
use pQuery;

/**
 * Test some xss strings.
 */
class XssTest extends \PHPUnit_Framework_TestCase {
    /**
     * Assert that a string doesn't have a script tag.
     *
     * @param string $str The string to test.
     * @param string $message The error message if a script tag is found.
     */
    public function assertNoScript($str, $message = '') {
        self::assertFalse(
            (bool)preg_match('`<\s*/?\s*script`i', $str),
            $message
        );
    }

    /**
     * Run a snippet of html with phantomjs.
     *
     * @param string $html The html to run.
     * @param array &$result The result of the execution.
     * @param int $resultCode The result code of the execution.
     * @see exec()
     */
    protected function runPhantomJs($html, &$result = null, &$resultCode = null) {
        file_put_contents(__DIR__.'/fixtures/phantomjs.html', $html);

        chdir(__DIR__.'/fixtures');
        exec('phantomjs phantom.js', $result, $resultCode);
    }

    /**
     * Test a malformed href including a script element.
     *
     * @param array $config
     * @param string $spec
     * @dataProvider provideConfigs
     */
    public function testScriptInHref($config = [], $spec = '') {
        $str = <<<EOT
<a href="<script foo=''">alert('xss')</a>
EOT;

        if ($config === 'rss') {
            $filtered = Htmlawed::filterRSS($str);
        } else {
            $filtered = Htmlawed::filter($str, $config, $spec);
        }

        $this->assertNoScript($filtered);
    }

    /**
     * Test that filtering a string twice returns the same strings.
     *
     * @param string $str The string to test.
     * @param array $config The htmlawed config.
     * @param string $spec The htmlawed spec.
     * @dataProvider provideXss
     */
    public function testIdempotence($str, $config = [], $spec = '') {
        if ($config === 'rss') {
            $filtered = Htmlawed::filterRSS($str);
        } else {
            $filtered = Htmlawed::filter($str, $config, $spec);
        }
        if ($config === 'rss') {
            $filteredAgain = Htmlawed::filterRSS($filtered);
        } else {
            $filteredAgain = Htmlawed::filter($filtered, $config, $spec);
        }
        $this->assertEquals($filtered, $filteredAgain);
    }

    /**
     * Test that the xss test strings don't have a script tag.
     *
     * @param string $str The string to test.
     * @param array $config The htmlawed config.
     * @param string $spec The htmlawed spec.
     * @dataProvider provideXss
     */
    public function testNoScript($str, $config = [], $spec = '') {
        if ($config === 'rss') {
            $filtered = Htmlawed::filterRSS($str);
        } else {
            $filtered = Htmlawed::filter($str, $config, $spec);
        }
        $this->assertNoScript($filtered);
    }

    /**
     * Test the xss strings against a {@link pQuery} dom construction.
     *
     * @param string $str The string to test.
     * @param array $config The htmlawed config.
     * @param string $spec The htmlawed spec.
     * @dataProvider provideXss
     */
    public function testDom($str, $config = [], $spec = '') {
        if ($config === 'rss') {
            $filtered = Htmlawed::filterRSS($str);
        } else {
            $filtered = Htmlawed::filter($str, $config, $spec);
        }

        $q = pQuery::parseStr($filtered);

        // Test event handlers.
        $ons = ['onclick', 'onmouseover', 'onload', 'onerror'];
        foreach ($ons as $on) {
            if (strpos($filtered, $on) !== false) {
                $elems = $q->query("*[$on]");
                $this->assertSame(0, $elems->count(), "Filtered still has an $on attribute.");
            }
        }

        // Test bad elements.
        if (count($config) == 1 && !empty($config['safe'])) {
            $elems = ['applet', 'iframe', 'script', 'embed', 'object'];
        } else {
            $elems = ['applet', 'form', 'input', 'textarea', 'iframe', 'script', 'style', 'embed', 'object'];
        }
        foreach ($elems as $elem) {
            $count = $q->query($elem)->count();
            if ($count > 0) {
                $foo = 'bar';
            }
            $this->assertSame(0, $q->query($elem)->count(), "Filtered still has an $elem element.");
        }

        // Look for javascript: hrefs.
        foreach ($q->query('*[href]') as $node) {
            /* @var pQuery\IQuery $node */
            $href = $node->attr('href');
            $this->assertStringStartsNotWith('javascript', $href);
        }
    }

    /**
     * Test to make sure that the phantomjs tests can even work.
     */
    public function testPhantomJs() {
        // Check for phantomjs first.
        exec('phantomjs --version', $version, $resultCode);
        if ($resultCode !== 0) {
            $this->markTestSkipped("PhantomJs not installed.");
        }

        $xss = <<<XSS
<html>
<body>
<script>alert("xss")</script>
</body>
</html>
XSS;

        $this->runPhantomJs($xss, $result, $resultCode);
        $this->assertSame(0, $resultCode);
        $this->assertEquals(['Loading', 'ALERT: xss', 'Loaded'], $result);
    }

    /**
     * Test the filtered xss attack vectors with phantomjs.
     *
     * @depends testPhantomJs
     */
    public function testFilterWithPhantomJs() {
        $xss = $this->provideXss();

        $result = '';
        foreach ($xss as $key => $str) {
            $filtered = Htmlawed::filter($str[0]);
            $result .= '<h2>'.htmlspecialchars($key)."</h2>\n<div>$filtered</div>\n\n";
        }

        $result = <<<HTML
<html>
<body>
<h1>Filtered XSS</h1>

$result
</body>
</html>
HTML;

        $this->runPhantomJs($result, $output, $resultCode);
        $this->assertSame(0, $resultCode, "Phantomjs failed.");
        $this->assertSame(['Loading', 'Loaded'], $output);
    }

    /**
     * Provide some htmlawed configs.
     *
     * @return array Returns the configs.
     */
    public function provideConfigs() {
        $result = [
            'default' => [null, null],
            'safe' => [['safe' => 1], ''],
            'rss' => ['rss', null]
        ];

        return $result;
    }

    /**
     * Combine two providers into one.
     *
     * @param callable $a The first provider.
     * @param callable $b The second provider.
     * @return array Returns the combined providers.
     */
    protected function combineProviders(callable $a, callable $b) {
        $a_items = call_user_func($a);
        $b_items = call_user_func($b);

        $result = [];
        foreach ($a_items as $a_key => $a_row) {
            foreach ($b_items as $b_key => $b_row) {
                $result[$a_key.': '.$b_key] = array_merge($a_row, $b_row);
            }
        }
        return $result;
    }

    /**
     * Provide all the xss strings.
     *
     * @return array Returns an array of xss strings.
     */
    public function provideXss() {
        return array_merge($this->provideRSnake(), $this->provideEvasion());
    }

    /**
     * Provide the RSnake strings.
     *
     * @return array Returns the RSnake strings.
     */
    public function provideRSnake() {
        return $this->combineProviders([$this, 'provideRSnakeTests'], [$this, 'provideConfigs']);
    }

    /**
     * Provide the xss evasion strings.
     *
     * @return array Returns the xss evasion strings.
     */
    public function provideEvasion() {
        $result = $this->combineProviders([$this, 'provideEvasionTests'], [$this, 'provideConfigs']);
        return $result;
    }


    /**
     * Provides a list of hacks from RSnake (special thanks).
     *
     * @see https://fuzzdb.googlecode.com/svn-history/r186/trunk/attack-payloads/xss/xss-rsnake.txt
     */
    public function provideRSnakeTests() {
        $lines = explode("\n\n", file_get_contents(__DIR__.'/fixtures/xss-rsnake.txt'));
        array_walk($lines, function(&$line) {
            $line = [trim($line)];
        });

        return $lines;
    }

    /**
     * Provide a list of hacks from owasp.org (special thanks).
     *
     * @see https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
     */
    public function provideEvasionTests() {
        $lines = explode("\n\n", file_get_contents(__DIR__.'/fixtures/xss-evasion.txt'));

        $result = [];
        foreach ($lines as $line) {
            list($key, $value) = explode("\n", $line, 2);
            $result[$key] = [$value];
        }

        return $result;
    }
}
 