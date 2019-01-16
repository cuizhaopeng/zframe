<?php
namespace app\osys\osys_init;

use think\Db;

/**
 * 初始化数据库
 */
class InitApp
{
	
	public function run()
	{
		$conn = config()["database"];
		$database = $conn["database"];
		$conn["database"] = "";
		$dbCheck = Db::connect($conn)->query("SELECT * FROM information_schema.SCHEMATA where SCHEMA_NAME='".$database."'");
		if(!is_array($dbCheck) || sizeof($dbCheck) == 0){
			$dbCheck = Db::connect($conn)->query("CREATE DATABASE ".$database." CHARACTER SET utf8 COLLATE utf8_general_ci");
		}
	}
}
