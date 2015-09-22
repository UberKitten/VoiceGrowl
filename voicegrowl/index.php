<?php 

session_start();                       
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <title>VoiceGrowl - Instant Google Voice Prowl and Growl Notifications</title>
        <link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection"/>
        <link rel="stylesheet" href="blueprint/src/forms.css" type="text/css" media="screen, projection"/>
        <link rel="stylesheet" href="blueprint/print.css" type="text/css" media="print"/>
        <!--[if lt IE 8]>
          <link rel="stylesheet" href="blueprint/ie.css" type="text/css" media="screen, projection"/>
        <![endif]-->
        <style type="text/css">
        .right { text-align: right; }
        .center { text-align: center; }   
        .largealt { font-size: 1.2em; }        
        textarea { height: 100px; }    
        </style>
    </head>
    <body>
        <script type="text/javascript">
              var _gaq = _gaq || [];
              _gaq.push(['_setAccount', 'UA-967859-7']);
              _gaq.push(['_trackPageview']);

              (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
              })();

            function testProwl(apiKey) {
                var xmlHttpReq = false;
                var self = this;
                // Mozilla/Safari
                if (window.XMLHttpRequest) {
                    self.xmlHttpReq = new XMLHttpRequest();
                }
                // IE
                else if (window.ActiveXObject) {
                    self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
                }
                self.xmlHttpReq.open('GET', 'testprowl.php?key=' + apiKey, true);
                self.xmlHttpReq.send();
                self.xmlHttpReq.onreadystatechange = function() {
                    if (self.xmlHttpReq.readyState == 4) {
                        document.getElementById('testProwlResult').innerHTML = self.xmlHttpReq.responseText;
                    }
                }
            }
        </script>
        
        <div class="container">
            <div class="span-24 last">
                <div class="span-13">
                    <img src="https://www.google.com/accounts/grandcentral/voice-logo.png" />
                    <img src="http://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Growl.png/120px-Growl.png" style="height: 70px;"/>
                    <img src="http://dl.getdropbox.com/u/156341/Prowl.png" />
                    <h3>VoiceGrowl - Google Voice Growl and Prowl Notifications</h3>
                </div>
                <div class="span-11 prepend-top right large last">      
                    <?php
                    if (array_key_exists('identity', $_SESSION)) {
                        echo $_SESSION['email'];
                    ?>
                    
                    - <a href="index.php" onclick="pageTracker._link(this.href); return false;">Home</a> <a href="index.php?settings" onclick="pageTracker._link(this.href); return false;">Settings</a> <a href="index.php?privacy">Privacy</a> <a href="login.php" onclick="pageTracker._link(this.href); return false;">Sign out</a>
                    
                    <?php
                    } else {
                    ?>
                    
                    <a href="login.php" onclick="pageTracker._link(this.href); return false;">Sign in with your Google Account</a> - <a href="index.php?privacy">Privacy</a>
                    
                    <?php
                    }                                              
                    ?>
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="HCYR3WPAD576J">
                        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="Donate">
                        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>

                </div>
            </div>
        </div>
        
        <div class="container">
            <?php
            if (array_key_exists('settings', $_REQUEST) && array_key_exists('identity', $_SESSION)) {
                require_once 'includes/defines.php';
                if (PRODUCTION)
                    include_once 'includes/mysql.inc.php';          
                
                if (PRODUCTION)
                    $id = mysql_real_escape_string($_SESSION['identity']);
                else
                    $id = $_SESSION['identity'];
                $host_fail = false;
                $pass_fail = false;
                $api_key_fail = false;
                $custom_url_fail = false;
                $growl_exception = false;
                $mysql_success = false;
                $mysql_fail = false;
                
                if (array_key_exists('submit', $_POST))
                {
                    $vars = $_POST;
                    $vars = array_map('html_entity_decode', $vars);
                    
                    // Validation
                    if ($vars['growl_host'] != '')
                        if (!preg_match('/^[a-zA-Z0-9\-:.]+$/', $vars['growl_host']))
                            $host_fail = true;
                    
                    if ($vars['growl_password'] != '')
                        if (preg_match('/[\n\r]+/', $vars['growl_password']))
                            $pass_fail = true;
                    
                    if (!$host_fail && !$pass_fail && $vars['growl_password'] != '' & $vars['growl_host'] != '')
                    {
                        $spl = explode(':', $vars['growl_host'], 2);
                        $spl[0] = gethostbyname($spl[0]);
                        include_once 'includes/Net/Growl.php';
                        $options = array(
                            'host' => $spl[0],
                            'protocol' => 'tcp',
                            'port' => (array_key_exists(1, $spl) ? $spl[1] : Net_Growl::GNTP_PORT),
                            'timeout' => 10,
                            'AppIcon' => 'http://www.growlforwindows.com/gfw/images/plugins/googlevoice.png',
                            'debug' => true
                        );
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
                        
                        for ($tries = 0; $tries <= 5; $tries++);
                        {
                            try {
                                $growl = Net_Growl::singleton($z = 'Google Voice', $notifications, $vars['growl_password'], $options);
                                $growl->register();
                                
                                $tries = 6;
                            }
                            catch (Net_Growl_Exception $e) {
                                unset($growl);
                                sleep(1); // Helps for temporary problems
                                if ($tries == 5)
                                {
                                    $growl_exception = '<div class="notice">' . $e->getMessage();
                                    $growl_exception .= '<br />I\'ll still try and send you notifications, though.<br />Make sure you double check your password!</div>';
                                }
                            }
                        }
                    } 
                    
                    if ($vars['prowl_api_key'] != '')
                    {
                        include_once 'includes/ProwlPHP.php';
                        include_once 'includes/prowl.inc.php';
                        $prowl = new Prowl();
                        
                        $keys = explode(',', $vars['prowl_api_key']);
                        foreach($keys as $key)
                        {
                            if (!$prowl->verify($key, $prowl_provider_key))
                                $api_key_fail = true;    
                        }
                    }
                    
                    if ($vars['custom_url'] != '')
                    {
                        $url = $vars['custom_url'];  
                        
                        $url = str_replace('$name', 'name', $url);
                        $url = str_replace('$body', 'body', $url);
                        
                        if (!filter_var($url, FILTER_VALIDATE_URL))
                            $custom_url_fail = true;
                    }
                    
                    if (!$host_fail && !$pass_fail && !$api_key_fail && !$custom_url_fail)
                    {
                        $vars = array_map('mysql_real_escape_string', $vars);
                        $res = mysql_query("UPDATE `users` SET `growl_password` = '{$vars['growl_password']}', `growl_host` = '{$vars['growl_host']}', `prowl_api_key` = '{$vars['prowl_api_key']}', `custom_url` = '{$vars['custom_url']}' WHERE `identity` = '$id' LIMIT 1") or die(mysql_error());
                        
                        if ($res)
                            $mysql_success = true;
                        else
                            $mysql_fail = true;
                    }
                }
                
                
                if (PRODUCTION)
                {
                    $res = mysql_query("SELECT `email`, `growl_password`, `growl_host`, `prowl_api_key`, `custom_url` FROM `users` WHERE `identity`='$id'");
                    if (mysql_numrows($res) > 0)
                    {
                        $row = mysql_fetch_assoc($res);
                        $row = array_map('stripslashes', $row);
                        $row = array_map('htmlentities', $row);
                    }
                }
                else
                {
                    $row = array(
                        'email' => $_SESSION['email'],
                        'growl_password' => 'growl pass',
                        'growl_host' => 'example.com',
                        'prowl_api_key' => 'sjdkoasjdklajsdl;kajsdl;k',
                        'custom_url' => 'http://google.com'
                    );   
                }
            ?>
                <div class="prepend-3 span-18 append-3 last" style="font-size: 1.2em">
                    <div align="center">
                        If you use Google Apps and experience problems, please <a href="mailto:voicegrowl@ub3rk1tten.com">email support</a> to get your account set up.<br />    
                        <span class="large">
                            Only fill out what you want, you can leave everything else blank.<br />   
                        </span>
                        <?php if (isset($_GET['gmailsuccess'])) echo '<div class="success">GMail and Google Voice configured!<br />Just edit the settings below to finish</div>'; ?>
                        <?php if ($mysql_success) echo '<div class="success">Settings saved! <a href="index.php" onclick="pageTracker._link(this.href); return false;">(go back)</a></div>'; ?>
                        <?php if ($mysql_fail) echo '<div class="error">An unknown error occurred!</div>'; ?>
                    </div>
                    <form action="index.php?settings" method="POST" onSubmit="javascript:pageTracker._linkByPost(this)" name="vgsettings" id="vgsettings">
                        <fieldset>
                            <legend>Prowl Settings</legend>                                                               
                            <?php if ($api_key_fail) echo '<div class="error">Invalid API Key!</div>'; ?>
                            <label for="prowl_api_key">Prowl API Key</label> - Login and get one <a href="https://prowl.weks.net/settings.php">here</a><br />
                            Separate multiple keys with commas.<br />
                            <input type="text" class="title span-13" name="prowl_api_key" value="<?php echo $row['prowl_api_key']; ?>"/><br />
                            <span id="testProwlResult"></span>
                            <div align="right">
                                <input type="button" value="Send test message" onClick="testProwl(document.vgsettings.elements['prowl_api_key'].value)" />        
                            </div>
                        </fieldset>
                        
                        <fieldset>
                            <legend>Growl Settings</legend>    
                            In order to use this, you must have Growl (<a href="http://www.growlforwindows.com/gfw/">Windows</a>, <a href="http://growl.info">Mac</a>) and a knowledge of how to <a href="http://portforward.com/">port forward</a>.<br />
                            Windows uses TCP port 23053, Mac uses UDP port 9887.<br />
                            <br />
                            <?php if ($growl_exception) echo $growl_exception; ?>                        
                            <?php if ($host_fail) echo '<div class="error">Invalid hostname!</div>'; ?>
                            <label for="growl_host">Growl Hostname</label> - Domains allowed, port optional<br />
                            <input type="text" class="title" name="growl_host" value="<?php echo $row['growl_host']; ?>"/><br />        
                            <?php if ($pass_fail) echo '<div class="error">Invalid password!</div>'; ?>
                            <label for="growl_password">Growl Password</label><br />
                            <input type="password" class="title" name="growl_password" value="<?php echo $row['growl_password']; ?>"/>       
                        </fieldset>
                        
                        <fieldset>
                            <legend>Custom URL Settings</legend>                                                        
                            <?php if ($custom_url_fail) echo '<div class="error">Invalid Custom URL!</div>'; ?>                   
                            This URL is called every time an SMS or voicemail is received, allowing for custom scripting of messages. The variables $name and $body are replaced with the message sender and body.<br />
                            Example: http://example.com/index.php?sender=<strong>$name</strong>&msg=<strong>$body</strong><br />        
                            <input type="text" class="title span-17" name="custom_url" value="<?php echo $row['custom_url']; ?>"/>    
                        </fieldset>
                        <p align="right">
                            <input type="submit" name="submit" value="Save Settings" /> 
                            <input type="button" value="Cancel" onClick="window.location = 'index.php'" />
                            <input type="button" value="Advanced Settings" onClick="window.location = 'index.php?advanced'" />
                        </p>    
                    </form>    
                </div>      
            <?php
            }
            elseif (array_key_exists('advanced', $_REQUEST) && array_key_exists('identity', $_SESSION)) {
                require_once 'includes/defines.php';
                if (PRODUCTION)                      
                    include_once 'includes/mysql.inc.php';          
                    
                    
                
                if (PRODUCTION)
                    $id = mysql_real_escape_string($_SESSION['identity']);
                else
                    $id = $_SESSION['identity'];           
                $mysql_success = false;
                $mysql_fail = false;
                
                if (array_key_exists('submit', $_POST))
                {
                    $vars = $_POST;
                    $vars = array_map('html_entity_decode', $vars);
                    $vars = array_map('mysql_real_escape_string', $vars);            
                    
                    $upd = '';
                    $temp = $vars;
                    unset($temp['identity']);
                    unset($temp['submit']);
                    foreach($temp as $key => $val)
                    { 
                        $val = str_replace('\r\n', "\n", $val);
                        $val = str_replace('\n', "\n", $val);
                        
                        $upd .= '`' . mysql_real_escape_string($key) . '`=\'' . mysql_real_escape_string($val) . '\', ';  
                    }
                    $upd = substr($upd, 0, strlen($upd) - 2) . ' ';                                
                    
                    $res = mysql_query("UPDATE `advanced` SET $upd WHERE `identity` = '$id' LIMIT 1") or die(mysql_error());
                    
                    if ($res)
                        $mysql_success = true;
                    else
                        $mysql_fail = true;      
                }                                      
                
                if (PRODUCTION)
                {
                    if (array_key_exists('reset', $_GET))
                        mysql_query("DELETE FROM `advanced` WHERE `identity`='$id' LIMIT 1");
                        
                    $res = mysql_query("SELECT * FROM `advanced` WHERE `identity`='$id'");                      
                    if (!$res or mysql_numrows($res) == 0)
                    {                                                                                   
                        mysql_query("INSERT INTO `advanced` (`identity`) VALUES ('$id')");    
                        $res = mysql_query("SELECT * FROM `advanced` WHERE `identity`='$id'");     
                    }        
                    
                    if (mysql_numrows($res) > 0)
                    {
                        $row = mysql_fetch_assoc($res);
                        
                        foreach($row as $key => &$val)
                        {                                     
                            $val = str_replace('\n', "\n", $val);      
                        }
                        
                        $row = array_map('stripslashes', $row);
                        $row = array_map('htmlentities', $row);
                    }                                                
                }
            ?>
                <div class="prepend-3 span-18 append-3 last" style="font-size: 1.2em">
                    <div align="center">     
                        <span class="large">
                            These are advanced formatting settings for advanced users.<br />
                            As such, minimal support is provided.<br />
                        </span>            
                        <?php if ($mysql_success) echo '<div class="success">Settings saved! <a href="index.php" onclick="pageTracker._link(this.href); return false;">(go back)</a></div>'; ?>
                        <?php if ($mysql_fail) echo '<div class="error">An unknown error occurred!</div>'; ?>
                    </div>                                       
                    The supported variables, usable in every field, are:<br />
                    <strong>$name</strong> - The phone number (or name, if they have a contact entry) of the sender<br />   
                    <strong>$body</strong> - The message text<br />   
                    <strong>$username</strong> - Your GMail username (no domain)<br />            
                    <strong>$reply</strong> - An email address that this message can be replied to<br />
                    <br />                                                         
                    Priority is on a scale from -2 to 2:<br />        
                    <strong>-2</strong> - Very Low<br />
                    <strong>-1</strong> - Moderate<br />
                    <strong>0</strong> - Normal<br />
                    <strong>1</strong> - High<br />
                    <strong>2</strong> - Emergency<br />
                    <br />                 
                    <form action="index.php?advanced" method="POST" onSubmit="javascript:pageTracker._linkByPost(this)">
                        <fieldset>
                            <legend>Prowl Formatting Settings</legend>        
                            Notifications show up as "Application -- Event\n Description"<br />  
                            <br />             
                            <label for="prowl_sms_application">Prowl SMS Application</label><br />                                                                
                            <input type="text" class="title span-19" name="prowl_sms_application" value="<?php echo $row['prowl_sms_application']; ?>"/><br />       
                                     
                            <label for="prowl_sms_event">Prowl SMS Event</label><br />
                            <input type="text" class="title span-19" name="prowl_sms_event" value="<?php echo $row['prowl_sms_event']; ?>"/><br />           
                            <label for="prowl_sms_description">Prowl SMS Description</label><br />                                                                
                            <textarea class="title span-19" name="prowl_sms_description"><?php echo $row['prowl_sms_description']; ?></textarea><br /> 
                                                 
                            <label for="prowl_sms_priority">Prowl SMS Priority</label><br />
                            <input type="text" class="title span-2" name="prowl_sms_priority" value="<?php echo $row['prowl_sms_priority']; ?>"/><br />
                                                 
                            <label for="prowl_voicemail_application">Prowl Voicemail Application</label><br />                                                                
                            <input type="text" class="title span-19" name="prowl_voicemail_application" value="<?php echo $row['prowl_voicemail_application']; ?>"/><br />       
                                                 
                            <label for="prowl_voicemail_event">Prowl Voicemail Event</label><br />
                            <input type="text" class="title span-19" name="prowl_voicemail_event" value="<?php echo $row['prowl_voicemail_event']; ?>"/><br />           
                            <label for="prowl_voicemail_description">Prowl Voicemail Description</label><br />                                                                
                            <textarea class="title span-19" name="prowl_voicemail_description"><?php echo $row['prowl_voicemail_description']; ?></textarea><br />   
                             
                            <label for="prowl_voicemail_priority">Prowl Voicemail Priority</label><br />
                            <input type="text" class="title span-2" name="prowl_voicemail_priority" value="<?php echo $row['prowl_voicemail_priority']; ?>"/>                
                        </fieldset>
                        
                        <fieldset>
                            <legend>Growl Formatting Settings</legend>        
                            <label for="growl_icon">Growl Icon</label><br />             
                            <input type="text" class="title span-19" name="growl_icon" value="<?php echo $row['growl_icon']; ?>"/><br />         
                            <label for="growl_priority">Growl Priority</label><br />
                            <input type="text" class="title span-2" name="growl_priority" value="<?php echo $row['growl_priority']; ?>"/><br />    
                            
                            <label for="growl_sms_application">Growl SMS Application</label><br />                                                                
                            <input type="text" class="title span-19" name="growl_sms_application" value="<?php echo $row['growl_sms_application']; ?>"/><br />   
                                              
                            <label for="growl_event">Growl SMS Title</label><br />
                            <input type="text" class="title span-19" name="growl_sms_title" value="<?php echo $row['growl_sms_title']; ?>"/><br />           
                            <label for="growl_description">Growl SMS Description</label><br />                                                                          
                            <textarea class="title span-19" name="growl_sms_description"><?php echo $row['growl_sms_description']; ?></textarea><br />  
                            
                            <label for="growl_voicemail_application">Growl Voicemail Application</label><br />                                                                
                            <input type="text" class="title span-19" name="growl_voicemail_application" value="<?php echo $row['growl_voicemail_application']; ?>"/><br />    
                            
                            <label for="growl_event">Growl Voicemail Title</label><br />
                            <input type="text" class="title span-19" name="growl_voicemail_title" value="<?php echo $row['growl_voicemail_title']; ?>"/><br />           
                            <label for="growl_description">Growl Voicemail Description</label><br />                                                                             
                            <textarea class="title span-19" name="growl_voicemail_description"><?php echo $row['growl_voicemail_description']; ?></textarea><br />   
                        </fieldset>
                                 
                        <p align="right">
                            <input type="submit" name="submit" value="Save Settings"> 
                            <input type="button" value="Cancel" onClick="window.location = 'index.php?settings'">
                            <input type="button" value="Reset All" onClick="window.location = 'index.php?advanced&reset'">
                        </p>    
                    </form>    
                </div>      
            <?php                
            } elseif (array_key_exists('privacy', $_GET)) {
            ?>
                <div class="prepend-3 span-18 append-3 large last">
                    <h2 class="center">Privacy Policy</h2>
                    I, <a href="mailto:astra.west@ub3rk1tten.com">Astra West</a>, the creator of this application/service, regard privacy very highly.
                    (When I use first person, I mean myself, and when I use second person, I mean both me and this application)
                    The existence of this service actually sprouted from that, in that I didn't wish to share my Google username and password with any site or even have to store it in plaintext on my computer.
                    Because of that, I will always take the approach of putting privacy first.
                    <br />                                                                        
                    <br /><strong>Using the site</strong><br />
                    Like many other sites, we use <a href="http://google.com/analytics">Google Analytics</a> to better track the amount of users to this site and various statistics about them.
                    Read the Google <a href="http://www.google.com/privacy_highlights.html">privacy policy</a> for more info.
                    <br />
                    <br /><strong>Logging In</strong><br />
                    When you log in to this site, you are using <a href="http://code.google.com/apis/accounts/docs/OAuth.html">Google's OAuth</a> function to log in.
                    This is necessary to validate GMail addresses, as otherwise any user could potentially hijack an account.
                    When you enter your username and password, they are <strong>sent to Google and not us</strong>.
                    If Google accepts them, Google gives us a token that uniquely identifies you, and your email address.
                    The token allows us to remember your login across multiple sessions, even if your GMail account changes, and your email address is necessary as stated above.
                    <br />
                    <br /><strong>Stored Information</strong><br />
                    In order to effectively utilize this service, it must store a few user details.<br />
                    <br />
                    First, any information you enter on the <a href="index.php?settings">settings</a> page will be stored, un-encrypted.
                    It is stored un-encrypted due to the fact that it needs to be un-encrypted to be used, and that the information is relatively un-confidential originally.
                    You're giving away your Growl password to us, which by definition is compromising it, so it only makes sense that it should be a unique password, not shared with any other service.
                    Even if someone got your password, they would only be able to send your Growl notifications. Only a minor annoyance, really.
                    Growl's password management service makes managing multiple passwords easy.
                    The Prowl API is similar, in that if someone were to use it, they could only slightly annoy you by making your iPhone/iPod Touch buzz.  
                    Growl passwords are transmitted over the network as an MD5 hash, however the library we're using doesn't allow MD5 hashes to be specified, and I have an irrational fear of modifying libraries.<br />
                    <br />
                    Second, your email address and login token are stored, so they can be compared to in the future.
                    The token contains no personally identifiable information.
                    Your email is stored as plaintext rather than a hash to make troubleshooting and sending service notification emails easier.<br />
                    <br />
                    Third, the date and time of the last message you received and how many messages you've received is stored.
                    These are purely for statistical and troubleshooting purposes, and they couldn't be used for very much anyways.
                    <br /><strong>Receiving Emails/Messages</strong><br />
                    When we receive a message forwarded from Google Voice, only the information above is stored.
                    With over 350,000 messages forwarded as of 2/15/2010, it would be 
                    Because we want to make this very clear, the source code to the script that receives email is available <a href="email.phps">here</a>.
                    That's a live view of the source code.
                    Our MySQL information and Prowl API provider key are in separate includes for security.
                    Please note that this does not give you permission to use this code, only to view it.
                    All rights are reserved to me unless you receive permission otherwise.
                    <br />
                    <br /><strong>Third Party Privacy</strong><br />
                    We use <a href="http://smtp2web.com">smtp2web.com</a> as a backup mail server, a service that allows web applications to receive email.
                    Under normal circumstances, all messages will go through our servers alone, but in case our main mail server is down they'll automatically go to the smtp2web backup.
                    This way 
                    We take no responsibility for the privacy of their service, as we can't control it.
                    It logs the time and date of a message received, as well as your GMail address, the size in kb, and whether it succeeded or not.
                    No other information is apparently recorded, however we can't guarantee that that is true.
                    If you have any comments, questions, or concerns, you are encouraged to <a href="mailto:arachnid AT notdot.net">contact its owner</a> or <a href="http://code.google.com/p/smtp2web/">look at its source code</a>.<br />
                    <br />
                    You also have the option of creating an account at <a href="http://smtp2web.com">smtp2web.com</a> and setting up an email forwarder to 'http://googlevoice.ub3rk1tten.com/voicegrowl/email.php' if you'd like to prevent me from viewing those logs.
                    It won't change much, but at least you 'own' part of the process. 
                </div>
            <?php
            } else {
            ?>                         
            <div class="prepend-3 span-18 append-3 last">
                    
                <p class="largealt">           
                    <?php
                    if (array_key_exists('identity', $_SESSION)) {
                    ?>                    
                        <strong>Problems? Get support via <a href="http://twitter.com/home?status=%40t3hub3rk1tten%20">Twitter</a> or <a href="mailto:voicegrowl@ub3rk1tten.com">email</a>!</strong>
                        <h4>1. <a href="index.php?settings">Set up Prowl or Growl</a></h4>    
                        Prowl is for the iPhone, Growl is for the computer.<br />
                        <strong>If all you want to use is Prowl, you don't need to set up Growl, and vice versa.</strong><br />
                        <br />
                        
                        <h4 class="hr">2. <a href="https://www.google.com/voice#voicemailsettings">Set up "SMS Forwarding"</a> in Google Voice</h4>
                        Go to the link above, and check this:<br />
                        <img src="http://dl.getdropbox.com/u/156341/Google%20Voice%20SMS%20Forwarding.png"/>
                        
                        <h4>3. <a href="https://mail.google.com/mail/#settings/filters">Create a GMail filter</a> to forward SMS messages</h4>
                        Go to the link above, and then click:<br />
                        <img class="prepend-4" src="http://dl.getdropbox.com/u/156341/GMail%20Create%20a%20New%20Filter.png" /><br />
                        In the From: box, put <strong>txt.voice.google.com OR voice-noreply@google.com</strong> and click next.<br />
                        <img class="prepend-3" src="http://dl.getdropbox.com/u/156341/GMail%20Create%20a%20Filter%20From.png" /><br /> 
                        <img class="prepend-3" src="http://dl.getdropbox.com/u/156341/GMail%20Create%20a%20Filter%20Forward.png"/><br />
                        Check the box "Forward it to:"
                        <h4>Your specific generated email: <strong>
                        <?php
                        echo mb_substr($_SESSION['email'], 0, mb_strpos($_SESSION['email'], '@')), '.', time();
                        ?>@googlevoice.ub3rk1tten.com
                        </strong></h4>
                        This email may not be the same every time you load this page.<br />
                        Note that you can not send emails to this address, only forwarding works.<br />
                        If you don't want to have SMS emails in your inbox, you can check "Delete it" here.<br />
                        Click "Create Filter" and you're done!<br />
                        <br />
                        <h3>Problems with verification? Read this:</h3>
                        VoiceGrowl will attempt to automatically verify your email address. If you haven't yet, try refreshing the GMail page. It's probably already verified.<br />
                        If you're using Google Apps, just email me and tell me what your GMail address that you logged in with and your Google Apps address is and I'll add it to VoiceGrowl.<br />
                        If you aren't using Google Apps and it's not verified already, then check your Prowl or Growl messages. If you have your Prowl API key or Growl info set in your VoiceGrowl settings (remember to save!), it will send you a notification telling you the confirmation code.<br />
                        If you have your info entered in your VoiceGrowl settings and you're not receiving a notification then try clicking "Re-send email," waiting a few seconds, and refreshing GMail.<br />
                        If that still doesn't work, try removing the forwarding address and using a new one from the setup page. Every time you load that page it gives you a different email.<br />
                        If all this still doesn't work, email me and let me know.
                    <?php
                    } else {
                    ?>
                        <h2 class="center">Instant Growl / Prowl Updates for Google Voice!</h2>   
                        <div class="center">
                            <img src="http://dl.getdropbox.com/u/156341/Example.png" /><br />
                            <img src="http://cache.gawker.com/assets/images/lifehacker/2009/09/gv-gp.jpg" />
                        </div>  
                        This service receives Google Voice SMS / Voicemail emails, and forwards them as Growl or Prowl notifications.
                        There's no polling &mdash; it's all push, so notifications arrive in 3-4 seconds, <strong>faster than a cell phone!</strong><br/><br />
                        It's better than other services like <a href="http://gvmax.com" rel="nofollow">GVMax</a> because it <strong>does not require you to give up your password</strong> and doesn't need to check your account repeatedly.
                        Other services that rely on polling could easily and legitimately be <a href="http://www.techarena.in/news/8188-google-kills-free-sms-messaging-third-party-apps.htm">shut down by Google</a>, because they introduce a lot of server load.
                        Not to mention, <strong>it's totally free, and will be free forever</strong>.<br /><br />
                        <strong><a href="login.php">Log in</a> to get started.<br /></strong>
                    <?php
                    }
                    ?>
                </p>  
            </div>
            
            <?php
            }
            ?>
            
            <div class="hr span-24 right last">
                Created by <a href="http://twitter.com/t3hub3rk1tten">Astra West</a> - Feel free to Twitter <a href="http://twitter.com/home?status=%40t3hub3rk1tten%20">@t3hub3rk1tten</a> or <a href="mailto:voicegrowl@ub3rk1tten.com">email</a> for help<br />
                <strong>
                <?php
                include_once 'includes/mysql.inc.php';
                $res = mysql_query('SELECT SUM(`message_count`), COUNT(`identity`) FROM `users`');
                echo number_format(mysql_result($res, 0, 'SUM(`message_count`)')), ' messages forwarded by ', number_format(mysql_result($res, 0, 'COUNT(`identity`)')), ' users';
                ?>
                </strong>
                </div>
        </div><br />
        <div align="center">
        <script type="text/javascript"><!--
            google_ad_client = "pub-2743249987059957";
            /* 728x90 VoiceGrowl Footer */
            google_ad_slot = "9198136924";
            google_ad_width = 728;
            google_ad_height = 90;
            //-->
        </script>
        <script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
        </script>
        </div>
    </body>
</html>