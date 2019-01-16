<?php
namespace app\osys\controller;

use think\Db;
use think\facade\Env;
use \app\osys\controller\Base;
use \app\osys\osys_init\AuthObj;
use \app\osys\model\Oauthority;

class AuthorityRegister extends Base
{
    public function initAuth(){
        $this->setMenuAuth("权限模块");
        //$this->setApiAuth("权限管理","index");
        //$this->setApiAuth("权限分配","authDep");

    }

    public function index()
    {
        $oAuthority = new Oauthority();
        $appPath = Env::get('app_path');
        $dp = dir($appPath);
        $authTree = array();
        while ($dir = $dp->read()){
            if($dir !="." && $dir !=".." && is_dir($appPath.$dir)){
                $authTree[$dir] = new AuthObj();
                if(file_exists($appPath.$dir."/AuthSetting.php")){
                    if($myfile = fopen($appPath.$dir."/AuthSetting.php", "r")){
                        $readContent = fread($myfile,filesize($appPath.$dir."/AuthSetting.php"));
                        $readContent = str_replace("<?php", "", $readContent);
                        $readArray = json_decode($readContent);
                        fclose($myfile);
                        if ($readArray->hidden === true) {
                            unset($authTree[$dir]);
                            continue;
                        }
                        $authTree[$dir]->setAttr($readArray);
                    }
                }
                $dpp = dir($appPath.$dir."/controller/");
                while($ctr = $dpp->read()){
                    if (!is_dir($appPath.$dir."/controller/".$ctr)) {
                        $ctrFull = explode(".", $ctr);
                        if ($ctrFull[1] == "php" && !($ctrFull[0] == "Base" ||$ctrFull[0] == "Token" && $dir == "osys")) {
                            $ctrClassName = "\\app\\".$dir."\\controller\\".$ctrFull[0];
                            $ctrObject = new $ctrClassName();
                            $authTree[$dir]->children[$ctrFull[0]] = $ctrObject->getAllAuth();
                        }
                    }
                }
            }
        }

        $oAuthority->readjust_db_authority($authTree);
        return '权限管理';
    }
}
