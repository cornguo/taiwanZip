#!/usr/local/bin/php
<?php

if (2 !== $argc) {
    die("Usage parse.php [originalFile].\n");
}

echo "Processing...\n";
$ret = processData($argv[1]);
//echo $ret['cnt'] . " rows processed.\n";
//file_put_contents('parsed.json', json_encode($ret['data']));

function processData($filename) {
    $fp = fopen($filename, 'rb');

    $rCnt = 0;
    $colname = array();
    $data = array();
    $valKey = '郵遞區號';
    $keys = array('縣市名稱', '鄉鎮市區', '原始路名', '投遞範圍');

    while ($row = fgetcsv($fp, NULL, "\t")) {
        if (0 === $rCnt) {
            $colname = $row;
        } else {
            $rowNorm = array_combine($colname, processRow($row));
            $cityKey = $rowNorm[$keys[0]];
            if (!isset($data[$cityKey])) {
                $data[$cityKey] = array();
            }

            $distKey = $rowNorm[$keys[1]];
            if (!isset($data[$cityKey][$distKey])) {
                $data[$cityKey][$distKey] = array();
            }

            $roadKey = $rowNorm[$keys[2]];
            if (!isset($data[$cityKey][$distKey][$roadKey])) {
                $data[$cityKey][$distKey][$roadKey] = array();
            }

            $numbKey = processNumbKey($rowNorm[$keys[3]]);
            if (!isset($data[$cityKey][$distKey][$numbKey])) {
                $data[$cityKey][$distKey][$roadKey] = array();
            }

            $data[$cityKey][$distKey][$roadKey][$numbKey] = $rowNorm[$valKey];
        }
        $rCnt++;
    }
    return array('data' => $data, 'cnt' => $rCnt);
}

function processRow($row) {
    $fWidthChar = str_split('０１２３４５６７８９', 3);
    $hWidthChar = str_split('0123456789', 1);

    foreach ($row as $rK => $rV) {
        $row[$rK] = str_replace($fWidthChar, $hWidthChar, $rV);
    }

    return $row;
}

function processNumbKey($key) {
    $pattern = array(
                    '/　+/',
                    '/ +/',
                    '/^ +/',
                    '/全 ?/',
                    '/單 ?/',
                    '/雙 ?/',
                    '/連 ?/',
                    '/號?含?附號/u',
                    '/([0-9]+)巷/',
                    '/([0-9]+)弄/',
                    '/([0-9]+)鄰/',
                    '/([0-9]+)之 ?([0-9]+)號?/u',
                    '/([0-9]+)號/',
                    '/地下/',
                    '/([0-9\-]+)樓/',
                    '/\[([A-Z])_([0-9\-]+)\]至 ?\[([A-Z])_([0-9\-]+)\]/',
                    '/\[([A-Z])_([0-9]+)-([0-9]+)\]至之 ?\[([A-Z])_([0-9\-]+)\]/',
                    '/([0-9]+)至 ?\[F_([0-9\-]+)\]/',
                    '/\[([A-Z])_([0-9\-]+)\](_APP_)?及?以上/u',
                    '/\[([A-Z])_([0-9\-]+)\](_APP_)?及?以下/u',
                    '/^\[([A-Z])_([0-9\-]+)\]$/',
                    '/\(([^\)]*)\)?/u',
                    '/號_APP_/'
                );
    $replace = array(
                    ' ',
                    ' ',
                    '',
                    '[ALL]',
                    '[ODD]',
                    '[EVEN]',
                    '[CONT]',
                    '號_APP_',
                    '[L_$1]',
                    '[A_$1]',
                    '[G_$1]',
                    '[N_$1-$2]',
                    '[N_$1]',
                    '-',
                    '[F_$1]',
                    '[$1_$2]_TO_[$3_$4]',
                    '[$1_$2-$3]_TO_[$1_$2-$5]',
                    '[F_$1]_TO_[F_$2]',
                    '_GT_[$1_$2]$3',
                    '_LT_[$1_$2]$3',
                    '_ONLY_[$1_$2]',
                    '[STR_$1]',
                    '_APP_'
                );
    $keyTranslated = preg_replace($pattern, $replace, $key);
    return $keyTranslated;
}
