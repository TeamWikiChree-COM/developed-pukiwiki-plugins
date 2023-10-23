<?php
function plugin_counter_inline() {
    if (!exist_plugin("counter")) 
        return "Not found counter plugin";
    
	$args = func_get_args();
    $arg = strtolower(array_shift($args));
    switch ($arg) {
        case ''     :
        case 'total':
        case 'today':
        case 'yesterday':
            $total = 0;
            $extlen = strlen(COUNTER_EXT);
            if($dh = opendir(COUNTER_DIR)) {
                while (($file = readdir($dh)) !== false) {
                    $fileName = substr($file, 0, strlen($file)-$extlen);
                    $ext = substr($file, strlen($file)-$extlen, $extlen);
        
                    if(strcmp($ext, COUNTER_EXT) == 0) {
                        $pageName = decode($fileName);
                        $counter = plugin_allcounter_get_count($pageName);
                        $total += intval($counter[$arg]);
                    }
                }
            }
            
            return $total;
        default:
            return htmlsc('&allcounter([total|today|yesterday]);');
    }
}

function plugin_allcounter_get_count($page)
{
	global $vars, $plugin_counter_db_options, $plugin;
	static $counters = array();
	static $default;
	$page_counter_t = PLUGIN_COUNTER_DB_TABLE_NAME_PREFIX . 'page_counter';

	if (! isset($default))
		$default = array(
			'total'     => 0,
			'date'      => get_date('Y/m/d'),
			'today'     => 0,
			'yesterday' => 0,
			'ip'        => '');

	if (! is_page($page)) return $default;
	if (isset($counters[$page])) return $counters[$page];

	// Set default
	$counters[$page] = $default;
	$modify = FALSE;
	$c = & $counters[$page];

	if (PLUGIN_COUNTER_USE_DB) {
		if (SOURCE_ENCODING !== 'UTF-8') {
			die('counter.inc.php: Database counter is only available in UTF-8 mode');
		}
		$is_new_page = false;
		try {
			$pdo = new PDO(PLUGIN_COUNTER_DB_CONNECT_STRING,
				PLUGIN_COUNTER_DB_USERNAME, PLUGIN_COUNTER_DB_PASSWORD,
				$plugin_counter_db_options);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
			$stmt = $pdo->prepare(
"SELECT total, update_date,
   today_viewcount, yesterday_viewcount, remote_addr
 FROM $page_counter_t
 WHERE page_name = ?"
			);
			$stmt->execute(array($page));
			$r = $stmt->fetch();
			if ($r === false) {
				$is_new_page = true;
			} else {
				$c['ip'] = $r['remote_addr'];
				$c['date'] = $r['update_date'];
				$c['yesterday'] = intval($r['yesterday_viewcount']);
				$c['today'] = intval($r['today_viewcount']);
				$c['total'] = intval($r['total']);
				$stmt->closeCursor();
			}
		} catch (Exception $e) {
			// Error occurred
			$db_error = '(DBError)';
			return array(
				'total' => $db_error,
				'date' => $db_error,
				'today' => $db_error,
				'yesterday' => $db_error,
				'ip' => $db_error);
		}
	} else {
		// Open
		$file = COUNTER_DIR . encode($page) . PLUGIN_COUNTER_SUFFIX;
		pkwk_touch_file($file);
		$fp = fopen($file, 'r+')
			or die('counter.inc.php: Cannot open COUNTER_DIR/' . basename($file));
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);

		// Read
		foreach (array_keys($default) as $key) {
			// Update
			$c[$key] = rtrim(fgets($fp, 256));
			if (feof($fp)) break;
		}
	}
	return $c;
}