<?php

function notification_decode ($type) {
	global $type_name,$message,$skip,$ale,$values,$system_sql,$moon_sql;
	
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

	switch ($type) {
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
			
			/*
			Type : 22
			corpID: 98139285
			newCeoID: 92757608
			oldCeoID: 90467611
			*/
			if (array_key_exists ( 'corpID' , $values ))
			{
				$corpid = $values['corpID'];
				$params2['corporationID'] = $corpid;
				$params3['ids'] = $values['newCeoID'];
				$char_name=$ale->eve->CharacterName($params3);
				$row = $char_name->result->getSimpleXMLElement();
				$char = $row->rowset->row[0];
				$attr=$char->attributes();
				$new_ceo_name=$attr['name'];
				$params3['ids'] = $values['oldCeoID'];
				$char_name=$ale->eve->CharacterName($params3);
				$row = $char_name->result->getSimpleXMLElement();
				$char = $row->rowset->row[0];
				$attr=$char->attributes();
				$old_ceo_name=$attr['name'];
				$corp_details = $ale->corp->CorporationSheet($params2);
				$corp_name=$corp_details->result->corporationName;
			}
			
			$message = "CEO for $corp_name has changed from $old_ceo_name to $new_ceo_name";
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
			
			/*
			Sovereignty claim lost (alliance)
			Decode needed :
			Type : 41
			allianceID: 150097440
			corpID: 98066163
			solarSystemID: 3000199
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
			$message = "$corp_name have lost Sovereignty in $system_name";
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
			
			if (array_key_exists ( 'corpID' , $values ))
			{
				$corpid = $values['corpID'];
				$params2['corporationID'] = $corpid; 
				$corp_details = $ale->corp->CorporationSheet($params2);
				$corp_name=$corp_details->result->corporationName;
			}
			if (array_key_exists ( 'allianceID' , $values ))
			{
				$allianceid = $values['allianceID'];
				$params2['allianceID'] = $allianceid; 
				$corp_details = $ale->corp->CorporationSheet($params2);
				$alliance_name=$corp_details->result->allianceName;
			}
			if ($allianceid == 150097440)
			{
				$skip = true;
				Break;
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
										
			$message = "Alliance anchoring alert - $corp_name from $alliance_name anchored a POS in $system_name on Moon $moon_name";
			
			break;
			
			/*
			Type : 45
			allianceID: 150097440
			corpID: 98063259
			corpsPresent:
			- allianceID: 150097440
			  corpID: 1689276971
			  towers:
			  - moonID: 40127708
				typeID: 16213
			  - moonID: 40127707
				typeID: 16213
			  - moonID: 40127663
				typeID: 16213
			  - moonID: 40127705
				typeID: 16213
			  - moonID: 40127706
				typeID: 16213
			  - moonID: 40127710
				typeID: 16213
			- allianceID: 150097440
			  corpID: 709221692
			  towers:
			  - moonID: 40127685
				typeID: 20062
			- allianceID: null
			  corpID: 368731302
			  towers:
			  - moonID: 40127704
				typeID: 16213
			- allianceID: 150097440
			  corpID: 1493993699
			  towers:
			  - moonID: 40127703
				typeID: 16214
			  - moonID: 40127678
				typeID: 20064
			moonID: 40127687
			solarSystemID: 30002001
			typeID: 16213
			*/
			
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
			
			/*
			Station aggression message
			Decode needed : 
			398857786
			Type : 77
			aggressorCorpID: null
			aggressorID: null
			shieldValue: 0.9998302463317125
			solarSystemID: 30004388
			stationID: 61000033
			typeID: 28156
			*/			
			if (array_key_exists ( 'aggressorCorpID' , $values ))
			{
				$corpid = $values['aggressorCorpID'];
				if ($corpid = 'null')
				{
					$alliance_name = 'UNKNOWN';
					$corp_name = 'UNKNOWN';
				}
				else
				{
					$params2['corporationID'] = $corpid; 
					$corp_details = $ale->corp->CorporationSheet($params2);
					$corp_name=$corp_details->result->corporationName;
					$alliance_name=$corp_details->result->allianceName;
				}
			}
			
			if (array_key_exists ( 'solarSystemID' , $values ))
			{
				$systemid = $values['solarSystemID'];
				$system_sql->execute(array(':systemid'=>$systemid));
				while ($system_row=$system_sql->fetch()){
					$system_name = $system_row['sys_name'];
				}
			}
			
			$message = "Station in $system_name under attack by $corp_name from $alliance_name";
			break;
		case '78':
			$type_name="Station state change message";
			
			/* 
			Station state change message
			Decode needed :
			Type : 78
			solarSystemID: 30004341
			state: -1
			stationID: 61000311
			typeID: 28158
			*/
			if (array_key_exists ( 'solarSystemID' , $values ))
			{
				$systemid = $values['solarSystemID'];
				$system_sql->execute(array(':systemid'=>$systemid));
				while ($system_row=$system_sql->fetch()){
					$system_name = $system_row['sys_name'];
				}
			}
			
			$message = "Station in $system_name has changed State";
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
				if ($corpid = 'null')
				{
					$alliance_name = 'UNKNOWN';
					$corp_name = 'UNKNOWN';
				}
				else
				{
					$params2['corporationID'] = $corpid; 
					$corp_details = $ale->corp->CorporationSheet($params2);
					$corp_name=$corp_details->result->corporationName;
					$alliance_name=$corp_details->result->allianceName;
				}
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
				if ($corpid = 'null')
				{
					$alliance_name = 'UNKNOWN';
					$corp_name = 'UNKNOWN';
				}
				else
				{
					$params2['corporationID'] = $corpid; 
					$corp_details = $ale->corp->CorporationSheet($params2);
					$corp_name=$corp_details->result->corporationName;
					$alliance_name=$corp_details->result->allianceName;
				}
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
			$type_name = "Contact notification";
			$message = $type_name;
			break;
		case '90':
			$type_name = "Contact edit notification";
			$message = $type_name;
			break;
		case '91':
			$type_name = "Incursion Completed";
			$message = $type_name;
			break;
		case '92':
			$type_name = "Corp Kicked";
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
}
?>
