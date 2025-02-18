#!/usr/bin/env php
<?php
require_once '__consts.php';

cli_set_process_title('PHP parser - ' . basename(__FILE__));
ini_set('memory_limit', MAX_MEMORY_LIMIT);

//require_once dirname(dirname(__DIR__)) . "/vendor/autoload.php";
require_once dirname(dirname(__DIR__)) . "/autoload.php";

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

use helpers\TidyHelper;

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
	parseLinks($content, $contentType);

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




function parseLinks($content, $contentType = 'text/html', $inputEncoding = 'UTF-8') {
		$data = array();

		if ($contentType === 'text/xml') {
			$isXml = true;
			$xmlString = $content;
		} else {
			$isXml = false;
			$xmlString = TidyHelper::getCleanHtml5($content, $inputEncoding, 'text/xml');
		}
		$parser = xml_parser_create();

		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, true);

		xml_parse_into_struct($parser, $xmlString, $values, $tags);
		if ($isXml) {
			if(!empty($tags['offer'])){
				foreach ($tags['offer'] as $key => $val) {
					$tag = $values[$val];
					$id = $tag['attributes']['id'];
					if ($id === null) continue;

					$data[] = [
						'text' => $id,
						'href' => "I can't :(",
					];
				}
			}
		} else {
			if(!empty($tags['a'])){
				foreach ($tags['a'] as $key => $val) {
					$tag = $values[$val];
					$href = $tag['attributes']['href'];
					if (empty($tag['value']) && empty($href)) continue;

					$data[] = [
						'text' => $tag['value'] ,
						'href' => $href,
					];
				}
			}
		}
		xml_parser_free($parser);
		return $data;
	}
