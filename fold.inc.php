<?php
// $Id: fold.inc.php,v 1.0 2026/11/12 21:47:00 Team WikiChree.COM Exp $

/**
 * foldプラグイン (折りたたみ)
 *
 *   #fold(もっと見る){{
 *   隠されていたテキスト
 *   複数行
 *   }}
 *
 * 引数は、閉じた状態で表示する見出しの文字列
 * 見出しの後ろに open を付けると、最初から開いた状態になる
 *
 * <details> 要素で組み立てるため JavaScript は不要で、ページ幅フルに表示される
 */

define('PLUGIN_FOLD_DEFAULT_TITLE', 'もっと見る');

define('PLUGIN_FOLD_STYLE', <<<'EOD'
.fold{display:block;box-sizing:border-box;width:100%;margin:.4em 0}
.fold>summary{-webkit-user-select:none;user-select:none;display:flex;align-items:center;gap:.5em;min-height:1.6em;cursor:pointer;list-style:none}
.fold>summary::-webkit-details-marker{display:none}
.fold>summary::before{content:"+";flex:none;box-sizing:border-box;width:1.6em;height:1.6em;display:inline-flex;align-items:center;justify-content:center;border:1px solid gray;border-radius:3px;color:gray;font:normal 1em/1 monospace}
.fold[open]>summary::before{content:"-"}
.fold[open]{min-height:1.6em}
.fold[open]>summary>.fold_label{display:none}
.fold_body{display:flow-root;min-width:0;margin:-1.6em 0 0 2.1em}
.fold_body>:first-child{margin-top:0}
EOD
);

function plugin_fold_convert()
{
	static $style_done = false;

	$args = func_get_args();

	// 本文は最後の引数。{{ }} で囲まれていると、末尾が "\r" で終わる
	$body = null;
	if (count($args) > 0 && substr(end($args), -1) === "\r") {
		$body = array_pop($args);
	}
	if ($body === null) {
		return '<p>#fold: 本文を {{ と }} で囲んでください。</p>';
	}

	$title = PLUGIN_FOLD_DEFAULT_TITLE;
	$open  = false;
	if (count($args) > 0) {
		$first = array_shift($args);
		if (strtolower(trim($first)) === 'open') {
			$open = true;
		} elseif (trim($first) !== '') {
			$title = $first;
		}
	}
	foreach ($args as $value) {
		if (strtolower(trim($value)) === 'open') $open = true;
	}

	// 見出しは convert_html() を通して、ブラケット名などを使えるようにする
	$title_html = convert_html($title);
	$title_html = preg_replace('/^<p>/i', '', $title_html);
	$title_html = preg_replace('/<\/p>\s*$/i', '', $title_html);

	$body_html = convert_html(str_replace("\r", "\n", $body));

	if (! $style_done) {
		$style_done = true;
		global $head_tags;
		$head_tags[] = '<style>' . PLUGIN_FOLD_STYLE . '</style>';
	}

	return '<details class="fold"' . ($open ? ' open' : '') . '>'
		. '<summary><span class="fold_label">' . $title_html . '</span></summary>'
		. '<div class="fold_body">' . $body_html . '</div>'
		. '</details>' . "\n";
}