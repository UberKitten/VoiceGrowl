<?php

for($tries = 0; $tries <= 5; $tries++)
{
    $con = mysql_connect("database", "voicegrowl", "password");
    if ($con)
        $tries = 6;
    else
        sleep(1);
}
mysql_select_db("voicegrowl", $con);

?>