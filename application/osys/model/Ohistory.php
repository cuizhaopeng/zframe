<?php
namespace app\osys\model;

//use think\Model;

use app\osys\osys_init\Omodel;

class Ohistory extends Omodel
{
    public function cols($item){
    	$item->col("history")->type("MEDIUMTEXT");
    	$item->col("model");
    	$item->col("model_id");
    	$item->col("auth_status")->def("");
    }
}