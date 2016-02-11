<?php
/**
*   Crawls through user's page and gets all avaliable images/videos
*/
function crawl($username, $items, $max_id) {
    $id = '';
    $lastId = '';

    if ($max_id > 0) {
        $id = $max_id;
    }

    $userURL = "https://www.instagram.com/" . $username . "/media/?&max_id=" . $id;

    $ch = curl_init();
    $curl_options = array(
                        CURLOPT_URL => $userURL,
                        CURLOPT_REFERER => "https://www.instagram.com",
                        CURLOPT_USERAGENT => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)",
                        CURLOPT_HEADER => 0,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_HTTPHEADER => array('Content-type: application/json'),
                        CURLOPT_COOKIEFILE => __DIR__ . "/cookies.txt",
                        CURLOPT_SSL_VERIFYPEER => false
                    );
    curl_setopt_array($ch, $curl_options);
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response, true);

    if(empty($json['items'])) {
        die("Invalid username or private.\r\n");
    }
    	
    // Loop over json, get the filename, URL and timestamp
    foreach ($json['items'] as $data) {
        if($data['type'] == "video") {
            $imageURL = $data['videos']['standard_resolution']['url'];
            $name = explode("/", $imageURL);
            $name = $name[count($name) - 1];
        } else {
            $urlSplit = explode("/",
                    $data['images']['standard_resolution']['url']);
            $name = $urlSplit[count($urlSplit) - 1];
            
            // Some images have URLs of different lengths
			// "/s1080x1080/" ensures the image is the largest possible
            if(count($urlSplit) == 6) {
                $imageURL = $urlSplit[0] . "/" . $urlSplit[1] . "/" . $urlSplit[2] 
                        . "/" . $urlSplit[3] . "/" . $urlSplit[4] . "/" 
                        . $urlSplit[5];
            } elseif(count($urlSplit) == 8) {
                $imageURL = $urlSplit[0] . "/" . $urlSplit[1] . "/" . $urlSplit[2] 
                        . "/" . $urlSplit[3] . "/" . $urlSplit[4] 
                        . "/s1080x1080/" . $urlSplit[6] . "/" . $urlSplit[7];
            } elseif(count($urlSplit) == 9) {
                $imageURL = $urlSplit[0] . "/" . $urlSplit[1] . "/" . $urlSplit[2] 
                        . "/" . $urlSplit[3] . "/" . $urlSplit[4] 
                        . "/s1080x1080/" . $urlSplit[6] . "/" . $urlSplit[7] 
                        . "/" . $urlSplit[8];
            } else {
				$imageURL = $data['images']['standard_resolution']['url'];
			}
        }

        // Add file name, url, and upload date to array
        array_push($items, array($name, $imageURL, $data['created_time']));

        // Instagram only shows one page of images at a given time, saves the id of the last image
        $lastId = $data['id'];
    }

    // Recurse if more images are avaliable
    if($json['more_available'] == true){
        return crawl($username, $items, $lastId);
    } else {
        return $items;
    }
}


/**
*   Downloads images returned from crawl()
*/
function download($downloadList, $username) {
    $ch = curl_init();
    $curl_options = array(
                        CURLOPT_REFERER => "http://instagram.com",
                        CURLOPT_USERAGENT => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)",
                        CURLOPT_HEADER => 0,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                    );
    curl_setopt_array($ch, $curl_options);

    $saveto = "./" . $username . "/";

    // Create user's folder
    if(!file_exists($saveto)) {
        if (!mkdir($saveto, 0744, true)) {
            die(date("Y-m-d H:i:s") . " - Failed to create folder.\r\n");
        }
    }

    // Download and save image
    // Download and save image
    foreach ($downloadList as $data) {
        $fileName = $data[0];
        $imageURL = $data[1];
        $timestamp = $data[2];
        
        // Instagram API sometimes gives weird file names
        if(strpos($fileName, "ig_cache_key")) {
            $fileName = explode("?", $fileName)[0];
        }
        
        $fileLocation = $saveto . $timestamp . "_" . $fileName;
        
        if(!file_exists($fileLocation)) {
            curl_setopt($ch, CURLOPT_URL, $imageURL);
            $output = curl_exec($ch);

            // Check error code
            if(curl_getinfo($ch)['http_code'] == "200") {
                echo("[" . curl_getinfo($ch)['http_code'] . "] " .date("Y-m-d H:i:s") . " - Downloading " . $fileName . "\r\n");
            } else {
                echo("[" . curl_getinfo($ch)['http_code'] . "] " .date("Y-m-d H:i:s") . " - Error downloading " . $fileName . " @ " . $imageURL . "\r\n");
            }

            $fp = fopen($fileLocation, 'w');
            fwrite($fp, $output);
            fclose($fp);
        }
    }
	curl_close($ch);
}

if(!isset($argv[1]) || empty($argv[1])) {
    die("Usage: php " . $_SERVER["SCRIPT_FILENAME"] . " <username>\r\n");
}

if (!function_exists('curl_init')) {
    die(date("Y-m-d H:i:s") . " - cURL is not installed.\r\n");
}

download(crawl($argv[1], array(), 0), $argv[1]);
?>
