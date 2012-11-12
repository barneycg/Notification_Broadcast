#!/usr/bin/php
<?php
require_once '/opt/ale/factory.php';
include("XMPPHP/XMPP.php");
require_once './Thread.php';

ini_set("auto_detect_line_endings", true);

$config = parse_ini_file("nb.ini", true);

$notifications=array();
$message ='';

$jabcon = new XMPPHP_XMPP($config['jabber']['host'], 5222, $config['jabber']['user'], $config['jabber']['password'], 'Notifier');
$jabcon->useEncryption(true);
try {
	$jabcon->connect();
	$jabcon->processUntil('session_start');	
	$jabcon->presence($status="Online");
}
catch (Exception $e) {
	echo $e->getMessage();
	exit;
}

$bluep = Thread::create("bluep.php");
$twoold = Thread::create("2-old.php");
$lawns = Thread::create("lawns.php");

while (1)
{
$bluep_resp='';
$twoold_resp='';
$lawns_resp='';
$bluep_resp = $bluep->listen();
$twoold_resp = $twoold->listen();
$lawns_resp = $lawns->listen();


echo $bluep_resp;
if (!empty($bluep_resp))
{
	if (!empty($twoold_resp))
	{
		echo "\n\n";
	}
	else
	{
		echo "\n";
	}
}
echo $twoold_resp;
if (!empty($twoold_resp))
{
	if (!empty($lawns_resp))
        {
                echo "\n\n";
        }
        else
        {
                echo "\n";
        }

}
echo $lawns_resp;
if (!empty($lawns_resp))
{
	echo "\n";
}

if (preg_match("/This is a Broadcast/",$bluep_resp))
{	
	$jabcon->message('tb1@broadcast.lawnalliance.org', $bluep_resp);
}
if (preg_match("/This is a Broadcast/",$twoold_resp))
{
	$jabcon->message('tb1@broadcast.lawnalliance.org', $twoold_resp);
}
if (preg_match("/This is a Broadcast/",$lawns_resp))
{
        $jabcon->message('tb1@broadcast.lawnalliance.org', $lawns_resp);
}



sleep(1);

}
$jabcon->disconnect();
?>
