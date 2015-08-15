<?php

// script to update data files, based on https://en.wikipedia.org/wiki/List_of_country_calling_codes#Alphabetical_listing_by_country_or_region

require_once __DIR__.'/../vendor/autoload.php';

function cleanText($s)
{
    $s = trim($s);

    if ($s == '|-') {
        return '';
    }

    if (substr($s, 0, 2) == '| ') {
        $s = substr($s, 2);
    }

    $p1 = strpos($s, '<!--');
    if ($p1 !== false) {
        $p2 = strpos($s, '-->');
        if ($p2 !== false) {
            $s = substr($s, 0, $p1).substr($s, $p2 + strlen('-->'));
            return cleanText($s);
        }
        return '';
    }

    $p1 = strpos($s, '<ref>');
    if ($p1 !== false) {
        $p2 = strpos($s, '</ref>');
        if ($p2 !== false) {
            $s = substr($s, 0, $p1).substr($s, $p2 + strlen('</ref>'));
            return cleanText($s);
        }
        return '';
    }

    return $s;
}

function getRightSideOfMediawikiTag($t)
{
    $pos = mb_strpos($t, '{{');
    if ($pos === false) {
        return $t;
    }

    $pos2 = mb_strpos($t, '}}', $pos);
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

function isAlpha3InList($alpha3, $list)
{
    foreach ($list as $o) {
        if ($o->alpha3 == $alpha3) {
            return true;
        }
    }
    return false;
}

function write_csv($fileName, $list)
{
    $csv = League\Csv\Writer::createFromFileObject(new SplTempFileObject());

    $csv->insertOne(['alpha2', 'alpha3', 'number', 'name']);

    foreach ($list as $o) {
        $csv->insertOne([$o->alpha2, $o->alpha3, $o->number, $o->name]);
    }

    file_put_contents($fileName, $csv->__toString());
}

function write_json($fileName, $list)
{
    $data = json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($fileName, $data);
}


$res = (new MartinLindhe\MediawikiClient\Client)
    ->server('en.wikipedia.org')
    ->cacheTtlSeconds(3600) // 1 hour
    ->fetchArticle('List of country calling codes');

$x = $res->data;

$start = "Link to [[ISO 3166-2]] subdivision codes"."\n"."|-"."\n";


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

    $rows[$i] = cleanText($rows[$i]);
    if (!$rows[$i]) {
        continue;
    }

    $cols = explode('||', $rows[$i]);
    if (count($cols) == 1) {
        $name = $rows[$i];
        $i++;
        $rows[$i] = cleanText($rows[$i]);
        $cols = explode('||', $rows[$i]);
    }

    $o = new \MartinLindhe\Data\CallingCodes\CallingCode;
    $o->alpha2 = getRightSideOfMediawikiTag($cols[0]);
    $o->alpha3 = getRightSideOfMediawikiTag($cols[1]);
    $o->number = getRightSideOfMediawikiTag($cols[2]);

    $name = cleanText($name);
    $name = getRightSideOfMediawikiTag(\MartinLindhe\MediawikiClient\Client::stripMediawikiLinks($name));

    $pos = mb_strpos($name, '/');
    if ($pos !== false) {
        $name = mb_substr($name, 0, $pos);
    }

    $pos = mb_strpos($name, '|');
    if ($pos !== false) {
        $name = mb_substr($name, $pos + 1);
    }

    $o->name = trim($name);

    $list[] = $o;
}


write_csv(__DIR__.'/../data/calling_codes.csv', $list);
write_json(__DIR__.'/../data/calling_codes.json', $list);
