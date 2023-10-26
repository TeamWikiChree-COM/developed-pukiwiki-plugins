<?php
// $Id: google_form.inc.php,v 1.0 2023/10/26 21:19:01 WikiChree.COM Team $

/**
 * Google Form plugin
 *
 * Syntax: #google_form(google-form-url,width,height)
 *
 * @author Bing AI
 * @license CC0
 */ 
function plugin_google_form_convert()
{
    // Check arguments
    $args = func_get_args();
    if (count($args) < 1) return '#google_form Usage: #google_form(google-form-url,width,height)';
    if (!isset($args[1])) $args[1] = '640';
    if (!isset($args[2])) $args[2] = '480';
    
    list($url, $width, $height) = $args;

    // Check URL
    if (!preg_match('/^https:\/\/docs\.google\.com\/forms\/.*$/', $url)) {
        return '#google_form Invalid URL for Google Form';
    }

    // Generate HTML code
    $html = '<iframe src="' . htmlsc($url) . '" width="' . htmlsc($width) . '" height="' . htmlsc($height) . '" frameborder="0" marginheight="0" marginwidth="0">読み込んでいます…</iframe>';

    // Return HTML code
    return $html;
}