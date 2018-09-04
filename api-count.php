<?php
function MakeReadable($num) {
	if ($num == 0) return '0';
		$i = floor(log($num, 1000));
		return round($num / pow(1000, $i), [1,1,2,2,3][$i]).['','k','M','G','T'][$i];
}

header('Content-Type: application/json');

$cache_path = '/var/www/html/astrocats/api-count-cache/query';

$cache_time = 3600;
if (file_exists($cache_path) && time() - filemtime($cache_path) < $cache_time) {
	echo file_get_contents($cache_path);
	return;
}

$patt="/var/log/oacapi.log*";

$days = 7;
$day_secs = $days * 86400;
$ips = array();
$events = array();

$linecount = 0;
foreach (glob($patt) as $file) {
	$date = explode('-', $file);
	if (count($date) == 2) {
		$date = $date[1];
		$sdate = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, -2);
		if (time() - strtotime($sdate) > $day_secs) continue;
	}
	if (file_exists($file)) {
		$handle = fopen($file, "r");
		$lc = 0;
		while(!feof($handle)){
			$line = fgets($handle);
			preg_match('/(Query successful)/', $line, $qmatch);
			if (count($qmatch) > 0) {
				preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})/', $line, $match);
				$time = strtotime($match[0]);
				if ((time() - $time) <= $day_secs) {
					$linecount++;
				}
				continue;
			}
			preg_match('/(Query unsuccessful)/', $line, $umatch);
			if (count($umatch) > 0) {
				if ($lc <= $lastelc + 3) {
					foreach ($lastevents as $lev) {
						if (array_key_exists($lev, $events)) {
							if ($events[$lev] == 1) {
								unset($events[$lev]);
							} else {
								$events[$lev]--;
							}
						}
					}
				}
				if ($lc <= $lastilc + 3) {
					if (array_key_exists($lastip, $ips)) {
						if ($ips[$lastip] == 1) {
							unset($ips[$lastip]);
						} else {
							$ips[$lastip]--;
						}
					}
				}
				continue;
			}
			preg_match('/(Query from )(.+): (.+) -- (.+) -- (.+)/', $line, $imatch);
			if (count($imatch) == 0) continue;

			$ip = $imatch[2];
			if (array_key_exists($ip, $ips)) {
				$ips[$ip]++;
			} else {
				$ips[$ip] = 1;
			}
			$lastilc = $lc;
			$lastip = $ip;

			$event = explode('/', $imatch[4])[0];
			$pevents = array_map('trim', explode('+', $event));
			$npevents = array();
			foreach ($pevents as $pev) {
				if (is_numeric($pev)) {
					$npevents[count($npevents) - 1] .= '+' . $pev;
				} else {
					array_push($npevents, $pev);
				}
			}
			$pevents = $npevents;
			$lastevents = array();
			foreach ($pevents as $pev) {
				if (!in_array($pev, array('None', 'all', 'catalog', 'atel', 'reload_atels', 'reload_cats'))) {
					if (array_key_exists($pev, $events)) {
						$events[$pev]++;
					} else {
						$events[$pev] = 1;
					}
					$lastelc = $lc;
					array_push($lastevents, $pev);
				}
			}
			$lc++;
		}
		
		fclose($handle);
	}
}

arsort($events);
$arr = array('count' => MakeReadable($linecount), 'unique' => MakeReadable(count($ips)),
	'trending' => $events, 'top5' => implode(", ", array_slice(array_keys($events), 0, 5)));
$output = json_encode($arr);
file_put_contents($cache_path, $output);
echo $output;
?>
