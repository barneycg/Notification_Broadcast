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
$qs = Thread::create("/home/barney/Notification_Broadcast/qs.php");
$oarg = Thread::create("/home/barney/Notification_Broadcast/oar-g.php");
$vd = Thread::create("/home/barney/Notification_Broadcast/vd.php");
$luc = Thread::create("/home/barney/Notification_Broadcast/luc.php");
$nga4l = Thread::create("/home/barney/Notification_Broadcast/nga4l.php");
$eh = Thread::create("/home/barney/Notification_Broadcast/eh.php");
$gomyh = Thread::create("/home/barney/Notification_Broadcast/gomyh.php");
$loon = Thread::create("/home/barney/Notification_Broadcast/loon.php");
$mbalm = Thread::create("/home/barney/Notification_Broadcast/mbalm.php");
$old = Thread::create("/home/barney/Notification_Broadcast/2old.php");

while (1)
{
	$bluep_resp='';
	$lawns_resp='';
	$pvpu_resp='';
	$exi_resp='';
	$qs_resp='';
	$oarg_resp='';
	$vd_resp='';
	$luc_resp='';
	$nga4l_resp='';
	$eh_resp='';
	$gomyh_resp='';
	$loon_resp='';
	$mbalm_resp='';
	$old_resp='';
	$bluep_resp = $bluep->listen();
	$lawns_resp = $lawns->listen();
	$pvpu_resp = $pvpu->listen();
	$exi_resp = $exi->listen();
	$qs_resp = $qs->listen();
	$oarg_resp = $oarg->listen();
	$vd_resp = $vd->listen();
	$luc_resp = $luc->listen();
	$nga4l_resp = $nga4l->listen();
	$eh_resp = $eh->listen();
	$gomyh_resp = $gomyh->listen();
	$loon_resp = $loon->listen();
	$mblam_resp = $mbalm->listen();
	$old_resp = $old->listen();

	sleep(1);
}
?>
