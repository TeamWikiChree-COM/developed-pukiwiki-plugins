<?php
// $Id: font.inc.php,v 1.0 2023/11/11 18:38:00 WikiChree.COM Team Exp $

function plugin_font_convert() {
    $args = func_get_args();
    $res = "";
    if (func_num_args() >= 2) {
        $src = array_pop($args);
        $c = 0;
        foreach ($args as $font) {
            if (!in_array($font, ['sans-serif', 'serif', 'system-ui', 'monospace', 'cursive', 'fantasy'])) {
                $args[$c] = "'" . $font . "'";
            }
            ++$c;
        }
        $fontfamily = htmlsc(implode(',', $args));
        $res = '<span style="font-family: ' . $fontfamily . ';" class="fontplugin">' . convert_html($src) . '</span>';
    }
  	return $res;
}

function plugin_font_inline() {
    $args = func_get_args();
    $res = "";
    if (func_num_args() >= 2) {
        $src = array_pop($args);
        $c = 0;
        foreach ($args as $font) {
            if (!in_array($font, ['sans-serif', 'serif', 'system-ui', 'monospace', 'cursive', 'fantasy'])) {
                $args[$c] = "'" . $font . "'";
            }
            ++$c;
        }
        $fontfamily = htmlsc(implode(',', $args));
        $res = '<span style="font-family: ' . $fontfamily . ';" class="fontplugin">' . $src . '</span>';
    }
  	return $res;
}