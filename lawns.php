#!/usr/bin/php
<?php
require_once '/opt/ale/factory.php';
include("XMPPHP/XMPP.php");
ob_end_flush();

$config = parse_ini_file("nb.ini", true);

//var_dump($config);
//exit;
//username = user without domain, "user" and not "user@server" - home is the resource

$notifications=array();
$message ='';

$pdo = new PDO("mysql:host=".$config['mysql']['host'].";dbname=".$config['mysql']['db_name'], $config['mysql']['user'], $config['mysql']['password']);
$api_count_sql = $pdo->prepare('select count(*) as count from api_keys where corp = "LAWNS"');
$previous_sql = $pdo->prepare('select notificationID from notification where notificationID= :nid');
$api_sql = $pdo->prepare('select id,key_id,vcode,charid from api_keys where corp = "LAWNS"');
$system_sql = $pdo->prepare('select sys_name from eve_systems where sys_id= :systemid');
$moon_sql = $pdo->prepare('select itemName from mapDenormalize where itemID= :moonid');
$notified_sql = $pdo->prepare('insert into notification values(:nid)');

while (1)
{

	$api_count_sql->execute();
	while ($count_row = $api_count_sql->fetch())
	{
		$api_count=$count_row['count'];
	}
	//echo "\nThere are $api_count API's in the db for LAWNS\n\n";

	$sleep_time=2100/$api_count;


	//TODO : Cycle through list of api keys.
	//set user credentials, third parameter $characterID is also possible;	
	
	$api_sql->execute();
	while ($api_key_row = $api_sql->fetch())
	{
		$id=(int)$api_key_row['id'];
		//echo "using api $id\n";
		//$jabcon = new XMPPHP_XMPP($config['jabber']['host'], 5222, $config['jabber']['user'], $config['jabber']['password'], 'Notifier');
		// Enables TLS - enabled by default, call before connect()!
		//$jabcon->useEncryption(true);
		//try {
		//	$jabcon->connect();
		//$jabcon->processUntil('session_start');
			
		//}
		//catch (Exception $e) {
		//	echo $e->getMessage();
		//	continue;
		//}
		
		//and finally, we should handle exceptions

		$ale = AleFactory::getEVEOnline();
		//echo "Fetching key\n";
		$key_id=(int)$api_key_row['key_id']; 
		$api_key=(string)$api_key_row['vcode'];
		$characterID=$api_key_row['charid'];
		//var_dump($key_id);
		//var_dump($api_key);
		//var_dump($characterID);
		//$ale->setCredentials($userID, $apiKey);
		$ale->setKey((int)$key_id, $api_key, (int)$characterID);
	
		//$ale->setCredentials($userID, $apiKey, $characterID);
		//all errors are handled by exceptions
		//echo "getting headers\n";
		try {
			$notification_headers = $ale->char->Notifications();
		}
		catch (Exception $e) {
			echo $key_id." - ";
			echo $e->getMessage()."\n\n";
			continue;
		}
		//var_dump($notification_headers->result);
		$params['HC']['IDs']='';
		//$params['Directors']['IDs']='';
		foreach ($notification_headers->result->notifications as $nheader)
		{			
			$notified='0';

			$previous_sql->execute(array(':nid'=>$nheader->notificationID));
			//var_dump($nheader->typeID);
			while ($previous_row= $previous_sql->fetch()){
				$notified = $previous_row['notificationID'];
			}
			//var_dump($notified);
			$corpid='';
			$systemid='';
			if ($notified == '0')
			{
				//if (($nheader->typeID == 4) || ($nheader->typeID == 26) || ($nheader->typeID == 37) || ($nheader->typeID == 39) || ($nheader->typeID == 41) || ($nheader->typeID == 43) || ($nheader->typeID == 92) || ($nheader->typeID == 95))
				if (true) // (($nheader->typeID >=46) && ($nheader->typeID <=48)) || (($nheader->typeID >=75) && ($nheader->typeID <=80)) || (($nheader->typeID >=86) && ($nheader->typeID <=88)) || (($nheader->typeID >=93) && ($nheader->typeID <=94)) )
				{
					$params['HC']['IDs'] .= $nheader->notificationID.",";
					$notifications['HC'][$nheader->notificationID]['type']=$nheader->typeID;
					$notifications['HC'][$nheader->notificationID]['sent']=$nheader->sentDate;
					//var_dump($nheader->typeID);
				}
				//if (($nheader->typeID == 22)||($nheader->typeID == 38)||($nheader->typeID == 40)||($nheader->typeID == 42)||(($nheader->typeID >= 44)&&($nheader->typeID <= 47))||($nheader->typeID == 75)||(($nheader->typeID >= 77)&&($nheader->typeID <= 79))||(($nheader->typeID >= 86)&&($nheader->typeID <= 88)))
				//if (true) // (($nheader->typeID >=46) && ($nheader->typeID <=48)) || (($nheader->typeID >=75) && ($nheader->typeID <=80)) || (($nheader->typeID >=86) && ($nheader->typeID <=88)) || (($nheader->typeID >=93) && ($nheader->typeID <=94)) )
				//{
				//	$params['Directors']['IDs'] .= $nheader->notificationID.",";
				//	$notifications['Directors'][$nheader->notificationID]['type']=$nheader->typeID;
					//var_dump($nheader->typeID);
				//}
			}
		}
		
		foreach (array_keys($params) as $group)
		{
			$params[$group]['IDs'] = substr($params[$group]['IDs'],0,-1);
			//var_dump($params);	
						
			//echo "Getting bodies\n";
			if ($params[$group]['IDs']!='')
			{
				$notification_bodies = $ale->char->NotificationTexts($params[$group]);
				
				$row = $notification_bodies->result->getSimpleXMLElement();
				foreach ($row->rowset->row as $notice)
				{
					//$row = $row->rowset->row ;
					//echo "-----Notice-----\n"."Type : ".$notifications[$group][(int)$notice->attributes()->notificationID]['type']."\n".$notice."\n";
$corpid='';
					$corpid='';
					$systemid='';
					$corp_details='';
					$corp_name='';
					$system_name='';
					$moonid='';
					$moon_details='';
					$moon_name='';
					$alliance_name='';
					$character_name='';
					$to_corp='';
					$from_corp='';
					$structure_name='';
						
					preg_match_all('/^(.+?): (.+?)$/m', (string)$notice, $m);
					$values = array();
					//var_dump($m[1]);
					for($i=0;$i<count($m[1]);$i++) {
						$values[$m[1][$i]] = $m[2][$i];
					}
	
			
					switch ($notifications[$group][(int)$notice->attributes()->notificationID]['type']) {
						case '2':
							$type_name="Character deleted";
							$message = $type_name;
							break;
						case '3':
							$type_name="Give medal to character";
							$message = $type_name;
							break;
						case '4':
							$type_name="Alliance maintenance bill";
							$message = $type_name;
							break;
						case '5':
							$type_name="Alliance war declared";
							$message = $type_name;
							break;
						case '6':
							$type_name="Alliance war surrender";
							$message = $type_name;
							break;
						case '7':
							$type_name="Alliance war retracted";
							$message = $type_name;
							break;
						case '8':
							$type_name="Alliance war invalidated by Concord";
							$message = $type_name;
							break;
						case '9':
							$type_name="Bill issued to a character";
							$message = $type_name;
							break;
						case '10':
							$type_name="Bill issued to corporation or alliance";
							$message = ".." . $type_name;
							break;
						case '11':
							$type_name="Bill not paid because there's not enough ISK available";
							$message = $type_name;
							break;
						case '12':
							$type_name="Bill, issued by a character, paid";
							$message = $type_name;
							break;
						case '13':
							$type_name="Bill, issued by a corporation or alliance, paid";
							$message = ".." . $type_name;
							break;
						case '14':
							$type_name="Bounty claimed";
							$message = $type_name;
							break;
						case '15':
							$type_name="Clone activated";
							$message = $type_name;
							break;
						case '16':
							$type_name="New corp member application";
							$message = ".." . $type_name;
							break;
						case '17':
							$type_name="Corp application rejected";
							$message = $type_name;
							break;
						case '18':
							$type_name="Corp application accepted";
							$message = $type_name;
							break;
						case '19':
							$type_name="Corp tax rate changed";
							$message = $type_name;
							break;
						case '20':
							$type_name="Corp news report, typically for shareholders";
							$message = $type_name;
							break;
						case '21':
							$type_name="Player leaves corp";
							$message = $type_name;
							break;
						case '22':
							$type_name="Corp news, new CEO";
							$message = $type_name;
							break;
						case '23':
							$type_name="Corp dividend/liquidation, sent to shareholders";
							$message = $type_name;
							break;
						case '24':
							$type_name="Corp dividend payout, sent to shareholders";
							$message = $type_name;
							break;
						case '25':
							$type_name="Corp vote created";
							$message = $type_name;
							break;
						case '26':
							$type_name="Corp CEO votes revoked during voting";
							$message = $type_name;
							break;
						case '27':
							$type_name="Corp declares war";
							$message = $type_name;
							break;
						case '28':
							$type_name="Corp war has started";
							$message = $type_name;
							break;
						case '29':
							$type_name="Corp surrenders war";
							$message = $type_name;
							break;
						case '30':
							$type_name="Corp retracts war";
							$message = $type_name;
							break;
						case '31':
							$type_name="Corp war invalidated by Concord";
							$message = $type_name;
							break;
						case '32':
							$type_name="Container password retrieval";
							$message = $type_name;
							break;
						case '33':
							$type_name="Contraband or low standings cause an attack or items being confiscated";
							$message = $type_name;
							break;
						case '34':
							$type_name="First ship insurance";
							$message = $type_name;
							break;
						case '35':
							$type_name="Ship destroyed, insurance payed";
							$message = $type_name;
							break;
						case '36':
							$type_name="Insurance contract invalidated/runs out";
							$message = $type_name;
							break;
						case '37':
							$type_name="Sovereignty claim fails (alliance)";
							$message = $type_name;
							break;
						case '38':
							$type_name="Sovereignty claim fails (corporation)";
							$message = $type_name;
							break;
						case '39':
							$type_name="Sovereignty bill late (alliance)";
							$message = $type_name;
							break;
						case '40':
							$type_name="Sovereignty bill late (corporation)";
							$message = $type_name;
							break;
						case '41':
							$type_name="Sovereignty claim lost (alliance)";
							$message = $type_name;
							break;
						case '42':
							$type_name="Sovereignty claim lost (corporation)";
							$message = $type_name;
							break;
						case '43':
							$type_name="Sovereignty claim acquired (alliance)";
							$message = $type_name;
							break;
						case '44':
							$type_name="Sovereignty claim acquired (corporation)";
							$message = $type_name;
							break;
						case '45':
							$type_name="Alliance anchoring alert";
							$message = $type_name;
							break;
						case '46':
							$type_name="Alliance structure turns vulnerable";
							$message = $type_name;
							break;
						case '47':
							$type_name="Alliance structure turns invulnerable";
							$message = $type_name;
							break;
						case '48':
							$type_name="Sovereignty disruptor anchored";
							$message = $type_name;
							break;
						case '49':
							$type_name="Structure won/lost";
							$message = $type_name;
							break;
						case '50':
							$type_name="Corp office lease expiration notice";
							$message = $type_name;
							break;
						case '51':
							$type_name="Clone contract revoked by station manager";
							$message = $type_name;
							break;
						case '52':
							$type_name="Corp member clones moved between stations";
							$message = $type_name;
							break;
						case '53':
							$type_name="Clone contract revoked by station manager";
							$message = $type_name;
							break;
						case '54':
							$type_name="Insurance contract expired";
							$message = $type_name;
							break;
						case '55':
							$type_name="Insurance contract issued";
							$message = $type_name;
							break;
						case '56':
							$type_name="Jump clone destroyed";
							$message = $type_name;
							break;
						case '57':
							$type_name="Jump clone destroyed";
							$message = $type_name;
							break;
						case '58':
							$type_name="Corporation joining factional warfare";
							$message = $type_name;
							break;
						case '59':
							$type_name="Corporation leaving factional warfare";
							$message = $type_name;
							break;
						case '60':
							$type_name="Corporation kicked from factional warfare on startup because of too low standing to the faction";
							$message = $type_name;
							break;
						case '61':
							$type_name="Character kicked from factional warfare on startup because of too low standing to the faction";
							$message = $type_name;
							break;
						case '62':
							$type_name="Corporation in factional warfare warned on startup because of too low standing to the faction";
							$message = $type_name;
							break;
						case '63':
							$type_name="Character in factional warfare warned on startup because of too low standing to the faction";
							$message = $type_name;
							break;
						case '64':
							$type_name="Character loses factional warfare rank";
							$message = $type_name;
							break;
						case '65':
							$type_name="Character gains factional warfare rank";
							$message = $type_name;
							break;
						case '66':
							$type_name="Agent has moved";
							$message = $type_name;
							break;
						case '67':
							$type_name="Mass transaction reversal message";
							$message = $type_name;
							break;
						case '68':
							$type_name="Reimbursement message";
							$message = $type_name;
							break;
						case '69':
							$type_name="Agent locates a character";
							$message = $type_name;
							break;
						case '70':
							$type_name="Research mission becomes available from an agent";
							$message = $type_name;
							break;
						case '71':
							$type_name="Agent mission offer expires";
							$message = $type_name;
							break;
						case '72':
							$type_name="Agent mission times out";
							$message = $type_name;
							break;
						case '73':
							$type_name="Agent offers a storyline mission";
							$message = $type_name;
							break;
						case '74':
							$type_name="Tutorial message sent on character creation";
							$message = $type_name;
							break;
						case '75':
							$type_name="Tower alert";
							$message = $type_name;
							break;
						case '76':
							$type_name="Tower resource alert";

							/*
							allianceID: 150097440 
							corpID: 709221692
							moonID: 40126125
							solarSystemID: 30001975
							*/
							if (array_key_exists ( 'corpID' , $values ))
							{
								$corpid = $values['corpID'];
								$params2['corporationID'] = $corpid; 
								$corp_details = $ale->corp->CorporationSheet($params2);
								$corp_name=$corp_details->result->corporationName;
							}
							if (array_key_exists ( 'solarSystemID' , $values ))
							{
								$systemid = $values['solarSystemID'];
								$system_sql->execute(array(':systemid'=>$systemid));
								while ($system_row=$system_sql->fetch()){
									$system_name = $system_row['sys_name'];
								}
							}
							if (array_key_exists ( 'moonID' , $values ))
                                                        {
                                                                $moonid  = $values['moonID'];
                                                                $moon_sql->execute(array(':moonid'=>$moonid));
                                                                while ($moon_row=$moon_sql->fetch()){
                                                                        $moon_name = $moon_row['itemName'];
                                                                }
                                                        }	
							
							$message = "POS in $moon_name owned by $corp_name is low on resources";

							break;
						case '77':
							$type_name="Station aggression message";
							$message = $type_name;
							break;
						case '78':
							$type_name="Station state change message";
							$message = $type_name;
							break;
						case '79':
							$type_name="Station conquered message";
							$message = $type_name;
							break;
						case '80':
							$type_name="Station aggression message";
							$message = $type_name;
							break;
						case '81':
							$type_name="Corporation requests joining factional warfare";
							$message = $type_name;
							break;
						case '82':
							$type_name="Corporation requests leaving factional warfare";
							$message = $type_name;
							break;
						case '83':
							$type_name="Corporation withdrawing a request to join factional warfare";
							$message = $type_name;
							break;
						case '84':
							$type_name="Corporation withdrawing a request to leave factional warfare";
							$message = $type_name;
							break;
						case '85':
							$type_name="Corporation liquidation";
							$message = $type_name;
							break;
						case '86':
							$type_name="Territorial Claim Unit under attack";
							$message = $type_name;
							break;
						case '87':
							$type_name="Sovereignty Blockade Unit under attack";
							
							/*
							aggressorAllianceID: 1727758877
							aggressorCorpID: 98100699
							aggressorID: 1080080234
							armorValue: 1.0
							hullValue: 1.0
							shieldValue: 0.7882259002445235
							solarSystemID: 30000866
							*/
							
							if (array_key_exists ( 'aggressorCorpID' , $values ))
							{
								$corpid = $values['aggressorCorpID'];
								$params2['corporationID'] = $corpid; 
								$corp_details = $ale->corp->CorporationSheet($params2);
								$corp_name=$corp_details->result->corporationName;
								$alliance_name=$corp_details->result->allianceName;
							}
							if (array_key_exists ( 'solarSystemID' , $values ))
							{
								$systemid = $values['solarSystemID'];
								$system_sql->execute(array(':systemid'=>$systemid));
								while ($system_row=$system_sql->fetch()){
									$system_name = $system_row['sys_name'];
								}
							}
							
							$message = "SBU in $system_name under attack by $corp_name from $alliance_name";
							break;
						case '88':
							$type_name="Infrastructure Hub under attack";
							
							if (array_key_exists ( 'aggressorCorpID' , $values ))
							{
								$corpid = $values['aggressorCorpID'];
								$params2['corporationID'] = $corpid; 
								$corp_details = $ale->corp->CorporationSheet($params2);
								$corp_name=$corp_details->result->corporationName;
								$alliance_name=$corp_details->result->allianceName;
							}
							if (array_key_exists ( 'solarSystemID' , $values ))
							{
								$systemid = $values['solarSystemID'];
								$system_sql->execute(array(':systemid'=>$systemid));
								while ($system_row=$system_sql->fetch()){
									$system_name = $system_row['sys_name'];
								}
							}
							
							$message = "iHub in $system_name under attack by $corp_name from $alliance_name";
							break;
						case '89':
							$type_name="Contact notification";
							$message = $type_name;
							break;
						case '93':
							$type_name="Customs office has been attacked";
							$message = $type_name;
							break;
						case '94':
							$type_name="Customs office has entered reinforced";
							$message = $type_name;
							break;
						case '95':
							$type_name="Customs office has been transferred";
							
							/*
							characterLinkData:
							- showinfo
							- 1385
							- 91355974
							characterName: Hare Crishna Vulpine
							fromCorporationLinkData:
							- showinfo
							- 2
							- 147945511
							fromCorporationName: Get Off My Lawn HC
							solarSystemLinkData:
							- showinfo
							- 5
							- 30001998
							solarSystemName: WW-KGD
							structureLinkData:
							- showinfo
							- 2233
							- 1005892181463
							structureName: Customs Office (WW-KGD III)
							toCorporationLinkData:
							- showinfo
							- 2
							- 98139285
							toCorporationName: Love All Woodland Nymphs
							*/
							
							if (array_key_exists ( 'characterName' , $values ))
							{
								$character_name = $values['characterName'];
							}
							if (array_key_exists ( 'fromCorporationName' , $values ))
							{
								$from_corp = $values['fromCorporationName'];
							}
							if (array_key_exists ( 'solarSystemName' , $values ))
							{
								$system_name = $values['solarSystemName'];
							}
							if (array_key_exists ( 'structureName' , $values ))
							{
								$structure_name = $values['structureName'];
							}
							if (array_key_exists ( 'toCorporationName' , $values ))
							{
								$to_corp = $values['toCorporationName'];
							}
							
							$message = "$structure_name in $system_name transferred from $from_corp to $to_corp by $character_name";
							
							break;
					}		
					//var_dump($type_name);
					//$type_name = $nheader->typeID;
					if ($type_name==$message)
					{
						$message = $message . "\nDecode needed : \n" . "Type : ".$notifications[$group][(int)$notice->attributes()->notificationID]['type']."\n".$notice."\n";
					}
					else
					{
						$message = $message ."\n\n";
					}
					$now = date("d-M-Y H:i:s");
					$message = "This is a Broadcast By the Automated Notification System to the $group Group at $now\n" . "Notification Origianlly sent : " . $notifications[$group][(int)$notice->attributes()->notificationID]['sent'] . "\n" . $message;
					$rest = substr($notice, -1);
					echo $message . $rest."\n\n";
					//var_dump($message);
					//$jabcon->message('director_gnome@lawnalliance.org', $message);
					//$jabcon->message('tb1@broadcast.lawnalliance.org', $message);
					$message = '';					
					$nid=$notice->attributes()->notificationID;
					$notified_sql->execute(array(':nid'=>$nid));
				}
			}
		}
		//$jabcon->disconnect();
		//echo "LAWNS sleeping $sleep_time seconds\n";
		sleep ($sleep_time);
		//sleep (5);
		//echo "next\n";
	}	
}
?>
