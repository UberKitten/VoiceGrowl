<?php

require_once 'GoogleVoice.php';

$GV = new GoogleVoice('username', 'password');
$GV->login();

$messages = $GV->get_messages(true);

print_r($messages);
print_r($GV->message_count());

$GV->mark_as($messages[0]['id'], true);
$GV->mark_as($messages[0]['id'], false);

$nums = $GV->get_contact_numbers($messages[0]['contact_id']);
print_r($nums);

echo $GV->_rnr_se() . PHP_EOL;

$phones = $GV->get_phones();
print_r($phones);

//$GV->call_number('', '');
//$GV->send_sms('', 'Test');

$grant = $GV->search_contacts('grant');
print_r($grant);

$newall = $GV->get_all_new_messages();
print_r($newall);
?>
