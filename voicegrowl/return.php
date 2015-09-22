<?php
session_start();

require_once 'includes/GoogleOpenID.php';
$googleLogin = GoogleOpenID::getResponse();

require_once 'includes/defines.php';

if (PRODUCTION)
{
    if ($googleLogin->success())
    {                                              
        require_once 'includes/mysql.inc.php';
        
        $identity = $googleLogin->identity;
        $_SESSION['identity'] = $identity;
        $identity = mysql_real_escape_string($identity);
        
        $user_email = strtolower($googleLogin->email);
        $_SESSION['email'] = $user_email;
        $user_email = mysql_real_escape_string($user_email);
                                                               
        $res = mysql_query("SELECT `email` FROM `users` WHERE `identity` = '$identity'", $con) or die(mysql_error());
        
        if ($res !== FALSE && mysql_numrows($res) != 0) // Existing user
        {    
            $email = mysql_result($res, 0, 'email'); 
            if ($email != stripslashes($user_email)) // Email changed? Update it
            {                                                                       
                mysql_query("UPDATE `users` SET `email` = '$user_email' WHERE `identity` = '$identity'", $con) or die(mysql_error());
            }   
        }
        else // New user
        {
            mysql_query("INSERT INTO `users` (`identity`, `email`) VALUES ('$identity', '$user_email')", $con) or die(mysql_error());
        }
    }
}
else
{
    $_SESSION['identity'] = $googleLogin->identity;
    $_SESSION['email'] = $googleLogin->email;   
}

header('Location: index.php');
?>
<br />
<a href="index.php">Continue</a>












