<?php  
          
/**
*   This code is copyright Astra West, 2009. All rights reserved.
* 
*   That means that even though this code is available to let anyone verify my claims of user privacy,
*   NO PERMISSION TO MODIFY, REPRODUCE, OR DISTRIBUTE THIS CODE IS GRANTED.
* 
*   That means you may NOT use this to make your own project.
* 
*   If you would like to use this, just ask me at astra.west@ub3rk1tten.com
*   I'll probably grant you the right, I'm not trying to be mean.
*   If all you want to do is receive some messages, just use the Custom URL function.
* 
*   You can write this yourself, too, it's dead simple.
* 
*   The amount of work it would take to repurpose this script to work on another server
*   would be much more than simply making your own version for yourself. Trust me.
*/

require_once 'includes/defines.php';
require_once 'includes/mimeparser/rfc822_addresses.php';
require_once 'includes/mimeparser/mime_parser.php';       
if (PRODUCTION)
    require_once 'includes/mysql.inc.php';
                                      
include_once 'includes/ProwlPHP.php';
include_once 'includes/prowl.inc.php'; // Provider key
include_once 'includes/Net/Growl.php';   

/**
* Growl notifications declaration
*/
define('GROWL_NOTIFY_VOICEMAIL', 'GROWL_NOTIFY_VOICEMAIL');
define('GROWL_NOTIFY_SMS', 'GROWL_NOTIFY_SMS');
define('GROWL_NOTIFY_INBOX_ITEM', 'GROWL_NOTIFY_INBOX_ITEM');
$notifications = array(
    GROWL_NOTIFY_VOICEMAIL => array(
        'display' => 'New Voicemail',
        'sticky' => true
    ),
    GROWL_NOTIFY_SMS => array(
        'display' => 'New SMS',
        'sticky' => true
    ),
    GROWL_NOTIFY_INBOX_ITEM => array(
        'display' => 'New Inbox Item',
        'sticky' => true
    ),
);

/**
* Outputs header error 500, so smtp2web will pick it up in logs
*/
function error($msg)
{
    header('HTTP/1.1 500 ' . $msg);
    die($msg);
}                                         
           
/**
* Assign local variables from request data
*/                                   
$smtp2webaddress = array_key_exists('to', $_GET) ? $_GET['to'] : 'unknown'; // *@smtp2web.com or *@googlevoice.ub3rk1tten.com
$messagebody = file_get_contents('php://input'); // Email and headers    
$mute = false; // True to not send a message 

$useremail = ''; // Scope clarifications
$sendername = '';
$confirmationcode = '';
$replyemail = '';
$type = 'SMS';

// For prettiness of debug output
$divider = '--------------------------------------------------';

/**
* If called without a message in POST, will provide example emails to parse test
* ?voicemail will cause it to be a voicemail
* ?verification will cause it to be a filter verification email
*/
if ($messagebody == '') // Empty POST request _should_ be browser sent
{
    echo 'Debugging mode', PHP_EOL;
    if (array_key_exists('voicemail', $_GET))
        $messagebody = <<<DEBUG
Delivered-To: {$_GET['from']}
Subject: New voicemail from {$_SERVER['REMOTE_ADDR']} at 5:13 PM
From: Google Voice <voice-noreply@google.com>
To: {$_GET['from']}
Content-Type: multipart/alternative; boundary=001636e90b7adf8365047a8e3e68

--001636e90b7adf8365047a8e3e68
Content-Type: text/plain; charset=ISO-8859-1; format=flowed; delsp=yes

Voicemail from {$_SERVER['REMOTE_ADDR']} (555) 123-4321 at 3:53 PM
Transcript: Test voicemail message!
Play message:  
http://askahskldjhalskdj/

--001636e90b7adf8365047a8e3e68
Content-Type: text/html; charset=ISO-8859-1
Content-Transfer-Encoding: quoted-printable

<bunch of HTML>

--001636e90b7adf8365047a8e3e68--
DEBUG;
    else if (array_key_exists('verification', $_GET))
        $messagebody = <<<DEBUG
From: Gmail Team <mail-noreply@google.com>
To: {$_GET['from']}
Content-Type: text/plain; charset=ISO-8859-1

https://mail.google.com/mail/vf-e63a1ad9e2-voicegrowl%40ub3rk1tten.com-ed79ff2e8853914d
DEBUG;
    else
        $messagebody = <<<DEBUG
From: "{$_SERVER['REMOTE_ADDR']} (SMS)" <1234567890.14444655.asdfgqa@txt.voice.google.com>
To: {$_GET['from']}
Content-Type: text/plain; charset=ISO-8859-1; format=flowed; delsp=yes

Test message!

--
Sent using SMS-to-email.  Reply to this email to text the sender back and  
save on SMS fees
https://www.google.com/voice    
DEBUG;

    $mute = true;    
    echo $divider, PHP_EOL, $messagebody, PHP_EOL, $divider, PHP_EOL;
}                      
                  
/**
* smtp2web requests only, it may take a few ms for the DNS request, but then I don't have to keep this updated
* Still allows for using your own smtp2web.com account to forward
* 
* EDIT: Well, this didn't work. Fix... Sometime.
*/
//if (!preg_match('/notdot.net$/', gethostbyaddr($_SERVER['REMOTE_ADDR'])))
//    $mute = true;        
                             
/**
* Parse the email
*/
$mime = new mime_parser_class();
$mime->mbox = 0;
$mime->decode_bodies = 1;
$mime->ignore_syntax_errors = 1;
$parameters = array(
    'Data' => $messagebody,
    'SkipBody' => 0,
);
$decoded = array();
if(!@$mime->Decode($parameters, $decoded))
    error('Message decoding error ' . $mime->error);         
                                
/**
* Set the sender's name and the user's email from headers 
*/
foreach ($decoded[0]['Headers'] as $header => $val)
{
    $header = mb_strtolower($header);
    if ($header == 'from:' && $sendername == '')
    {
        // "Name (SMS)" <aslaksjlasj@skdmal.com>
        if (mb_strrpos($val, '(SMS)') !== false)
            $sendername = mb_substr($val, 1, mb_strrpos($val, '(SMS)') - 1);
        // "wat" <slajslkjlkj@fklsj.com> or
        // 48368 <asjlkjakljs@jalskj.com>
        else
        {
            $sendername = mb_substr($val, 0, mb_strrpos($val, ' <')); 
        }
        $sendername = trim($sendername, ' "');
                
        $replyemail = mb_substr($val, mb_strrpos($val, '<'));
        $replyemail = mb_substr($replyemail, 1, mb_strlen($replyemail) - 2);   
    }                   
    if ($header == 'delivered-to:')
    {
        $useremail = mb_strtolower($val);
    } 
    if ($header == 'to:' && $useremail == '')
    {
        $useremail = mb_strtolower($val);
    }            
    if ($header == 'subject:')
    {                  
        if (strtolower(mb_substr($val, 0, 18)) == 'new voicemail from')
        {
            $type = 'Voicemail';    
            $sendername = mb_substr($val, 19);
            $sendername = mb_substr($sendername, 0, mb_strrpos($sendername, ' at '));
        }
        else if (strpos(strtolower($val), 'forwarding confirmation'))
        {
            $type = 'Verification';

            $matches = array();
            preg_match("/https:\/\/mail.google.com\/mail\/vf-[0-9A-Za-z%\.-]+/", $decoded[0]['Body'], $matches);
            echo 'Filter verification URL: ', $matches[0];
            file_get_contents($matches[0]);

            $matches2 = array();
            preg_match("/Confirmation code: ([0-9]*)/", $decoded[0]['Body'], $matches2);
            $confirmationcode = $matches2[1];
            echo 'Filter verification code: ', $confirmationcode;

            $matches3 = array();
            preg_match("/(.*) has requested/", $decoded[0]['Body'], $matches3);
            $useremail = $matches3[1];
            echo 'Filter verification username: ', $useremail;
        }
    }
}                 
echo 'Sender name: ', $sendername, PHP_EOL;

/**
* Grab their username from their email    
*/
$username = mb_substr($useremail, 0, mb_strpos($useremail, '@'));      

/**
* Parse message body
*/
$messagebody = $decoded[0]['Body'];
if ($messagebody == '')
    $messagebody = $decoded[0]['Parts'][0]['Body'];
if ($type == 'SMS')
{
    if ($pt = mb_strrpos($messagebody, "\n\n--\nSent using SMS-to-email"))
        $messagebody = mb_substr($messagebody, 0, $pt);
}
elseif ($type == 'Voicemail')
{                                      
    $messagebody = mb_substr($messagebody, mb_strpos($messagebody, 'Transcript:') + 12);               
    $messagebody = mb_substr($messagebody, 0, mb_strrpos($messagebody, "\nPlay message:"));   
}
elseif ($type == 'Verification')
{
    $messagebody = 'Your GMail confirmation code: ' . $confirmationcode;
    $messagebody .= "\n\n" . 'VoiceGrowl will attempt to automatically verify your forward address, but if not, you may enter the above code into GMail';
}
echo 'Message body: ', $messagebody, PHP_EOL;
echo 'Message type: ', $type, PHP_EOL; 

/**
* Fetch their MySQL details
*/                             
if (PRODUCTION)
{                                                        
    $res = mysql_query("SELECT * FROM `users` WHERE `email`='" . mysql_real_escape_string($useremail) . "' OR `secondary_email`='" . mysql_real_escape_string($useremail) . "'") or error(mysql_error());
    if (mysql_num_rows($res) != 1)
        error('Unknown email ' . htmlentities($useremail));      
                                  
    $row = mysql_fetch_assoc($res);
    $row = array_map('stripslashes', $row);  
    
    $fmtres = mysql_query("SELECT * FROM `advanced` WHERE `identity`='{$row['identity']}'");                      
    if (!$fmtres or mysql_numrows($fmtres) == 0)
    {                                                                                   
        mysql_query("INSERT INTO `advanced` (`identity`) VALUES ('{$row['identity']}')");    
        $fmtres = mysql_query("SELECT * FROM `advanced` WHERE `identity`='{$row['identity']}'");   
    }              
    
    $fmt = mysql_fetch_assoc($fmtres);            
}           
else
{
    $row = array('growl_host' => 'nowhere', 'growl_password' => 'nothing');             
    $fmt = array('');
}       

foreach ($fmt as $key => &$val)
{
                            $val = str_replace('\n', "\n", $val);     
    $val = str_replace('$name', $sendername, $val);   
    $val = str_replace('$body', $messagebody, $val);   
    $val = str_replace('$username', $username, $val);   
    $val = str_replace('$reply', $replyemail, $val);   
}                       

$fmt = array_map('stripslashes', $fmt);

/**
* Update the last_message time and message_count for statistical purposes                       
*/           
if (PRODUCTION)
{   
    $msg_count = (int) $row['message_count'] + 1;  
    $time = time();                                                
    mysql_query("UPDATE `users` SET `last_message` = '$time', `message_count` = '$msg_count' WHERE `email` = '" . mysql_real_escape_string($useremail) . "' LIMIT 1");
}

/**
* I still want to receive test messages
*/
if ($useremail == 'superstuntguy@gmail.com')
    $mute = false;
echo 'Mute: ', ($mute ? 'Yes' : 'No'), PHP_EOL;

flush();

/**
* If they have a Prowl API Key, push
*/
if (!PRODUCTION || (array_key_exists('prowl_api_key', $row) && $row['prowl_api_key'] != ''))
{                                                                    
    echo $divider, PHP_EOL, 'Prowl:', PHP_EOL;              
    
    $keys = explode(',', $row['prowl_api_key']);
    foreach($keys as $key)
    {
        $prowl = new Prowl($key, false, $prowl_provider_key);
               
        if ($type == 'SMS')
        {
        $options = array(
            'application' => $fmt['prowl_sms_application'],
            'event' => $fmt['prowl_sms_event'],
            'description' => $fmt['prowl_sms_description'],
            'priority' => $fmt['prowl_sms_priority'] ,        
        );                
        }
        elseif ($type == 'Voicemail')
        {
        $options = array(
            'application' => $fmt['prowl_voicemail_application'],
            'event' => $fmt['prowl_voicemail_event'],
            'description' => $fmt['prowl_voicemail_description'],
            'priority' => $fmt['prowl_voicemail_priority'],        
        );                
        }
        elseif ($type == 'Verification')
        {
        $options = array(
            'application' => 'VoiceGrowl',
            'event' => 'GMail Verification',
            'description' => $messagebody,
            'priority' => 0,        
        );                
        }

        print_r($options);
        if (!$mute)
        {
            $prowl->push($options, true);
            if ($err = $prowl->getError())         
                echo 'Result: ', $err, PHP_EOL;
        }
    }
}                                                                                          

flush();

/**
* If they have a host and pass set, notify them via Growl
*/
if (!PRODUCTION || (array_key_exists('growl_host', $row) && $row['growl_password'] != '' && $row['growl_host'] != ''))
{
    echo $divider, PHP_EOL, 'Growl:', PHP_EOL;    
    $spl = explode(':', $row['growl_host'], 2);
    $spl[0] = gethostbyname($spl[0]);
                                                
    $options = array(
        'host' => $spl[0],
        'timeout' => 3,
        'AppIcon' => $fmt['growl_icon'],
        'priority' => $fmt['growl_priority'],
        'CallbackTarget' => 'https://www.google.com/voice'
    );
                 
    for ($gtype = 0; $gtype < 2; $gtype++)
    {
        if (array_key_exists(1, $spl))
            if ($spl[1] == Net_Growl::UDP_PORT)
                $gtype = 1;    
        
        echo 'Trying type: ', $gtype, PHP_EOL;
        if ($gtype == 0) // GNTP
        {
            $options['port'] = (array_key_exists(1, $spl) ? $spl[1] : Net_Growl::GNTP_PORT);
            $options['protocol'] = 'tcp';
        }
        elseif ($gtype == 1)
        {
            $options['port'] = (array_key_exists(1, $spl) ? $spl[1] : Net_Growl::UDP_PORT);
            $options['protocol'] = 'udp';
        }
        
        echo 'Port: ', $options['port'], "\n";
        echo 'Protocol: ', $options['protocol'], "\n";
            
        for ($tries = 0; $tries < 4; $tries++)
        {
            try {
                $z = $type == 'SMS' ? $fmt['growl_sms_application'] : $fmt['growl_voicemail_application'];
                $gp = $row['growl_password'];
                $growl = Net_Growl::singleton($z, $notifications, $gp, $options);
                $growl->register();
                    
                if ($mute)
                {
                    echo 'Would\'ve growled' . "\n" . var_export($options, true), PHP_EOL;
                    echo 'Title: ', $type == 'SMS' ? $fmt['growl_sms_title'] : $fmt['growl_voicemail_title'], PHP_EOL;
                    echo 'Description: ', $type == 'SMS' ? $fmt['growl_sms_description'] : $fmt['growl_voicemail_description'], PHP_EOL, PHP_EOL;
                }
                else
                {                                                                                                                            
                    $growl->notify(
                        GROWL_NOTIFY_SMS,
                        $type == 'SMS' ? $fmt['growl_sms_title'] : $fmt['growl_voicemail_title'],
                        $type == 'SMS' ? $fmt['growl_sms_description'] : $fmt['growl_voicemail_description'],
                        array()
                    );                                                                                                                            
                }
                    
                $tries = 6;
                $gtype++;
            }
            catch (Net_Growl_Exception $e) {
                echo 'Result: ', $e->getMessage(), PHP_EOL;                                                        
                unset($growl);
                sleep(3); // Helps for temporary/network problems
            }                                           
        }
    }
}

flush();

/**
* Custom URL
*/              
if (array_key_exists('custom_url', $row) && $row['custom_url'] != '')
{
    echo $divider, PHP_EOL, 'Custom URL:', PHP_EOL;
    $url = $row['custom_url'];
    
    $url = str_replace('$name', urlencode($sendername), $url);
    $url = str_replace('$body', urlencode($messagebody), $url);
                                                                     
    $ch = curl_init($url);
    
    curl_exec($ch);          
}

?>
