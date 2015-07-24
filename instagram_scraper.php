<?php
function crawl($username, $items, $max_id) {
    $id = '';
    $lastId = '';

    if($max_id > 0)
        $id = $max_id;

    $url = "http://instagram.com/" . $username . "/media/?&max_id=" . $id;

    if (!function_exists('curl_init'))
        die('cURL is not installed.');

    $ch = curl_init();
    $curl_options = array(
                        CURLOPT_URL => $url,
                        CURLOPT_REFERER => "http://instagram.com",
                        CURLOPT_USERAGENT => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)",
                        CURLOPT_HEADER => 0,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_HTTPHEADER => array('Content-type: application/json')
                    );
    curl_setopt_array($ch, $curl_options);
    $output = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($output, true);

    foreach ($json['items'] as $data) {
        $urlSplit = explode("/", $data['images']['low_resolution']['url']);
        $name = $urlSplit[count($urlSplit) - 1];
        $url = $urlSplit[0] . "/" . $urlSplit[1] . "/" . $urlSplit[2] . "/" . $urlSplit[3] . "/" . $urlSplit[4] . "/s1080x1080/" . $urlSplit[6] . "/" . $urlSplit[7];

        array_push($items, array($name, $url, $data['created_time']));
        $lastId = $data['id'];
    }

    if($json['more_available'] == true){
        return crawl($username, $items, $lastId);
    }else {
        return $items;
    }
}

function download($downloadList, $username) {
    $ch = curl_init();
    $curl_options = array(
                        CURLOPT_REFERER => "http://instagram.com",
                        CURLOPT_USERAGENT => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)",
                        CURLOPT_HEADER => 0,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_HTTPHEADER => array('Content-type: application/json')
                    );
    curl_setopt_array($ch, $curl_options);
    $saveto = "./" . $username . "/";

    if(!file_exists($saveto)) {
        if (!mkdir($saveto, 0744, true)) {
            die('Failed to create folder.');
        }
    }


    foreach ($downloadList as $data) {        
        if(!file_exists($saveto . $data[2] . "_" . $data[0])) {
            curl_setopt($ch, CURLOPT_URL, $data[1]);
            $output = curl_exec($ch);

            echo(date("Y-m-d H:i:s") . " - Downloading " . $data[0] . "\n");

            $fp = fopen($saveto . $data[2] . "_" . $data[0], 'w');
            fwrite($fp, $output);
            fclose($fp);
        }
    }
}

if(!isset($argv[1]) || empty($argv[1])) {
    die("Usage: php " . $_SERVER["SCRIPT_FILENAME"] . " <username>\n");
}

download(crawl($argv[1], array(), 0), $argv[1]);
?>
