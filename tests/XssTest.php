<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Htmlawed\Tests;

use Htmlawed\Htmlawed;


class XssTest extends \PHPUnit_Framework_TestCase {
    public function assertNoScript($str, $message = '') {
        self::assertFalse(
            (bool)preg_match('`<\s*/?\s*script`i', $str),
            $message
        );
    }

    /**
     * Test a malformed href including a script element.
     *
     * @param array $config
     * @param string $spec
     * @dataProvider provideConfigs
     */
//    public function testScriptInHref($config = [], $spec = '') {
//        $str = <<<EOT
//<a href="<script foo=''">alert('xss')</a>
//EOT;
//
//        $filtered = Htmlawed::filter($str, $config, $spec);
//
//        $this->assertNoScript($filtered);
//    }

    /**
     * @param string $str
     * @param array $config
     * @param string $spec
     * @dataProvider provideXss
     */
    public function testIdempotence($str, $config = [], $spec = '') {
        $filtered = Htmlawed::filter($str, $config, $spec);
        $filteredAgain = Htmlawed::filter($filtered, $config, $spec);
        $this->assertEquals($filtered, $filteredAgain);
    }

    /**
     * @param string $str
     * @param array $config
     * @param string $spec
     * @dataProvider provideXss
     */
    public function testNoScript($str, $config = [], $spec = '') {
        $filtered = Htmlawed::filter($str, $config, $spec);
        $this->assertNoScript($filtered);
    }

    public function provideConfigs() {
        $result = [
            'safe' => [['safe' => 1], ''],
        ];

        $result['vanilla'] = [
            [
                'anti_link_spam' => ['`.`', ''],
                'comment' => 1,
                'cdata' => 3,
                'css_expression' => 1,
                'deny_attribute' => 'on*',
                'unique_ids' => 0,
                'elements' => '*-applet-form-input-textarea-iframe-script-style-embed-object',
                'keep_bad' => 0,
                'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
                'valid_xhtml' => 0,
                'direct_list_nest' => 1,
                'balance' => 1
            ],
            'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash)'
        ];

        return $result;
    }

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

    public function provideXss() {
        return array_merge($this->provideRSnake(), $this->provideEvasion());
    }

    public function provideRSnake() {
        return $this->combineProviders([$this, 'provideRSnakeTests'], [$this, 'provideConfigs']);
    }

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
 