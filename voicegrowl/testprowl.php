<?php 

    include_once 'includes/ProwlPHP.php';
    include_once 'includes/prowl.inc.php';
    
    $keys = explode(',', $_GET['key']);
    foreach($keys as $key)
    {
        $prowl = new Prowl($key, false, $prowl_provider_key);
        $options = array(
            'application' => 'VoiceGrowl',
            'event' => 'Test Message',
            'description' => 'This is a test message from VoiceGrowl, sent to you by ' . $_SERVER['REMOTE_ADDR'],
            'priority' => 0,     
        );                
        $prowl->push($options, true);
        echo $prowl->getError() . '<br />';
    }
?>
