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
.wikic-fold{display:block;box-sizing:border-box;width:100%;margin:.5em 0;border:1px solid rgba(128,128,128,.35);border-radius:6px}
.wikic-fold>summary{display:flex;align-items:center;gap:.5em;padding:.5em .8em;cursor:pointer;font-weight:600;list-style:none;user-select:none}
.wikic-fold>summary::-webkit-details-marker{display:none}
.wikic-fold>summary::before{content:"";flex:none;width:.45em;height:.45em;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(-45deg);transition:transform .15s}
.wikic-fold[open]>summary::before{transform:rotate(45deg)}
.wikic-fold>summary:hover{background:rgba(128,128,128,.08)}
.wikic-fold-body{min-width:0;padding:.4em .9em .8em;border-top:1px solid rgba(128,128,128,.25)}
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

	return '<details class="wikic-fold"' . ($open ? ' open' : '') . '>'
		. '<summary>' . $title_html . '</summary>'
		. '<div class="wikic-fold-body">' . $body_html . '</div>'
		. '</details>' . "\n";
}
