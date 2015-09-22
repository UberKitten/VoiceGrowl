<?php 

    include_once 'includes/ProwlPHP.php';
    require_once 'includes/mysql.inc.php';
    include_once 'includes/prowl.inc.php'; // Provider key
    
    
    $res = mysql_query("SELECT * FROM `users` WHERE `prowl_api_key`!=''");
    while($row = mysql_fetch_assoc($res))
    {
        echo $row['email'], ' (', $row['prowl_api_key'], '): ';
        $prowl = new Prowl($row['prowl_api_key'], false, $prowl_provider_key);
        $options = array(
            'application' => 'VoiceGrowl',
            'event' => 'Service Change',
            'description' => 'VoiceGrowl users:' . "\n" . 'A recent server change means you need to change a GMail filter setting.' . "\n" . 'See http://bit.ly/5lB4hE for more info.' ."\n\n" . 'Email voicegrowl@ub3rk1tten.com or Twitter @t3hub3rk1tten for support',
            'priority' => 1,
        );
        $prowl->push($options, true);
        if ($err = $prowl->getError())
        {
            echo $err;                                                     
        }    
        echo '<br />';
    }
?>