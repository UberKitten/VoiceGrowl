<?php

if (function_exists('mysql_connect'))
    define('PRODUCTION', true);
else
    define('PRODUCTION', false);

?>
