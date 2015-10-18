<?php

// script to update data files,
// based on https://en.wikipedia.org/wiki/List_of_country_calling_codes#Alphabetical_listing_by_country_or_region

require_once __DIR__.'/../vendor/autoload.php';

function getRightSideOfMediawikiTag($t)
{
    $start = '[[';
    $pos = mb_strpos($t, $start);
    if ($pos === false) {
        return $t;
    }
    $t = mb_substr($t, $pos + strlen($start));

    $pos2 = mb_strpos($t, ']]', $pos);
    if ($pos2 === false) {
        return $t;
    }

    $t = mb_substr($t, $pos, $pos2 - $pos);
    $n = explode('|', $t);

    if (!empty($n[1])) {
        return array_pop($n);
    }
    return $t;
}

function cleanText($s)
{
    $s = str_replace('|', '', $s);
    $s = trim($s);
    return $s;
}

function write_csv($fileName, $list)
{
    $csv = League\Csv\Writer::createFromFileObject(new SplTempFileObject());

    $csv->insertOne(['code', 'country']);

    foreach ($list as $o) {
        $csv->insertOne([$o->code, $o->country]);
    }

    file_put_contents($fileName, $csv->__toString());
}

function write_json($fileName, $list)
{
    $data = json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($fileName, $data);
}


function extractLinks($s)
{
    preg_match_all('/\[\[[\w\d\s,+|]+\]\]/', $s, $matches);

    return $matches[0];
}

function mapCountry($c)
{
    if ($c == 'Korea, South') {
        return 'South Korea';
    }

    if ($c == 'Korea, North') {
        return 'North Korea';
    }

    if ($c == 'Vatican City State (Holy See)') {
        return 'Vatican City';
    }

    if ($c == 'Congo, Democratic Republic of the (Zaire)') {
        return 'Democratic Republic of the Congo';
    }

    return $c;
}

$res = (new MartinLindhe\MediawikiClient\Client)
    ->server('en.wikipedia.org')
    ->cacheTtlSeconds(3600) // 1 hour
    ->fetchArticle('List of country calling codes');

$x = $res->data;


$start = "! [[Daylight saving time|DST]]"."\n"."|-"."\n";

$pos = strpos($x, $start);
if ($pos === false) {
    echo "ERROR: didn't find start\n";
    exit;
}

$pos += strlen($start);


$end = "\n"."|}";
$pos2 = strpos($x, $end, $pos);
if ($pos2 === false) {
    echo "ERROR: didnt find end\n";
    exit;
}

$data = substr($x, $pos, $pos2 - $pos);

$list = [];

$rows = explode("\n", $data);
for ($i = 0; $i < count($rows); $i++) {

    if (!$rows[$i] || $rows[$i] == '|-' || $rows[$i] == '|') {
        continue;
    }

    $country = cleanText($rows[$i++]);
    $country = mapCountry($country);


    $codes = extractLinks($rows[$i++]);

    $timezone = $rows[$i++];


    foreach ($codes as $c) {

        $code = getRightSideOfMediawikiTag($c);
        $code = str_replace(' ', '', $code);
        $code = str_replace('+', '', $code);

        $codes2 = explode(',', $code);

        foreach ($codes2 as $code2) {

            if (!is_numeric($code2)) {
                echo "Skipping ".$code2."\n";
                continue;
            }

            $o = new \MartinLindhe\Data\CallingCodes\CallingCode;
            $o->country = $country;
            $o->code = $code2;

            $list[] = $o;
        }
    }


}


write_csv(__DIR__.'/../data/calling_codes.csv', $list);
write_json(__DIR__.'/../data/calling_codes.json', $list);
