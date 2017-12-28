<?php
function MakeReadable($num) {
	if ($num == 0) return '0';
		$i = floor(log($num, 1000));
		return round($num / pow(1000, $i), [0,0,2,2,3][$i]).['','k','M','G','T'][$i];
}

header('Content-Type: application/json');
$file="/var/log/oacapi.log";

if (file_exists($file)) {
	$linecount = 0;
	$handle = fopen($file, "r");
	while(!feof($handle)){
		$line = fgets($handle);
		preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})/', $line, $match);
		preg_match('/(Query)/', $line, $qmatch);
		if (count($qmatch) > 0) {
			$time = strtotime($match[0]);
			if ((time() - $time) <= 86400) {
				$linecount++;
			}
		}
	}
	
	fclose($handle);
} else {
	$linecount = 0;
}

$arr = array('count' => MakeReadable($linecount));
echo json_encode($arr);
?>
