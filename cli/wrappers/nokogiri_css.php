#!/usr/bin/env php
<?php
require_once '__consts.php';

cli_set_process_title('PHP parser - ' . basename(__FILE__));
ini_set('memory_limit', MAX_MEMORY_LIMIT);

require_once dirname(dirname(__DIR__)) . "/vendor/autoload.php";
//require_once dirname(dirname(__DIR__)) . "/autoload.php";

$file = $argv[1];
$type  = $argv[2];
$count = $argv[3];

if (!ctype_digit($count)) {
	file_put_contents( 'php://stderr', 'Wrong count value: '.$count);
	exit(1);
}

switch ($type) {
	case 'html':
		$contentType = 'text/html';
		break;
	case 'xhtml':
		$contentType = 'application/xhtml+xml';
		break;
	case 'xml':
		$contentType = 'text/xml';
		break;
	default:
		file_put_contents( 'php://stderr', 'Wrong type: '.$type);
		exit(1);
}

$url = str_replace(APP_ROOT, APP_ROOT_URL, $file);
$url = str_replace('\\', '/', $url);

$content = file_get_contents($url, false, null, 0, MAX_INPUT_SIZE);
if ( $content === false )
{
	file_put_contents( 'php://stderr', 'Unable to read file: '.$file );
	exit;
}


$memAvailable = filter_var(ini_get("memory_limit"), FILTER_SANITIZE_NUMBER_INT);
$memAvailable = $memAvailable * 1024 * 1024;

$memStat = array(
	//"HIGHEST_MEMORY" => 0,
	"HIGHEST_DIFF" => 0,
	//"AVERAGE" => array(),
);

$memDiff = 0;
$memStart = memory_get_peak_usage(false);

$timeStart = microtime(true);

for ($i=0; $i < $count; $i++) {
	parseLinks($content);

	$memUsed = memory_get_peak_usage(false);
	$memDiff = $memUsed - $memStart;

	//$memStat['HIGHEST_MEMORY'] = $memUsed > $memStat['HIGHEST_MEMORY'] ? $memUsed : $memStat['HIGHEST_MEMORY'];
	$memStat['HIGHEST_DIFF'] = $memDiff > $memStat['HIGHEST_DIFF'] ? $memDiff : $memStat['HIGHEST_DIFF'];
	//$memStat['AVERAGE'][] = $memDiff;
}
$elapsedTime = microtime(true) - $timeStart;

//$percentage = (($memStart + $memStat['HIGHEST_DIFF']) / $memAvailable) * 100;
//$memStat['AVERAGE'] = array_sum($memStat['AVERAGE']) / count($memStat['AVERAGE']);

echo $memStat['HIGHEST_DIFF'] . PHP_EOL;
echo $elapsedTime . PHP_EOL;

global $tmp;

function recursive_array_search($needkey, $haystack)
	{
		global $tmp;
		$tmp = 0;
		foreach($haystack as $key => $value) {
			$tmp = $value;
			if($needkey === $key OR (is_array($value) && recursive_array_search($needkey, $value) !== false)) {
				return $tmp;
			}
		}
		return false;
	}

function parseLinks($html)
	{
		// Creates DOMDocument of version 1.0 encoding UTF-8
		$dom = new nokogiri($html);
		$links = array();
		foreach($dom->get('a') as $a) {
			$text = recursive_array_search('#text', $a);
			$links[] = [
				'text' => $text[0],
				'href' => $a['href'],
			];
		}
		unset($dom);
		unset($links);
	}
