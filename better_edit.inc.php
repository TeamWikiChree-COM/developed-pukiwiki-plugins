<?php
// $Id: better_edit.inc.php,v 1.1 2024/12/04 15:40:00 WikiChree.COM Team Exp $

// PukiWiki - Yet another WikiWikiWeb clone.
// better_edit.inc.php
// Copyright 2024 WikiChree.COM Team
// Copyright 2001-2022 PukiWiki Development Team
// License: GPL v2 or (at your option) any later version
//
// Better Edit plugin (cmd=better_edit)

//ini_set('display_errors', 1);

include_once(PLUGIN_DIR . 'edit.inc.php');
include_once(PLUGIN_DIR . 'attach.inc.php');

// Remove #freeze written by hand
if (!defined('PLUGIN_EDIT_FREEZE_REGEX'))
    define('PLUGIN_EDIT_FREEZE_REGEX', '/^(?:#freeze(?!\w)\s*)+/im');

if (!defined('PUKIWIKI_CSS'))
    define('PUKIWIKI_CSS', SKIN_DIR . 'pukiwiki.css');


function plugin_better_edit_action()
{
    global $vars, $_title_edit;

    if (isset($_POST['data'])) {
        $postdata = hex2bin($_POST['data']);
        $postdata = make_str_rules($postdata);
        $postdata = explode("\n", $postdata);
        $postdata = drop_submit(convert_html($postdata));
        $css = PUKIWIKI_CSS;
        echo <<<EOD
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="{$css}" />
</head>
<body>
    <div id="main" style="max-width:100%">
        <div id="contents" style="max-width:100%">
            <div id="body" style="max-width:100%">
                {$postdata}
            </div>
        </div>
    </div>
</body>
</html>
EOD;
        exit;
    }
    if (isset($vars['loading'])) {
        echo "Loading...";
        exit;
    }

    if (PKWK_READONLY) {
        die_message('PKWK_READONLY prohibits editing');
    }

    // Create initial pages
    plugin_edit_setup_initial_pages();

    $page = isset($vars['page']) ? $vars['page'] : '';
    check_editable($page, true, true);
    check_readable($page, true, true);

    if (isset($vars['preview'])) {
        return plugin_better_edit_preview($vars['msg']);
    } elseif (isset($vars['template'])) {
        return plugin_better_edit_preview_with_template();
    } elseif (isset($vars['write'])) {
        return plugin_better_edit_write();
    } elseif (isset($vars['cancel'])) {
        return plugin_better_edit_cancel();
    }
    
    ensure_valid_page_name_length($page);
    $postdata = @join('', get_source($page));
    if ($postdata === '') {
        $postdata = auto_template($page);
    }
    $postdata = remove_author_info($postdata);
    return array('msg'=>$_title_edit, 'body'=>plugin_better_edit_form($page, $postdata));
}

/**
 * Preview with template
 */
function plugin_better_edit_preview_with_template()
{
    global $vars;
    $msg = '';
    $page = isset($vars['page']) ? $vars['page'] : '';
    // Loading template
    $template_page;
    if (isset($vars['template_page']) && is_page($template_page = $vars['template_page'])) {
        if (is_page_readable($template_page)) {
            $msg = remove_author_info(get_source($vars['template_page'], TRUE, TRUE));
            // Cut fixed anchors
            $msg = preg_replace('/^(\*{1,3}.*)\[#[A-Za-z][\w-]+\](.*)$/m', '$1$2', $msg);
        }
    }
    return plugin_better_edit_preview($msg);
}

/**
 * Preview
 *
 * @param msg preview target
 */
function plugin_better_edit_preview($msg)
{
    global $vars;
    global $_title_preview, $_msg_preview, $_msg_preview_delete;

    $page = isset($vars['page']) ? $vars['page'] : '';

    $msg = preg_replace(PLUGIN_EDIT_FREEZE_REGEX, '', $msg);
    $postdata = $msg;

    if (isset($vars['add']) && $vars['add']) {
        if (isset($vars['add_top']) && $vars['add_top']) {
            $postdata  = $postdata . "\n\n" . @join('', get_source($page));
        } else {
            $postdata  = @join('', get_source($page)) . "\n\n" . $postdata;
        }
    }

    $body = $_msg_preview . '<br />' . "\n";
    if ($postdata === '') {
        $body .= '<strong>' . $_msg_preview_delete . '</strong>';
    }
    $body .= '<br />' . "\n";

    if ($postdata) {
        $postdata = make_str_rules($postdata);
        $postdata = explode("\n", $postdata);
        $postdata = drop_submit(convert_html($postdata));
        $body .= '<div id="preview">' . $postdata . '</div>' . "\n";
    }
    $body .= plugin_better_edit_form($page, $msg, $vars['digest'], false);

    return array('msg'=>$_title_preview, 'body'=>$body);
}

// Inline: Show edit (or unfreeze text) link
function plugin_better_edit_inline()
{
    static $usage = '&edit(pagename#anchor[[,noicon],nolabel])[{label}];';

    global $vars, $fixed_heading_anchor_edit;

    if (PKWK_READONLY) {
        return '';
    } // Show nothing

    // Arguments
    $args = func_get_args();

    // {label}. Strip anchor tags only
    $s_label = strip_htmltag(array_pop($args), false);

    $page = array_shift($args);
    if ($page === null) {
        $page = '';
    }
    $_noicon = $_nolabel = false;
    foreach ($args as $arg) {
        switch (strtolower($arg)) {
        case '':                   break;
        case 'nolabel': $_nolabel = true; break;
        case 'noicon': $_noicon  = true; break;
        default: return $usage;
        }
    }

    // Separate a page-name and a fixed anchor
    list($s_page, $id, $editable) = anchor_explode($page, true);

    // Default: This one
    if ($s_page == '') {
        $s_page = isset($vars['page']) ? $vars['page'] : '';
    }

    // $s_page fixed
    $isfreeze = is_freeze($s_page);
    $ispage   = is_page($s_page);

    // Paragraph edit enabled or not
    $short = htmlsc('Edit');
    if ($fixed_heading_anchor_edit && $editable && $ispage && ! $isfreeze) {
        // Paragraph editing
        $id    = rawurlencode($id);
        $title = htmlsc(sprintf('Edit %s', $page));
        $icon = '<img src="' . IMAGE_DIR . 'paraedit.png' .
            '" width="9" height="9" alt="' .
            $short . '" title="' . $title . '" /> ';
        $class = ' class="anchor_super"';
    } else {
        // Normal editing / unfreeze
        $id    = '';
        if ($isfreeze) {
            $title = 'Unfreeze %s';
            $icon  = 'unfreeze.png';
        } else {
            $title = 'Edit %s';
            $icon  = 'edit.png';
        }
        $title = htmlsc(sprintf($title, $s_page));
        $icon = '<img src="' . IMAGE_DIR . $icon .
            '" width="20" height="20" alt="' .
            $short . '" title="' . $title . '" />';
        $class = '';
    }
    if ($_noicon) {
        $icon = '';
    } // No more icon
    if ($_nolabel) {
        if (!$_noicon) {
            $s_label = '';     // No label with an icon
        } else {
            $s_label = $short; // Short label without an icon
        }
    } else {
        if ($s_label == '') {
            $s_label = $title;
        } // Rich label with an icon
    }

    // URL
    $script = get_base_uri();
    if ($isfreeze) {
        $url   = $script . '?cmd=unfreeze&amp;page=' . rawurlencode($s_page);
    } else {
        $s_id = ($id == '') ? '' : '&amp;id=' . $id;
        $url  = $script . '?cmd=edit&amp;page=' . rawurlencode($s_page) . $s_id;
    }
    $atag  = '<a' . $class . ' href="' . $url . '" title="' . $title . '">';
    static $atags = '</a>';

    if ($ispage) {
        // Normal edit link
        return $atag . $icon . $s_label . $atags;
    } else {
        // Dangling edit link
        return '<span class="noexists">' . $atag . $icon . $atags .
            $s_label . $atag . '?' . $atags . '</span>';
    }
}

// Write, add, or insert new comment
function plugin_better_edit_write()
{
    global $vars;
    global $_title_collided, $_msg_collided_auto, $_msg_collided, $_title_deleted;
    global $notimeupdate, $_msg_invalidpass, $do_update_diff_table;

    $page   = isset($vars['page'])   ? $vars['page']   : '';
    $add    = isset($vars['add'])    ? $vars['add']    : '';
    $digest = isset($vars['digest']) ? $vars['digest'] : '';

    ensure_valid_page_name_length($page);
    $vars['msg'] = preg_replace(PLUGIN_EDIT_FREEZE_REGEX, '', $vars['msg']);
    $msg = & $vars['msg']; // Reference

    $retvars = array();

    // Collision Detection
    $oldpagesrc = join('', get_source($page));
    $oldpagemd5 = md5($oldpagesrc);
    if ($digest !== $oldpagemd5) {
        $vars['digest'] = $oldpagemd5; // Reset

        $original = isset($vars['original']) ? $vars['original'] : '';
        $old_body = remove_author_info($oldpagesrc);
        list($postdata_input, $auto) = do_update_diff($old_body, $msg, $original);

        $retvars['msg' ] = $_title_collided;
        $retvars['body'] = ($auto ? $_msg_collided_auto : $_msg_collided) . "\n";
        $retvars['body'] .= $do_update_diff_table;
        $retvars['body'] .= plugin_better_edit_form($page, $postdata_input, $oldpagemd5, false);
        return $retvars;
    }

    // Action?
    if ($add) {
        // Add
        if (isset($vars['add_top']) && $vars['add_top']) {
            $postdata  = $msg . "\n\n" . @join('', get_source($page));
        } else {
            $postdata  = @join('', get_source($page)) . "\n\n" . $msg;
        }
    } else {
        // Edit or Remove
        $postdata = & $msg; // Reference
    }

    // NULL POSTING, OR removing existing page
    if ($postdata === '') {
        page_write($page, $postdata);
        $retvars['msg' ] = $_title_deleted;
        $retvars['body'] = str_replace('$1', htmlsc($page), $_title_deleted);
        return $retvars;
    }

    // $notimeupdate: Checkbox 'Do not change timestamp'
    $notimestamp = isset($vars['notimestamp']) && $vars['notimestamp'] != '';
    if ($notimeupdate > 1 && $notimestamp && ! pkwk_login($vars['pass'])) {
        // Enable only administrator & password error
        $retvars['body']  = '<p><strong>' . $_msg_invalidpass . '</strong></p>' . "\n";
        $retvars['body'] .= plugin_better_edit_form($page, $msg, $digest, false);
        return $retvars;
    }

    page_write($page, $postdata, $notimeupdate != 0 && $notimestamp);
    pkwk_headers_sent();
    header('Location: ' . get_page_uri($page, PKWK_URI_ROOT));
    exit;
}

// Cancel (Back to the page / Escape edit page)
function plugin_better_edit_cancel()
{
    global $vars;
    pkwk_headers_sent();
    header('Location: ' . get_page_uri($vars['page'], PKWK_URI_ROOT));
    exit;
}

/**
 * Setup initial pages
 */
function plugin_better_edit_setup_initial_pages()
{
    global $autoalias, $no_autoticketlinkname;

    // Related: Rename plugin
    if (exist_plugin('rename') && function_exists('plugin_rename_setup_initial_pages')) {
        plugin_rename_setup_initial_pages();
    }
    // AutoTicketLinkName page
    if (! $no_autoticketlinkname) {
        init_autoticketlink_def_page();
    }
    // AutoAliasName page
    if ($autoalias) {
        init_autoalias_def_page();
    }
}

function plugin_better_edit_form($page, $postdata, $digest = false, $b_template = true) {
    global $vars, $rows, $cols;
    global $_btn_preview, $_btn_repreview, $_btn_update, $_btn_cancel, $_msg_help, $_btn_draft;
    global $_btn_template, $_btn_load, $load_template_func;
    global $notimeupdate;
    global $_msg_edit_cancel_confirm, $_msg_edit_unloadbefore_message;
    global $rule_page;

    $script = get_base_uri();
    if ($digest === false) {
        $digest = md5(join('', get_source($page)));
    }
    $refer = $template = '';
    $addtag = $add_top = '';
    if (isset($vars['add'])) {
        global $_btn_addtop;
        $addtag  = '<input type="hidden" name="add"    value="true" />';
        $add_top = isset($vars['add_top']) ? ' checked="checked"' : '';
        $add_top = '<input type="checkbox" name="add_top" ' .
            'id="_edit_form_add_top" value="true"' . $add_top . ' />' . "\n" .
            '  <label for="_edit_form_add_top">' .
            '<span class="small">' . $_btn_addtop . '</span>' .
            '</label>';
    }
    if ($load_template_func && $b_template) {
        $template_page_list = get_template_page_list();
        $tpages = array(); // Template pages
        foreach ($template_page_list as $p) {
            $ps = htmlsc($p);
            $tpages[] = '   <option value="' . $ps . '">' . $ps . '</option>';
        }
        if (count($template_page_list) > 0) {
            $s_tpages = join("\n", $tpages);
        } else {
            $s_tpages = '   <option value="">(no template pages)</option>';
        }
        $template = <<<EOD
  <select name="template_page">
   <option value="">-- $_btn_template --</option>
$s_tpages
  </select>
  <input type="submit" name="template" value="$_btn_load" accesskey="r" />
  <br />
EOD;

        if (isset($vars['refer']) && $vars['refer'] != '') 
            $refer = '[[' . strip_bracket($vars['refer']) . ']]' . "\n\n";
    }

    $r_page      = rawurlencode($page);
    $s_page      = htmlsc($page);
    $s_digest    = htmlsc($digest);
    $s_postdata  = htmlsc($refer . $postdata);
    $postdatahex = bin2hex($s_postdata);
    $s_original  = isset($vars['original']) ? htmlsc($vars['original']) : $s_postdata;
    $b_preview   = isset($vars['preview']); // TRUE when preview
    $btn_preview = $b_preview ? $_btn_repreview : $_btn_preview;

    // Checkbox 'do not change timestamp'
    $add_notimestamp = '';
    if ($notimeupdate != 0) {
        global $_btn_notchangetimestamp;
        $checked_time = isset($vars['notimestamp']) ? ' checked="checked"' : '';
        // Only for administrator
        if ($notimeupdate == 2) {
            $add_notimestamp = '   ' .
                '<input type="password" name="pass" size="12" />' . "\n";
        }
        $add_notimestamp = '<input type="checkbox" name="notimestamp" ' .
            'id="_edit_form_notimestamp" value="true"' . $checked_time . ' />' . "\n" .
            '   ' . '<label for="_edit_form_notimestamp"><span class="small">' .
            $_btn_notchangetimestamp . '</span></label>' . "\n" .
            $add_notimestamp .
            '&nbsp;';
    }

    // 'margin-bottom', 'float:left', and 'margin-top'
    // are for layout of 'cancel button'
    $h_msg_edit_cancel_confirm = htmlsc($_msg_edit_cancel_confirm);
    $h_msg_edit_unloadbefore_message = htmlsc($_msg_edit_unloadbefore_message);
    $body = <<<EOD
<div class="edit_form" id="better_edit_form">
  <style>
    #resizable {
        overflow: auto;
        resize: both;
        width: 100%;
        height: 40vh;
        border: 0.1px solid #777777;
        border-radius: 0.2em;
    }
    #resizable > iframe {
        overflow:hidden;
        width:100%;
        height:99%;
    }
    #better_edit_form_preview iframe {
        border: none;
        width:100%;
        height:40vh;
        padding:0;
        margin:0;
    }
    #better_edit_form_textarea textarea {
        border: 0.1px solid #777777;
        border-radius: 0.2em;
        height:40vh;
        width:100%;
        padding:0;
        margin:0;
    }
    #clear_float_better_edit {
        clear:left;	
    }
    #editor:focus {
        outline: 1px solid #8FBFFF;
    }


.form_tab {
  list-style: none;
  margin: 0;
  padding: 0;
}

.form_button {
  display:inline-block;
  background-color: #D2E2F2;
  border:1px solid #AAAAAA;
  padding:5px 10px;
  border-radius : 3px;
}

.form_button:hover {
  background-color: #C0D0E0;
}

.form_tab li {
  display: inline-block;
  color: #AAAAAA;
  background-color: #DDEEFF;
  padding: 10px 10px;
  cursor: pointer;
}
 
.form_tab li:hover {
  color: #000;
  background-color: #D2E2F2;
}
 
.form_tab li.form_active {
  color: #000;
  background-color: #D2E2F2;
}
 
.tabContent {
  display: none;
  padding: 15px;
  border: 1px solid #D2E2F2;
}
 
.form_active {
  display: block;
}

  </style>
<script src="https&#58;//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
function string_to_utf8_hex_string(text)
{
	var bytes1 = string_to_utf8_bytes(text);
	var hex_str1 = bytes_to_hex_string(bytes1);
	return hex_str1;
}
function string_to_utf8_bytes(text)
{
    var result = [];
    if (text == null)
        return result;
    for (var i = 0; i < text.length; i++) {
        var c = text.charCodeAt(i);
        if (c <= 0x7f) {
            result.push(c);
        } else if (c <= 0x07ff) {
            result.push(((c >> 6) & 0x1F) | 0xC0);
            result.push((c & 0x3F) | 0x80);
        } else {
            result.push(((c >> 12) & 0x0F) | 0xE0);
            result.push(((c >> 6) & 0x3F) | 0x80);
            result.push((c & 0x3F) | 0x80);
        }
    }
    return result;
}
function byte_to_hex(byte_num)
{
	var digits = (byte_num).toString(16);
    if (byte_num < 16) return '0' + digits;
    return digits;
}
function bytes_to_hex_string(bytes)
{
	var	result = "";

	for (var i = 0; i < bytes.length; i++) {
		result += byte_to_hex(bytes[i]);
	}
	return result;
}
function load_better_preview(){
    var editor_data = document.getElementById("editor").value;
    document.getElementById("previewpostdata").value = string_to_utf8_hex_string(editor_data);
    var previewpost2 = document.getElementById("previewpost");
    previewpost2.submit();
var better_edit_load = function() {
    var better_edit_preview = document.getElementById('preview_better_edit').contentWindow.document.body;
    var better_edit_textarea = document.getElementById('editor');
}
setTimeout(better_edit_load, 1500);
setTimeout(better_edit_load, 2500);
setTimeout(better_edit_load, 3000);
}


    $(function() {
        $(".form_tab li").click(function() {
            var num = $(".form_tab li").index(this);
            if ($(this).hasClass('form_active')) {
                return;
            }

            $(".tabContent").removeClass('form_active');      
            $(".tabContent").eq(num).addClass('form_active');
            $(".form_tab li").removeClass('form_active');        
            $(this).addClass('form_active');

            if (num == 1) {
                load_better_preview();
            }
        });
    });

    // alt + pボタンを押したらタブを切り替える
    $(document).keydown(function(e) {
        if (e.altKey && e.keyCode === 80) {
            $(".form_tab li").eq(1).click();
        }
    });

    function dataURItoBlob(dataURI) {
        var byteString = atob(dataURI.split(',')[1]);
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
        var ab = new ArrayBuffer(byteString.length);
        var ia = new Uint8Array(ab);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        return new Blob([ab], {type: mimeString});
    }

    // クリップボードの画像をページに添付して&ref(ファイル名);を挿入する
    $(document).on('paste', function(e) {
        var items = e.originalEvent.clipboardData.items;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                var blob = items[i].getAsFile();
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = new Image();
                    img.src = e.target.result;
                    img.onload = function() {
                        var canvas = document.createElement('canvas');
                        var ctx = canvas.getContext('2d');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        ctx.drawImage(img, 0, 0);
                        var dataUrl = canvas.toDataURL('image/png');
                        var blob = dataURItoBlob(dataUrl);
                        var filename = 'paste_' + new Date().getTime() + '.png';
                        var file = new File([blob], filename, {type: 'image/png'});
                        var formData = new FormData();
                        formData.append('attach_file', file);
                        formData.append('plugin', 'attach');
                        formData.append('pcmd', 'post');
                        formData.append('refer', '$s_page');
                        $.ajax({
                            url: '$script',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(data) {
                                var text = '&ref(' + filename + ');';
                                var textarea = document.getElementById('editor');
                                var pos = textarea.selectionStart;

                                var before = textarea.value.substr(0, pos);
                                if (pos > 0 && textarea.value.substr(pos - 1, 1) == "\\n") {
                                    text = "#ref(" + filename + ")\\n";
                                }

                                var after = textarea.value.substr(pos);
                                textarea.value = before + text + after;
                                textarea.focus();
                                textarea.setSelectionRange(pos + text.length, pos + text.length);
                            }
                        });
                    };
                };
                reader.readAsDataURL(blob);
            }
        }
    });

    function insertText(text) {
        var textarea = document.getElementById('editor');
        var pos = textarea.selectionStart;
        var before = textarea.value.substr(0, pos);
        var after = textarea.value.substr(pos);
        textarea.value = before + text + after;
        textarea.focus();
        textarea.setSelectionRange(pos + text.length, pos + text.length);
    }

</script>
 <form action="$script" method="post" class="_plugin_edit_edit_form" style="margin-bottom:0;right:50%">
  $addtag
  <input type="hidden" name="cmd"    value="better_edit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="digest" value="$s_digest" />
  <input type="hidden" id="_msg_edit_cancel_confirm" value="$h_msg_edit_cancel_confirm" />
  <input type="hidden" id="_msg_edit_unloadbefore_message" value="$h_msg_edit_unloadbefore_message" />

<ul class="form_tab">
    <li class="form_active">編集</li>
    <li>プレビュー</li>
</ul>

  <div class="tabContent form_active" id="better_edit_form_textarea">
    {$template}
    <textarea id="editor" name="msg" rows="$rows">$s_postdata</textarea>
    {$inputtoolbar}
  </div>

  <div class="tabContent" id="better_edit_form_preview">
    <div id="resizable">
      <iframe name="preview_better_edit" id="preview_better_edit" src="./?plugin=better_edit&loading=true"></iframe>
    </div>
  </div>

  <br />
  <div style="float:left;">
   <input type="submit" name="write"   value="$_btn_update" accesskey="s" />
   $add_top
   $add_notimestamp
  </div>
  
  <textarea name="original" rows="1" cols="1" style="display:none">$s_original</textarea>
 </form>
 <form action="$script" method="post" class="_plugin_edit_cancel" style="margin-top:0;">
  <input type="hidden" name="cmd"    value="better_edit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="submit" name="cancel" value="$_btn_cancel" accesskey="c" />
 </form>
</div>

<form id="previewpost" target="preview_better_edit" action="./?plugin=better_edit" method="post">
    <input type="text" id="previewpostdata" name="data" value="{$postdatahex}" style="display:none;" />
    <input type="hidden" name="page" value="$s_page" />
    <input type="submit" style="display:none;" />
</form>
EOD;

    $body .= '<ul><li><a href="' .
        get_page_uri($rule_page) .
        '" target="_blank">' . $_msg_help . '</a></li></ul>';

    $attach_pages = new AttachPages($s_page);
    
    $ret = '';
    $attachFiles = $attach_pages->pages[$s_page];

    if (check_readable($s_page, false, false) && !empty($attachFiles->files)) {
        $files = array_keys($attachFiles->files);
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $_files = array();
            foreach (array_keys($attachFiles->files[$file]) as $age) {
                $_files[$age] = $attachFiles->files[$file][$age]->toString(false, true);
            }
            if (! isset($_files[0])) {
                $_files[0] = htmlsc($file);
            }
            ksort($_files, SORT_NUMERIC);
            $_file = $_files[0];
            $_filename = $attachFiles->files[$file][0]->file;
            unset($_files[0]);
            $ret .= " <li>$_file" . 
            "<span class=\"small\">[<a href=\"javascript:insertText('&ref($_filename);');\">挿入</a>]</span>" .
            "\n";
            if (count($_files)) {
                $ret .= "<ul>\n<li>" . join("</li>\n<li>", $_files) . "</li>\n</ul>\n";
            }
            $ret .= " </li>\n";
        }

        if ($ret != '') {
            global $_attach_messages;

            $body .= "<h4>" . $_attach_messages['msg_file'] . "</h4>\n<ul>" . $ret . '</ul>';
        }
    }



    return $body;
}

