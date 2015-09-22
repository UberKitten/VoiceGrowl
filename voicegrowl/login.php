<?php 

session_start();

require_once 'includes/GoogleOpenID.php';

if (array_key_exists('identity', $_SESSION))
{
    session_destroy();
    header('Location: index.php');
    exit;
}

$googleLogin = GoogleOpenID::createRequest('voicegrowl/return.php', null, true);
$googleLogin->redirect();

?>


