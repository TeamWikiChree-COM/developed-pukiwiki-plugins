<?php
// $Id: flex_box.inc.php,v 1.0 2026/11/12 21:50:00 Team WikiChree.COM Exp $

/**
 * flex_boxプラグイン
 *
 * #flex_container 内で用いる。書式は flex_container.inc.php を参照
 *
 *   #flex_box(幅){{
 *   中身
 *   }}
 *
 * 幅は数値 (px) / 数値+px / % で、省略すると #flex_container の列幅の初期値
 */

if (! defined('PLUGIN_FLEX_JUSTIFY')) {
	define('PLUGIN_FLEX_JUSTIFY', 'center flex-start flex-end space-between space-around space-evenly');
}

if (! defined('PLUGIN_FLEX_STYLE')) {
	define('PLUGIN_FLEX_STYLE', <<<'EOD'
.wikic-flex{display:flex;flex-wrap:wrap;box-sizing:border-box;width:100%;margin:.5em 0}
.wikic-flex-nowrap{flex-wrap:nowrap}
.wikic-flex>.wikic-flex-box{flex:0 1 var(--wikic-flex-basis,auto);min-width:0;box-sizing:border-box;padding:.25em .5em}
.wikic-flex-stretch>.wikic-flex-box{flex-grow:1}
EOD
	);
}

if (! function_exists('plugin_flex_length')) {
	/**
	 * 幅の指定を CSS の値にする。px の数値・単位付き・% だけを受け付ける。不正なら空文字。
	 */
	function plugin_flex_length($value)
	{
		$value = trim($value);
		if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $value)) return $value . 'px';
		if (preg_match('/^[0-9]+(\.[0-9]+)?(px|%)$/', $value)) return $value;
		return '';
	}
}

if (! function_exists('plugin_flex_take_body')) {
	/**
	 * 引数から本文 ({{ }} の中身。末尾が "\r") を取り出す。無ければ null。
	 */
	function plugin_flex_take_body(& $args)
	{
		if (count($args) > 0 && substr(end($args), -1) === "\r") {
			return array_pop($args);
		}
		return null;
	}
}

if (! function_exists('plugin_flex_add_style')) {
	/**
	 * Flexbox 用のスタイルを、ページに1回だけ出力する。
	 */
	function plugin_flex_add_style()
	{
		static $done = false;
		if ($done) return;
		$done = true;
		global $head_tags;
		$head_tags[] = '<style>' . PLUGIN_FLEX_STYLE . '</style>';
	}
}

function plugin_flex_box_convert() {
	$args = func_get_args();
	$body = plugin_flex_take_body($args);
	if ($body === null) {
		return '<p>#flex_box: 本文を {{ と }} で囲んでください。</p>';
	}

	$width = isset($args[0]) ? plugin_flex_length($args[0]) : '';
	$style = ($width !== '') ? ' style="flex-basis:' . $width . ';"' : '';

	plugin_flex_add_style();

	return '<div class="wikic-flex-box"' . $style . '>' . "\n"
		. convert_html(str_replace("\r", "\n", $body))
		. '</div>' . "\n";
}
