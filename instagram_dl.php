<?php
include("includes/rollingcurlx.class.php");

/**
*   Crawls through user's page and downloads all avaliable images/videos
*/
function download($RCX, $username, $max_id = 0) {
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
	
	if(empty($response)){
		die("API returned nothing\r\n");
	}
	
    curl_close($ch);
    $json = json_decode($response, true);
	
    if($json['status'] == "ok" && !empty($json['items'])) {	
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
					$imageURL = $urlSplit[0] . "//" . $urlSplit[2] 
							. "/" . $urlSplit[3] . "/" . $urlSplit[4] . "/" 
							. $urlSplit[5];
				} elseif(count($urlSplit) == 7) {
					$imageURL = $urlSplit[0] . "//" . $urlSplit[2] 
							. "/" . $urlSplit[3] . "/s1080x1080/" . $urlSplit[5]
							. "/" . $urlSplit[6];
				} elseif(count($urlSplit) == 8) {
					$imageURL = $urlSplit[0] . "//" . $urlSplit[2] 
							. "/" . $urlSplit[3] . "/" . $urlSplit[4] 
							. "/s1080x1080/" . $urlSplit[6] . "/" . $urlSplit[7];
				} elseif(count($urlSplit) == 9) {
					$imageURL = $urlSplit[0] . "//" . $urlSplit[2] 
							. "/" . $urlSplit[3] . "/" . $urlSplit[4] 
							. "/s1080x1080/" . $urlSplit[6] . "/" . $urlSplit[7] 
							. "/" . $urlSplit[8];
				} else {
					$imageURL = $data['images']['standard_resolution']['url'];
				}
			}

			// Add image to download queue
			$RCX->addRequest($imageURL, null, 'save', ['fileName' => $name, 'created_time' => $data['created_time'], 'username' => $username]);

			// Instagram only shows one page of images at a given time, saves the id of the last image
			$lastId = $data['id'];
		}
	} else {
		die("Invalid username or private account.\r\n");
	}

    // Recurse if more images are avaliable
    if($json['more_available'] == true){
        return download($RCX, $username, $lastId);
    } else {
		$RCX->setOptions([array(
                        CURLOPT_REFERER => "http://instagram.com",
                        CURLOPT_USERAGENT => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)",
                        CURLOPT_HEADER => 0,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                    )]);
		$RCX->execute();
	}
}

function save($response, $url, $request_info, $user_data, $time) {
    $saveto = "./" . $user_data['username'] . "/";

    // Create user's folder
    if(!file_exists($saveto)) {
        if (!mkdir($saveto, 0744, true)) {
            die(date("Y-m-d H:i:s") . " - Failed to create folder.\r\n");
        }
    }
	
	$fileName = $user_data['fileName'];
	$timestamp = $user_data['created_time'];
	
	// Instagram API sometimes gives weird file names
	if(strpos($fileName, "ig_cache_key")) {
		$fileName = explode("?", $fileName)[0];
	}
	
	$fileLocation = $saveto . $timestamp . "_" . $fileName;
	
	if(!file_exists($fileLocation)) {
		// Check error code
		if($request_info['http_code'] == "200") {
			echo("[" . $request_info['http_code'] . "] " .date("Y-m-d H:i:s") . " - saved " . $fileName . "\r\n");
		} else {
			echo("[" . $request_info['http_code'] . "] " .date("Y-m-d H:i:s") . " - Error downloading " . $fileName . " @ " . $url . "\r\n");
			return;
		}
		
		$fp = fopen($fileLocation, 'w');
		fwrite($fp, $response);
		fclose($fp);
	}
}

if(!isset($argv[1]) || empty($argv[1])) {
    die("Usage: php " . $_SERVER["SCRIPT_FILENAME"] . " <username>\r\n");
}

if (!function_exists('curl_init')) {
    die(date("Y-m-d H:i:s") . " - cURL is not installed.\r\n");
}

download(new RollingCurlX(10), $argv[1], 0);
?>
