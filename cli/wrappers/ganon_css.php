#!/usr/bin/env php
<?php
require_once '__consts.php';

cli_set_process_title('PHP parser - ' . basename(__FILE__));
ini_set('memory_limit', MAX_MEMORY_LIMIT);

//require_once dirname(dirname(__DIR__)) . "/vendor/autoload.php";
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

$DS = DIRECTORY_SEPARATOR;
$parser = dirname(dirname(__DIR__)). "{$DS}classes{$DS}parsing{$DS}Ganon.php";
if (file_exists( $parser ) && is_readable( $parser ))
{
	require $parser;
}
else {
	echo "Can't open Ganon.php";
}

$memAvailable = filter_var(ini_get("memory_limit"), FILTER_SANITIZE_NUMBER_INT);
$memAvailable = $memAvailable * 1024 * 1024;

$memStat = array(
	"HIGHEST_MEMORY" => 0,
	"HIGHEST_DIFF" => 0,
	"AVERAGE" => array(),
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


function parseLinks($html, $inputEncoding = 'UTF-8')
	{
		$links = array();

		$doc = str_get_dom($html);

		foreach($doc('a') as $node) {
			$links[] = [
				'text' => iconv($inputEncoding, "UTF-8", $node->getPlainText()),
				'href' => $node->getAttribute("href"),
			];
		}
		unset($doc);
		unset($links);
	}
