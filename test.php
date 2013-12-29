#!/usr/bin/php
<?php
require_once '/opt/ale/factory.php';

class evedb {

	public $pdo;
	public $ale;
	public $temp;
	
	function __construct()
	{
		ini_set("auto_detect_line_endings", true);
		$config = parse_ini_file("/home/barney/Notification_Broadcast/nb.ini", true);
		$this->pdo = new PDO("mysql:host=".$config['mysql']['host'].";dbname=".$config['mysql']['db_name'], $config['mysql']['user'], $config['mysql']['password'],array(PDO::ATTR_PERSISTENT => false));

		$ale = AleFactory::getEVEOnline();
		//unset ($this->temp);
		$ale->bye();
		unset ($ale);
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

$test = new evedb();
//var_dump($test);
$num = $test->get();
echo "Using  $num keys\n";

unset($test);
	
while (1)
{
	sleep(60);
}
?>