<?php
// plugin/nobr.inc.php
// 謎の人狼Y さま (with Gemini) より CC0 として提供されたコード

function plugin_nobr_inline() {
    $args = func_get_args();
    return '<span style="white-space:nowrap;">' . implode(',', $args) . '</span>';
}

function plugin_nobr_convert() {
    $args = func_get_args();
    $end = end($args);

    if (substr($end, -1) == "\r") {
        $body = array_pop($args);
        $body = str_replace("\r", "\n", $body);
    } else {
        $body = $args;
    }

    return '<div style="white-space:nowrap;">' . convert_html($body) . '</div>';
}
