<?php

/**
 * Make a curl request using basic auth.
 *
 * Most of this comes from:
 * http://drupalcode.org/project/feeds.git/blob/refs/heads/7.x-2.x:/libraries/http_request.inc
 */
function simple_curl_get($url, $username = NULL, $password = NULL) {
  $headers = array();
  if ($username && $password) {
    $headers[] = 'Authorization: Basic ' . base64_encode("$username:$password");
  }
  $headers[] = 'Content-Type: application/json';
  $headers[] = 'User-Agent: PHP github-issues-markdown.php (+http://github.com/jrbeeman/)';

  $download = curl_init($url);
  curl_setopt($download, CURLOPT_FOLLOWLOCATION, TRUE);
  if ($username && $password) {
    curl_setopt($download, CURLOPT_USERPWD, "{$username}:{$password}");
  }
  curl_setopt($download, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($download, CURLOPT_HEADER, TRUE);
  curl_setopt($download, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($download, CURLOPT_ENCODING, '');
  curl_setopt($download, CURLOPT_TIMEOUT, 15);
  curl_setopt($download, CURLOPT_SSL_VERIFYPEER, 0);
  $data = curl_exec($download);

  if (curl_error($download)) {
    throw new Exception('cURL error ('. curl_errno($download) .') '. curl_error($download) ." for $url");
  }

  $result = new stdClass();
  $header_size = curl_getinfo($download, CURLINFO_HEADER_SIZE);
  $header = substr($data, 0, $header_size - 1);
  $result->data = substr($data, $header_size);
  $headers = preg_split("/(\r\n){2}/", $header);
  $header_lines = preg_split("/\r\n|\n|\r/", end($headers));
  $result->headers = array();
  array_shift($header_lines); // skip HTTP response status

  while ($line = trim(array_shift($header_lines))) {
    list($header, $value) = explode(':', $line, 2);
    // Normalize the headers.
    $header = strtolower($header);

    if (isset($result->headers[$header]) && $header == 'set-cookie') {
      // RFC 2109: the Set-Cookie response header comprises the token Set-
      // Cookie:, followed by a comma-separated list of one or more cookies.
      $result->headers[$header] .= ',' . trim($value);
    }
    else {
      $result->headers[$header] = trim($value);
    }
  }
  $result->code = curl_getinfo($download, CURLINFO_HTTP_CODE);

  curl_close($download);
  return $result;
}
