<?php

require_once 'includes/simple_html_dom.php';

$user = $_REQUEST['user'];
$pass = $_REQUEST['pass'];
/*
$ch = curl_init('http://mail.google.com');

$html = file_get_html('http://mail.google.com/');

$login = $html->find('form[id=gaia_loginform]')
*/

$gacookie=".htcookies"; 
$postdata="Email=$user&Passwd=$pass&service=mail&GALX=IgNtoXYlpEc&hl=en&continue=https://mail.google.com/mail/#settings/filters"; 
$ch = curl_init(); 
curl_setopt ($ch, CURLOPT_URL,"https://www.google.com/accounts/ServiceLoginAuth?service=mail"); 
curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); 
curl_setopt ($ch, CURLOPT_TIMEOUT, 60); 
curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1); 
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt ($ch, CURLOPT_COOKIEJAR, $gacookie); 
curl_setopt ($ch, CURLOPT_COOKIEFILE, $gacookie); 
curl_setopt ($ch, CURLOPT_REFERER, "https://mail.google.com/mail/?nsr=1&auth=DQAAAHoAAACy1eD0UAT31WKeVsU-MTE17okXAamkjSuAMA7ChWMkQyzIng3arK9nHhPl-0LjhMx1xKqh8HTMy1L-H5BHsNDxdkWscqpMJHhQjanY5jsJaoIlhMe9yLGhqDeeGHatPoHwLZvNtzUwww_Ivc8xexsN4F2rAOGkI7-Ob8eQdo96Sg&gausr=$user"); 
curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata); 
curl_setopt ($ch, CURLOPT_POST, 1); 
echo curl_exec ($ch); 
?>
