<?php
function MakeReadable($num) {
	if ($num == 0) return '0';
		$i = floor(log($num, 1000));
		return round($num / pow(1000, $i), [1,1,2,2,3][$i]).['','k','M','G','T'][$i];
}

header('Content-Type: application/json');
$patt="/var/log/oacapi.log*";

$days = 7;

$linecount = 0;
foreach (glob($patt) as $file) {
	$date = explode('-', $file);
	if (count($date) == 2) {
		$date = $date[1];
		$sdate = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, -2);
		if (time() - strtotime($sdate) > $days*86400) continue;
	}
	if (file_exists($file)) {
		$handle = fopen($file, "r");
		while(!feof($handle)){
			$line = fgets($handle);
			preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})/', $line, $match);
			preg_match('/(Query successful)/', $line, $qmatch);
			if (count($qmatch) > 0) {
				$time = strtotime($match[0]);
				if ((time() - $time) <= $days*86400) {
					$linecount++;
				}
			}
		}
		
		fclose($handle);
	}
}

$arr = array('count' => MakeReadable($linecount));
echo json_encode($arr);
?>
