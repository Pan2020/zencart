<?php
/**
 * Standalone TLS Test tool for PayPal connection readiness in 2016
 * per https://www.paypal-knowledge.com/infocenter/index?page=content&widgetview=true&id=FAQ1914&viewlocale=en_US
 * 
 * Accepted parameters:
 *   i=1 -- to show certificate details
 *
 * @package utilities
 * @copyright Copyright 2003-2015 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: Author: DrByte  New in v1.5.5 $
 */
// no caching
header('Cache-Control: no-cache, no-store, must-revalidate');


$url = 'https://tlstest.paypal.com';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Zen Cart(tm) - TLS TEST');

if (isset($_GET['i'])) curl_setopt($ch, CURLOPT_CERTINFO, TRUE);

$result = curl_exec($ch);
$errtext = curl_error($ch);
$errnum = curl_errno($ch);
$commInfo = @curl_getinfo($ch);
curl_close ($ch);

if (isset($commInfo['url'])) $commInfo['url'] = '"' . $commInfo['url'] . '"';

// Handle results
if ($errnum != 0) {
  echo 'Error: ' . $errnum . ': ' . $errtext . '<br><br>';
} else {
  echo 'CURL TLS Connection successful.<br><br>';
  echo '<pre>' . $result . '</pre><br>';
}
echo '<pre>Connection Details:' . "\n" . print_r($commInfo, true) . '</pre><br /><br />';
echo '<br><br><br><em>Advanced use: To also display the certificate chain, add <strong>?i=</strong> to the end of the URL.</em>';
