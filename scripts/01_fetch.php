<?php
include dirname(__DIR__) . '/vendor/autoload.php';
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

$rawPath = dirname(__DIR__) . '/raw';
if(!file_exists($rawPath)) {
    mkdir($rawPath, 0777, true);
}

$dataPath = dirname(__DIR__) . '/data';
if(!file_exists($dataPath)) {
    mkdir($dataPath, 0777);
}

$client = new Client(HttpClient::create([
    'verify_peer' => false,
    'verify_host' => false,
]));
$client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:73.0) Gecko/20100101 Firefox/73.0');

$finalPage = false;
$page = 1;
$fhPool = [];
while(false === $finalPage) {
    $rawFile = $rawPath . '/page_' . $page . '.html';
    $offset = ($page - 1) * 10;
    $client->request('GET', 'https://cwisweb.sfaa.gov.tw/organtlist2.jsp?offset=' . $offset);
    file_put_contents($rawFile, $client->getResponse()->getContent());
    $pageContent = file_get_contents($rawFile);
    $lines = explode('</tr>', $pageContent);
    foreach($lines AS $line) {
        $cols = explode('</td>', $line);
        if(count($cols) === 9) {
            foreach($cols AS $k => $v) {
                $cols[$k] = trim(strip_tags($v));
            }
            if(!isset($fhPool[$cols[1]])) {
                $fhPool[$cols[1]] = fopen($dataPath . '/' . $cols[1] . '.csv', 'w');
                fputcsv($fhPool[$cols[1]], array('裁罰日期', '縣市', '罰鍰對象', '托嬰中心名稱', '負責人姓名', '違法事由', '違反法條', '處分內容'));
            }
            array_pop($cols);
            fputcsv($fhPool[$cols[1]], $cols);
        }
    }

    if(false === strpos($pageContent, '下一頁</a>')) {
        $finalPage = true;
    } else {
        ++$page;
    }
}