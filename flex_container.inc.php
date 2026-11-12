<?php
// $Id: flex_container.inc.php,v 1.0 2026/11/12 21:52:00 Team WikiChree.COM Exp $

/**
 * flex_containerプラグイン
 *
 *   #flex_container(配置方法,列幅の初期値,折り返し方法){{{
 *   #flex_box(幅){{
 *   1つ目の中身
 *   }}
 *   #flex_box(幅){{
 *   2つ目の中身
 *   }}
 *   }}}
 *
 * 引数はすべて省略できる。途中を飛ばすときは空にする (例: #flex_container(,400,nowrap))。
 *   配置方法     : center / flex-start / flex-end / space-between / space-around / space-evenly / stretch
 *   列幅の初期値 : 数値 (px) / 数値+px / %
 *                  各 #flex_box の既定の幅になる。#flex_box 側で幅を指定すればそちらが優先される。
 *   折り返し方法 : wrap (既定) / nowrap
 *
 * 値は許可リストと形式で検証し、それ以外は無視する。
 */

if (! defined('PLUGIN_FLEX_JUSTIFY')) {
	define('PLUGIN_FLEX_JUSTIFY', 'center flex-start flex-end space-between space-around space-evenly');
}

if (! defined('PLUGIN_FLEX_STYLE')) {
	define('PLUGIN_FLEX_STYLE', <<<'EOD'
.flex_container{display:flex;flex-wrap:wrap;box-sizing:border-box;width:100%;margin:.5em 0}
.flex_container_nowrap{flex-wrap:nowrap}
.flex_container>.flex_box{flex:0 1 var(--flex-basis,auto);min-width:0;box-sizing:border-box;padding:.25em .5em}
.flex_container_stretch>.flex_box{flex-grow:1}
EOD
	);
}

if (! function_exists('plugin_flex_length')) {
	/**
	 * 幅の指定を CSS の値にする。px の数値・単位付き・% だけを受け付ける。不正なら空文字。
	 */
	function plugin_flex_length($value) {
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
	function plugin_flex_take_body(& $args) {
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
	function plugin_flex_add_style() {
		static $done = false;
		if ($done) return;
		$done = true;
		global $head_tags;
		$head_tags[] = '<style>' . PLUGIN_FLEX_STYLE . '</style>';
	}
}

function plugin_flex_container_convert() {
	$args = func_get_args();
	$body = plugin_flex_take_body($args);
	if ($body === null) 
		return '<p>#flex_container: 本文を {{{ と }}} で囲んでください。</p>';

	$justify = '';
	$stretch = false;
	$basis   = '';
	$nowrap  = false;

	// 配置方法
	if (isset($args[0])) {
		$v = strtolower(trim($args[0]));
		if ($v === 'stretch') {
			$stretch = true;
		} else if (in_array($v, explode(' ', PLUGIN_FLEX_JUSTIFY), true)) {
			$justify = $v;
		}
	}

	// 列幅の初期値
	if (isset($args[1])) $basis = plugin_flex_length($args[1]);
	// 折り返し方法
	if (isset($args[2]) && strtolower(trim($args[2])) === 'nowrap') $nowrap = true;

	$class = 'flex_container' . ($nowrap ? ' flex_container_nowrap' : '') . ($stretch ? ' flex_container_stretch' : '');
	$style = '';
	if ($justify !== '') $style .= 'justify-content:' . $justify . ';';
	if ($basis !== '') $style .= '--flex-basis:' . $basis . ';';

	plugin_flex_add_style();

	return '<div class="' . $class . '"' . ($style !== '' ? ' style="' . $style . '"' : '') . '>' . "\n"
		. convert_html(str_replace("\r", "\n", $body))
		. '</div>' . "\n";
}
