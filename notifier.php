#!/usr/bin/php5
<?php
chdir('/home/barney/Notification_Broadcast/');
require_once '/home/barney/Notification_Broadcast/Thread.php';
echo "starting Notifier\n";

$message ='';

// Thread required for each corp

$bluep = Thread::create("/home/barney/Notification_Broadcast/bluep.php");
$lawns = Thread::create("/home/barney/Notification_Broadcast/lawns.php");
$pvpu = Thread::create("/home/barney/Notification_Broadcast/pvp-u.php");
$exi = Thread::create("/home/barney/Notification_Broadcast/5exi.php");

while (1)
{
	$bluep_resp='';
	$lawns_resp='';
	$pvpu_resp='';
	$exi_resp='';
	$bluep_resp = $bluep->listen();
	$lawns_resp = $lawns->listen();
	$pvpu_resp = $pvpu->listen();
	$exi_resp = $exi->listen();

	sleep(1);
}
?>
