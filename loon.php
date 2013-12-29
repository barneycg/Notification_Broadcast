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

$l = fopen("/home/barney/Notification_Broadcast/loon.txt", "a");
fwrite($l, date("c") . "  Starting\n");

$jabcon = new XMPPHP_XMPP($config['jabber']['host'], 5222, $config['jabber']['user'], $config['jabber']['password'], 'Notifier-LOON');
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

class evedb {

	public $pdo;
	public $ale;
	public $temp;
	
	function __construct()
	{
		global $config;
		ini_set("auto_detect_line_endings", true);
		//$config = parse_ini_file("/home/barney/Notification_Broadcast/nb.ini", true);
		$this->pdo = new PDO("mysql:host=".$config['mysql']['host'].";dbname=".$config['mysql']['db_name'], $config['mysql']['user'], $config['mysql']['password'],array(PDO::ATTR_PERSISTENT => false));
		
		$this->api_count_sql = $this->pdo->prepare('select count(*) as count from api_keys where corp = "LOON"');
		$this->previous_sql = $this->pdo->prepare('select notificationID from notification where notificationID= :nid');
		$this->api_sql = $this->pdo->prepare('select id,key_id,vcode,charid from api_keys where corp = "LOON"');
		$this->system_sql = $this->pdo->prepare('select sys_name from eve_systems where sys_id= :systemid');
		$this->moon_sql = $this->pdo->prepare('select itemName from mapDenormalize where itemID= :moonid');
		$this->notified_sql = $this->pdo->prepare('insert into notification values(:nid)');
		
	}
	
	function get()
	{
		$api_count_sql = $this->pdo->prepare('select count(*) as count from api_keys');
		$api_count_sql->execute();
		while ($count_row = $api_count_sql->fetch())
		{
			$api_count=$count_row['count'];
		}
		return $api_count;
	}
	
	
}


$api_count = 0;
$last_api_count=0;
while (1)
{
	$evedb = new evedb();
	
	$evedb->api_count_sql->execute();
	
	while ($count_row = $evedb->api_count_sql->fetch())
	{
		$api_count=$count_row['count'];
	}

	if (($last_api_count == 0) || ($last_api_count != $api_count))
	{
		fwrite($l, date("c") . "  Using $api_count keys\n");
		$last_api_count = $api_count;
	}

        if ($api_count !=0)
                $sleep_time=2100/$api_count;
        else
        {

                sleep(3600);
                continue;
        }

	$evedb->api_sql->execute();
	$result = $evedb->api_sql->fetchall(PDO::FETCH_ASSOC);
	unset($evedb);
	
	foreach ($result as $api_key_row)
	{
		//echo "Processing key id ".$api_key_row['id']."\n";
		$id=(int)$api_key_row['id'];
		//var_dump($ale);
		$ale = AleFactory::getEVEOnline(array(),true);
		
		$key_id=(int)$api_key_row['key_id']; 
		$api_key=(string)$api_key_row['vcode'];
		$characterID=$api_key_row['charid'];

		$ale->setKey((int)$key_id, $api_key, (int)$characterID);

		//all errors are handled by exceptions
		
		try {
			$notification_headers = $ale->char->Notifications();
		}
		catch (Exception $e) {
			$message = date("c") . "  " . $key_id . " - " . $e->getMessage() . "\n";
			fwrite($l, "$message\n");
			continue;
		}
		//var_dump($ale);
		
		//Defining Groups
		$params['hc']['IDs']='';
		$params['alliance_officers']['IDs']='';
		//$params['bph']['IDs']='';
		
		$evedb = new evedb();
		foreach ($notification_headers->result->notifications as $nheader)
		{			
			$notified='0';
			//var_dump($nheader->notificationID);
			$evedb->previous_sql->execute(array(':nid'=>$nheader->notificationID));
		
			while ($previous_row= $evedb->previous_sql->fetch()){
				$notified = $previous_row['notificationID'];
			}
			
			if ($notified == '0')
			{
				if (($nheader->typeID == 4) || ($nheader->typeID == 26) || ($nheader->typeID == 37) || ($nheader->typeID == 39) || ($nheader->typeID == 41) || ($nheader->typeID == 43) || ($nheader->typeID == 92) || ($nheader->typeID == 95))
				{
					$params['hc']['IDs'] .= $nheader->notificationID.",";
					$notifications['hc'][$nheader->notificationID]['type']=$nheader->typeID;
					$notifications['hc'][$nheader->notificationID]['sent']=$nheader->sentDate;
				}
				if (($nheader->typeID == 22)||($nheader->typeID == 38)||($nheader->typeID == 40)||($nheader->typeID == 42)||(($nheader->typeID >= 44)&&($nheader->typeID <= 48))||($nheader->typeID == 75)||(($nheader->typeID >= 77)&&($nheader->typeID <= 79))||(($nheader->typeID >= 86)&&($nheader->typeID <= 88)))
				{
					$params['alliance_officers']['IDs'] .= $nheader->notificationID.",";
					$notifications['alliance_officers'][$nheader->notificationID]['type']=$nheader->typeID;
					$notifications['alliance_officers'][$nheader->notificationID]['sent']=$nheader->sentDate;
				}
				//if (($nheader->typeID == 76))
				//{
				//	$params['bph']['IDs'] .= $nheader->notificationID.",";
                                //        $notifications['bph'][$nheader->notificationID]['type']=$nheader->typeID;
                                //        $notifications['bph'][$nheader->notificationID]['sent']=$nheader->sentDate;
				//}
			}
		}
		unset($notification_headers);
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
	
					notification_decode($notifications[$group][(int)$notice->attributes()->notificationID]['type'],"Blueprint Haus");

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
							fwrite($f, date("c"). "  " . $message2);
							fclose($f);
							$message = $message ."\n\n";
						}
						else
						{
							$message = $message ."\n\n";
						}
						$now = date("d-M-Y H:i:s");
						$message = "This is a Broadcast By the Automated Notification System to the $group Group at $now\n" . "Notification Originally sent : " . $notifications[$group][(int)$notice->attributes()->notificationID]['sent'] . "\n" . $message;
						$rest = substr($notice, -1);
						$bg_to = $group . '@broadcast.lawnalliance.org';
						$jabcon->message($bg_to, $message);
						fwrite($l, "$message\n");
					}
								
					$nid=$notice->attributes()->notificationID;
					$evedb->notified_sql->execute(array(':nid'=>$nid));
				}
			}
		}
		unset($evedb);
		$ale->bye();
		unset($ale);
		sleep ($sleep_time);
		//sleep (5);
	}

}
$jabcon->disconnect();
?>
