<?php
// $Id: dyntime.inc.php,v 1.0 2023/10/25 20:09:00 WikiChree.COM Team Exp $

function plugin_dyntime_inline() {
    global $time_format;
    return "<span class=\"dyntime\">" . get_date($time_format) . "</span>";
}

function plugin_dyntime_init() {
    global $head_tags;
    $head_tags[] = <<<EOD
    <script>
    function plugin_dyntime_display() {
        let date = new Date();
        let h = ('0' + date.getHours()).slice(-2);
        let m = ('0' + date.getMinutes()).slice(-2);
        let s = ('0' + date.getSeconds()).slice(-2);
        document.querySelectorAll(".dyntime").forEach((tag) => {
            tag.innerHTML = (h + ":" + m + ":" + s);
        });
    }
    setInterval('plugin_dyntime_display()', 1000);
    </script>
EOD;
}