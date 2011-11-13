<?php

/*
 * From http://www.jonasjohn.de/snippets/php/post-request.htm (public domain)
 *
 */
 
function PostRequest($url, $referer, $_data, $wait=true) {
 
    // convert variables array to string:
    $data = array();    
    foreach($_data as $k => $v)
        $data[] = htmlentities($k) . '=' . htmlentities($v);
    
    $data = implode('&', $data);
    // format --> test1=a&test2=b etc.
 
    // parse the given URL
    $url = parse_url($url);
    if ($url['scheme'] != 'http') { 
        trigger_error('Only HTTP request are supported !', E_USER_WARNING);
		return;
    }
 
    // extract host and path:
    $host = $url['host'];
    $path = $url['path'];
	$port = (isset($url['port'])) ? $url['port'] : 80;
 
    // open a socket connection
    $fp = fsockopen($host, $port);
 
    // send the request headers:
    fputs($fp, "POST $path HTTP/1.1\r\n");
    fputs($fp, "Host: $host\r\n");
    fputs($fp, "Referer: $referer\r\n");
    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
    fputs($fp, "Content-length: ". strlen($data) ."\r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    fputs($fp, $data);
 
    $result = ''; 
    while($wait && !feof($fp)) {
        // receive the results of the request
        $result .= fgets($fp, 128);
    }
 
    // close the socket connection:
    fclose($fp);
 
    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);
 
    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';
 
    // return as array:
    return array($header, $content);
}

?>