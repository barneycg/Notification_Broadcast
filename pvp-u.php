#!/usr/bin/php
<?php
require_once '/opt/ale/factory.php';
include("./decodes.php");
include("XMPPHP/XMPP.php");
ob_end_flush();
ini_set("auto_detect_line_endings", true);
$config = parse_ini_file("/home/barney/Notification_Broadcast/nb.ini", true);

$notifications=array();
$message ='';

$pdo = new PDO("mysql:host=".$config['mysql']['host'].";dbname=".$config['mysql']['db_name'], $config['mysql']['user'], $config['mysql']['password']);
$api_count_sql = $pdo->prepare('select count(*) as count from api_keys where corp = "PVP-U"');
$previous_sql = $pdo->prepare('select notificationID from notification where notificationID= :nid');
$api_sql = $pdo->prepare('select id,key_id,vcode,charid from api_keys where corp = "PVP-U"');
$system_sql = $pdo->prepare('select sys_name from eve_systems where sys_id= :systemid');
$moon_sql = $pdo->prepare('select itemName from mapDenormalize where itemID= :moonid');
$notified_sql = $pdo->prepare('insert into notification values(:nid)');

$l = fopen("/home/barney/Notification_Broadcast/pvp-u.txt", "a");
fwrite($l, "Starting\n");

$jabcon = new XMPPHP_XMPP($config['jabber']['host'], 5222, $config['jabber']['user'], $config['jabber']['password'], 'Notifier-PVP-U');
// Enables TLS - enabled by default, call before connect()!
$jabcon->useEncryption(true);
try {
	$jabcon->connect();
	$jabcon->processUntil('session_start');
	$jabcon->presence($status="Online");
}
catch (Exception $e) {
	echo $e->getMessage();
	continue;
}

while (1)
{

	$api_count_sql->execute();
	while ($count_row = $api_count_sql->fetch())
	{
		$api_count=$count_row['count'];
	}

	$sleep_time=2100/$api_count;

	$api_sql->execute();
	while ($api_key_row = $api_sql->fetch())
	{
		$id=(int)$api_key_row['id'];
		
		$ale = AleFactory::getEVEOnline();
		
		$key_id=(int)$api_key_row['key_id']; 
		$api_key=(string)$api_key_row['vcode'];
		$characterID=$api_key_row['charid'];

		$ale->setKey((int)$key_id, $api_key, (int)$characterID);
		
		//all errors are handled by exceptions
		
		try {
			$notification_headers = $ale->char->Notifications();
		}
		catch (Exception $e) {
			$message = $key_id . " - " . $e->getMessage() . "\n";
			fwrite($l, "$message\n");
			continue;
		}
		
		//Defining Groups
		$params['HC']['IDs']='';
		$params['Alliance_Officers']['IDs']='';
		
		foreach ($notification_headers->result->notifications as $nheader)
		{			
			$notified='0';

			$previous_sql->execute(array(':nid'=>$nheader->notificationID));
		
			while ($previous_row= $previous_sql->fetch()){
				$notified = $previous_row['notificationID'];
			}
			
			if ($notified == '0')
			{
				if (($nheader->typeID == 4) || ($nheader->typeID == 26) || ($nheader->typeID == 37) || ($nheader->typeID == 39) || ($nheader->typeID == 41) || ($nheader->typeID == 43) || ($nheader->typeID == 92) || ($nheader->typeID == 95))
				{
					$params['HC']['IDs'] .= $nheader->notificationID.",";
					$notifications['HC'][$nheader->notificationID]['type']=$nheader->typeID;
					$notifications['HC'][$nheader->notificationID]['sent']=$nheader->sentDate;
				}
				if (($nheader->typeID == 22)||($nheader->typeID == 38)||($nheader->typeID == 40)||($nheader->typeID == 42)||(($nheader->typeID >= 44)&&($nheader->typeID <= 47))||($nheader->typeID == 75)||(($nheader->typeID >= 77)&&($nheader->typeID <= 79))||(($nheader->typeID >= 86)&&($nheader->typeID <= 88)))
				{
					$params['Alliance_Officers']['IDs'] .= $nheader->notificationID.",";
					$notifications['Alliance_Officers'][$nheader->notificationID]['type']=$nheader->typeID;
					$notifications['Alliance_Officers'][$nheader->notificationID]['sent']=$nheader->sentDate;
				}
			}
		}
		
		foreach (array_keys($params) as $group)
		{
			$params[$group]['IDs'] = substr($params[$group]['IDs'],0,-1);
	
			if ($params[$group]['IDs']!='')
			{
				$notification_bodies = $ale->char->NotificationTexts($params[$group]);
				
				$row = $notification_bodies->result->getSimpleXMLElement();
				foreach ($row->rowset->row as $notice)
				{
					$skip=false;
					$message = '';	
					preg_match_all('/^([a-zA-Z]+?): (.+?)$/m', (string)$notice, $m);
					$values = array();

					for($i=0;$i<count($m[1]);$i++) {
						$values[$m[1][$i]] = $m[2][$i];
					}

					notification_decode($notifications[$group][(int)$notice->attributes()->notificationID]['type']);

					if (!$skip)
					{
						if ($type_name==$message)
						{
							$message2 = $message . "\nDecode needed : \n" . $notice->attributes()->notificationID . "\nType : ".$notifications[$group][(int)$notice->attributes()->notificationID]['type']."\n".$notice."\n";
							foreach (array_keys($values) as $val_key)
							{
								$message2 .= $val_key ."\n";
							}
							$message2 .= "\n\n";
							$f = fopen("/home/barney/Notification_Broadcast/notifier_decodes.txt", "a");
							fwrite($f, $message2);
							fclose($f);
							$message = $message ."\n\n";
						}
						else
						{
							$message = $message ."\n\n";
						}
						$now = date("d-M-Y H:i:s");
						$message = "This is a Broadcast By the Automated Notification System to the $group Group at $now\n" . "Notification Origianlly sent : " . $notifications[$group][(int)$notice->attributes()->notificationID]['sent'] . "\n" . $message;
						$rest = substr($notice, -1);
						$bg_to = $group . '@broadcast.lawnalliance.org';
						$jabcon->message($bg_to, $message);
						fwrite($l, "$message\n");
					}
								
					$nid=$notice->attributes()->notificationID;
					$notified_sql->execute(array(':nid'=>$nid));
				}
			}
		}
		sleep ($sleep_time);
	}	
}
$jabcon->disconnect();
?>

