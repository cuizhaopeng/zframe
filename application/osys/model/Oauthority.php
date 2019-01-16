<?php
namespace app\osys\model;


use app\osys\osys_init\Omodel;
use think\Db;

class Oauthority extends Omodel
{
    public function cols($item){
    	$item->col("code")->name("权限编码");
    	$item->col("name")->name("权限名称");
    	$item->col("type")->name("权限类型");
    	$item->col("func")->name("权限功能")->def("");
    	$item->col("upper")->name("上游权限");
    	$item->col("prerequisite")->name("先决条件")->def("");
    }

    //返回一级菜单
    public function l1_list(){
    	return $this->where("type","L1")->find();
    }


    //返回二级菜单
    public function l2_list($L1 = false){
    	$r = $this->where("type","L2");
    	if ($L1 != false) {
    		$r->where("upper",$L1);
    	}
    	return $r;
    }


    //返回三级API
    public function l3_list($L2 = false){
    	$r = $this->where("type","L3");
    	if ($L2 != false) {
    		$r->where("upper",$L2);
    	}
    	return $r;
    }

    //获得权限树
    public function getAuthTree($authArray = array(), $baseArray = array(),$all = true){
    	$aTree = array();

        //$authArray = ['OplanModuleOsys','OplanOsysAuthorityRegister'];
    	$this->getChildLeaves($aTree, "ROOT", $authArray, $baseArray, $all);
        //halt($aTree);
    	return $aTree;
    }

    //获得子节点
    public function getChildLeaves(&$bArray, $root, $authArray = array(), $baseArray = array(), $all = true){
    	$leavesResult = $this->where("upper", $root)->all();
    	//dump($leavesResult);
    	if ($leavesResult != NULL) {
    		foreach ($leavesResult as $key => $t) {
    		    if (sizeof($baseArray) == 0 || in_array($t["code"], $baseArray)) {
    		        $leavesShow = 1;
                } else {
    		        $leavesShow = 0;
                }
                $inArray = in_array($t["code"], $authArray);
	    		if ($leavesShow == 1 && (sizeof($authArray) == 0 || $all == true || $inArray)) {
                    //halt($t["code"]);
	    			$bArray[$key] = array("code" => $t["code"], "name" => $t["name"], "prerequisite" => $t["prerequisite"], "leaves" => array());
	    			if ($all == true) {
	    			    if ($inArray) {
                            $bArray[$key]["select"] = 1;
                        } else {
                            $bArray[$key]["select"] = 0;
                        }
                    }
	    			//halt($bArray[$key]["leaves"]);
	    			$this->getChildLeaves($bArray[$key]["leaves"], $t["code"],$authArray, $baseArray, $all);
	    		}
	    	}
    	}
    }

    //重新调整数据库权限表
    public function readjust_db_authority($authTree){
    	$appName = config("app.app_name");
    	$authList = array();
    	//dump($authTree);
    	$codeIdArray = $this->column("id","code");
    	$codeArray = array_keys($codeIdArray);
    	//dump($this->column("code","id"));
    	$exitsArray = array();
    	foreach ($authTree as $keyL1 => $valueL1) {
    		$codeL1 = $valueL1->code==""?$appName."Module".ucfirst($keyL1):$valueL1->code;	
    		$authL1 = array(
	    			"code" => $codeL1, 
	    			"name" => $valueL1->name==""?$appName."Module".ucfirst($keyL1):$valueL1->name, 
	    			"type" => "L1", 
	    			"upper" => $valueL1->upper==""?"ROOT":$valueL1->upper,
	    			"prerequisite" => $valueL1->prerequisite==""?"":$valueL1->upper,
	    			"created_by" => 0
	    		);
    		if (in_array($codeL1, $codeArray)) {
    			$exitsArray[] = $codeL1;
    			$this->get($codeIdArray[$codeL1])->save($authL1);
    		} else {
    			$authList[] = $authL1;
    		}
    		foreach ($valueL1->children as $keyL2 => $valueL2) {
	    		$codeL2 = $valueL2->code==""?$appName.ucfirst($keyL1).ucfirst($keyL2):$valueL2->code;
	    		$authL2 = array(
		    			"code" => $codeL2, 
		    			"name" => $valueL2->name==""?$codeL2:$valueL2->name, 
		    			"type" => "L2", 
		    			"upper" => $valueL2->upper==""?$codeL1:$valueL2->upper,
		    			"prerequisite" => $valueL2->prerequisite==""?"":$valueL2->upper,
	    				"created_by" => 0
	    			);
	    		if (in_array($codeL2, $codeArray)) {
	    			$exitsArray[] = $codeL2;
    				$this->get($codeIdArray[$codeL2])->save($authL2);
	    		} else {
	    			$authList[] = $authL2;
	    		}
    			foreach ($valueL2->children as $keyL3 => $valueL3) {
    				$codeL3 = $appName.ucfirst($keyL1).ucfirst($keyL2).$keyL3;
    				$authL3 = array(
			    			"code" => $codeL3,
			    			"name" => $valueL3->name==""?$codeL3:$valueL3->name, 
			    			"type" => "L3", 
			    			"upper" => $valueL3->upper==""?$codeL2:$valueL3->upper,
			    			"prerequisite" => $valueL3->prerequisite==""?"":$valueL3->upper,
	    					"created_by" => 0
		    			);
		    		if (in_array($codeL3, $codeArray)) {
		    			$exitsArray[] = $codeL3;
		    			$this->get($codeIdArray[$codeL3])->save($authL3);
		    		} else {
		    			$authList[] = $authL3;
		    		}
	    		}
    		}
    	}
    	$delArray = array_diff($codeArray, $exitsArray);
    	//dump($delArray);
    	if (sizeof($delArray) > 0) {
    		$this->whereIn("code", $delArray)->find()->delete();
    		//Db::table("osys_oauthority")->whereIn("code", $delArray)->delete();
    	}
    	$this->saveAll($authList);
    	//dump($this->getAuthTree());
    }

}