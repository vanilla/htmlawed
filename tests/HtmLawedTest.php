<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license LGPLv3
 */

namespace Htmlawed\Tests;

use Htmlawed;

/**
 * Run some test cases from htmLawed_TESTCASE.txt file.
 */
class HtmLawedTest extends \PHPUnit_Framework_TestCase {

    /**
     * Test some HTML attribute tests.
     *
     * @param string $html The HTML to filter.
     * @param string $expected The expected filtered result.
     * @dataProvider provideAttributeTests
     */
    public function testAttributes($html, $expected) {
        $config = Htmlawed::$defaultConfig;
        unset($config['elements']);

        $this->assertSame($expected, Htmlawed::filter($html, $config));
    }

    /**
     * Provide the attribute tests from htmLawed_TESTCASE.txt.
     *
     * @return array Returns a data provider array.
     */
    public function provideAttributeTests() {
        $r = [
            ['<a lang="en" xml:lang="en"></a>', '<a lang="en" xml:lang="en"></a>'],
            ['<input type="text" disabled />', '<input type="text" disabled="disabled" />'],
            ['<input type="text" disabled="DISABLED" />', '<input type="text" disabled="disabled" />'],
            ['<input type="text" disabled="1" />', '<input type="text" disabled="disabled" />'],
            ['<img />', '<img src="src" alt="image" />'],
            ['<img alt="image" />', '<img alt="image" src="src" />'],
            ['<a id=id1 name=xy>a</a>', '<a id="id1" name="xy">a</a>'],
            ['<a id=\'id2\' name="xy">a</a>', '<a id="id2" name="xy">a</a>'],
            ['<a   id=\' id3 \' name = "n"  >a</a>', '<a id="id3" name="n">a</a>'],
        ];

        return $r;
    }

    /**
     * Test some XSS test cases.
     *
     * @param string $html The HTML to check.
     * @param string $expected The expected clean data.
     * @dataProvider provideXss
     */
    public function testXss($html, $expected) {
        $config = Htmlawed::$defaultConfig;
        $config['anti_link_spam'] = 0;
        $config['deny_attribute'] = 'on*';
        
        $this->assertSame($expected, Htmlawed::filter($html, $config));
    }

    /**
     * Provide the XSS tests from htmLawed_TESTCASE.txt.
     *
     * There are some test cases commented out because they result in safe, but unclean style tags. This is possibly an
     * area to look at for improving the actual library.
     *
     * @return array Returns a data provider.
     */
    public function provideXss() {
        $r = [
            0 => ['<img alt="<img onmouseover=confirm(1)//"<"">
\'\';!--"<xss>=&{()}', '<img alt="&lt;img onmouseover=confirm(1)//" src="src" />
\'\';!--"=&amp;{()}'],
            1 => ['<img src="javascript%3Aalert(\'xss\');" />', '<img src="denied:javascript%3Aalert(\'xss\');" alt="image" />'],
            2 => ['<img src="javascript:alert(\'xss\');" />', '<img src="denied:javascript:alert(\'xss\');" alt="image" />'],
            3 => ['<img src="java script:alert(\'xss\');" />', '<img src="denied:java script:alert(\'xss\');" alt="image" />'],
            4 => ['<img
src=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41; />', '<img src="denied:&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;" alt="image" />'],
//            ['<font color=\'#FF6699"onmouseover="alert(1)//\'>test</font>', ''],
            5 => ['<font color=\'<img//onerror="alert`www.ptsecurity.com`"src=Psych0tr1a\'>
<div style="javascript:alert(\'xss\');"></div>', '<span style="color: img//onerror=\'alert`www.ptsecurity.com`\'src=Psych0tr1a;">
</span>'],
            6 => ['<div style="background-image:url(javascript:alert(\'xss\'));"></div>', '<div style="background-image:url(denied:javascript:alert(\'xss\'));"></div>'],
            7 => ['<div style="background-image:url(&quot;javascript:alert(\'xss\')&quot; );"></div>', '<div style="background-image:url(&quot;denied:javascript:alert(\'xss\')&quot; );"></div>'],
            8 => ['<!--[if gte IE 4]><script>alert(\'xss\');</script><![endif]-->', ''],
            9 => ['<script a=">" src="http://ha.ckers.org/xss.js"></script>', '" src="http://ha.ckers.org/xss.js"&gt;'],
            10 => ['<div style="background-image: &#117;r&#x6C;(\'js&#58;xss\'&#x29;"></div>', '<div style="background-image: url(\'denied:js&#58;xss\')"></div>'],
            11 => ['<a style=";-moz-binding:url(http://lukasz.pilorz.net/xss/xss.xml#xss)" href="http://example.com">test</a>', '<a style=";-moz-binding:url(denied:http://lukasz.pilorz.net/xss/xss.xml#xss)" href="http://example.com">test</a>'],
            12 => ['<a href="http://x&x=%22+style%3d%22background-image%3a+expression%28alert
%28%27xss%3f%29%29">x</a>', '<a href="http://x&amp;x=%22+style%3d%22background-image%3a+expression%28alert %28%27xss%3f%29%29">x</a>'],
            13 => ['<a href="\xE2\x80\x83javascript:alert(123)">link</a>', '<a href="denied:\xE2\x80\x83javascript:alert(123)">link</a>'],
            14 => ['<a style=color:expr/*comment*/ession(alert(document.domain))>xxx</a>', '<a style="color:expr comment*/ession(alert(document.domain))">xxx</a>'],
            15 => ['<a href="xxx" style="background: exp&#x72;ession(alert(\'xss\'));">xxx</a>', '<a href="xxx" style="background:  (alert(\'xss\'));">xxx</a>'],
            16 => ['<a href="xxx" style="background: &#101;xpression(alert(\'xss\'));">xxx</a>', '<a href="xxx" style="background:  (alert(\'xss\'));">xxx</a>'],
//            17 => ['<a href="xxx" style="background: %45xpression(alert(\'xss\'));">xxx</a>', ''],
//            18 => ['<a href="xxx" style="background:/**/expression(alert(\'xss\'));">xxx</a>', ''],
//            19 => ['<a href="xxx" style="background:/**/&#69;xpression(alert(\'xss\'));">xxx</a>', ''],
//            20 => ['<a href="xxx" style="background:/**/Exp&#x72;ession(alert(\'xss\'));">xxx</a>', ''],
//            21 => ['<a href="xxx" style="background: expr%45ssion(alert(\'xss\'));">xxx</a>', ''],
//            22 => ['<a href="xxx" style="background: exp/* */ression(alert(\'xss\'));">xxx</a>', ''],
//            23 => ['<a href="xxx" style="background: exp /* */ression(alert(\'xss\'));">xxx</a>', ''],
//            24 => ['<a href="xxx" style="background: exp/ * * /ression(alert(\'xss\'));">xxx</a>', ''],
//            25 => ['<a href="xxx" style="background:/* x */expression(alert(\'xss\'));">xxx</a>', ''],
//            26 => ['<a href="xxx" style="background:/* */ */expression(alert(\'xss\'));">xxx</a>', ''],
//            27 => ['<a href="x" style="width: /****/**;;;;;;*/expression/**/(alert(\'xss\'));">x</a>', ''],
//            28 => ['<a href="x" style="padding:10px; background:/**/expression(alert(\'xss\'));">x</a>', ''],
//            29 => ['<a href="x" style="background: huh /* */ */expression(alert(\'xss\'));">x</a>', ''],
//            30 => ['<a href="x" style="background:/**/expression(alert(\'xss\'));background:/**/expression(alert(\'xss\'));">x</a>', ''],
//            31 => ['exp/*<a style=\'no\xss:noxss("*//*");xss:&#101;x&#x2F;*XSS*//*/*/pression(alert("XSS"))\'>x</a>', ''],
//            32 => ['<a style="background:&#69;xpre\ssion(alert(\'xss\'));">hi</a>', ''],
//            33 => ['<a style="background:expre&#x5c;ssion(alert(\'xss\'));">hi</a>', ''],
//            34 => ['<a style="color: \\0065 \\0078 \\0070 \\0072 \\0065 \\0073 \\0073 \\0069 \\006f \\006e \\0028 \\0061 \\006c \\0065 \\0072 \\0074 \\0028 \\0031 \\0029 \\0029">test</a>', ''],
//            35 => ['<a style="xss:e&#92;&#48;&#48;&#55;&#56;pression(window.x?0:(alert(/XSS/),window.x=1));">hi</a>', ''],
            36 => ['<a style="background:url(\'java
script:eval(document.all.mycode.expr)\')">hi</a>', '<a style="background:url(\'denied:java script:eval(document.all.mycode.expr)\')">hi</a>'],
        ];
        
        return $r;
    }
}
