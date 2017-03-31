<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license LGPL-3.0
 */

/**
 * A class wrapper for the htmLawed library.
 */
class Htmlawed {
    /// Methods ///

    public static $defaultConfig = [
        'anti_link_spam' => ['`.`', ''],
        'balance' => 1,
        'cdata' => 3,
        'comment' => 1,
        'css_expression' => 0,
        'deny_attribute' => 'on*,style',
        'direct_list_nest' => 1,
        'elements' => '*-applet-button-form-input-textarea-iframe-script-style-embed-object',
        'keep_bad' => 0,
        'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
        'unique_ids' => 0,
        'valid_xhtml' => 0,
    ];

    public static $defaultSpec = [
        'object=-classid-type, -codebase',
        'embed=type(oneof=application/x-shockwave-flash)'
    ];

    /**
     * Filters a string of html with the htmLawed library.
     *
     * @param string $html The text to filter.
     * @param array|null $config Config settings for the array.
     * @param string|array|null $spec A specification to further limit the allowed attribute values in the html.
     * @return string Returns the filtered html.
     * @see http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawed_README.htm
     */
    public static function filter($html, array $config = null, $spec = null) {
        require_once __DIR__.'/htmLawed/htmLawed.php';

        if ($config === null) {
            $config = self::$defaultConfig;
        }

        if (isset($config['spec']) && !$spec) {
            $spec = $config['spec'];
        }

        if ($spec === null) {
            $spec = static::$defaultSpec;
        }

        $filtered = htmLawed($html, $config, $spec);
        // Replace empty paragraphs.
        // Htmlawed balances <p><pre><code>Code</code></pre></p> into <p></p><pre><code>Code</code></pre>
        $filtered = str_replace('<p></p>', null, $filtered);
        return $filtered;
    }


    /**
     * Filter a string of html so that it can be put into an rss feed.
     *
     * @param $html The html text to fitlter.
     * @return string Returns the filtered html.
     * @see Htmlawed::filter().
     */
    public static function filterRSS($html) {
        $config = array(
            'anti_link_spam' => ['`.`', ''],
            'comment' => 1,
            'cdata' => 3,
            'css_expression' => 1,
            'deny_attribute' => 'on*,style,class',
            'elements' => '*-applet-form-input-textarea-iframe-script-style-object-embed-comment-link-listing-meta-noscript-plaintext-xmp',
            'keep_bad' => 0,
            'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
            'valid_xml' => 2,
            'balance' => 1
        );
        $spec = static::$defaultSpec;

        $result = static::filter($html, $config, $spec);

        return $result;
    }
}
