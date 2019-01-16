<?php
namespace app\osys\controller;

use app\osys\lib\exception\ErrorMessage;
use app\osys\lib\exception\ParameterException;
use app\osys\lib\exception\SuccessMessage;
use app\osys\model\Oauthority;
use app\osys\osys_init\AuthObj;
use think\facade\Request;
use app\osys\validate\Base as BaseValidate;
use app\osys\model\User as UserModel;
use app\osys\service\Base as BaseService;
class Base
{
    //权限定义
    protected $authSetting;
    protected $globalId;
    
    public function __construct(){
        $this->authSetting = new AuthObj();
        // 验证登陆
        // 参数验证（code 验证） 返回解析数据
        // 验证token
        try{
            $getTokenBody =(new BaseService())->getToken();
            // if (empty($getTokenBody)) throw new ErrorMessage(['msg'=>'Token 不存在']);
            $this->globalId = $getTokenBody->id;
        }catch (\Exception $e){
            //halt($e);
            throw new ErrorMessage(['errCode'=>400400,'msg'=> $e->getMessage()]);
        }
        /*
        // 验证访问权限
        $getCode = Request::param('code');
        if (!$getCode) throw new ParameterException(['msg'=> 'code值不能为空！']);
        $resPower = (new UserModel())->where(['id' => $getTokenBody->id])->value('power');
        $power = unserialize($resPower);
        $keyNo = array_search($getCode,$power);
        if ($keyNo === false) throw new SuccessMessage(['errCode'=>1,'msg'=>'权限不足']);
    */}

    public function initAuth(){

    }

    public function setApiAuth($name,$func,$code = false,$prerequisite = ""){
        if ($code == false) {
            $classArray = explode("\\", get_class($this));
            $code = ucfirst($func);
        }
        $this->authSetting->children[] = new AuthObj(array("name" => $name, "func" => $func, "code" => $code, "prerequisite" => $prerequisite));
        //halt($this->authSetting->children);
    }

    public function setMenuAuth($name,$code = "",$prerequisite = ""){
        $this->authSetting->setAttr(array("name" => $name, "code" => $code, "prerequisite" => $prerequisite));
    }

    public function getAllAuth(){
        $this->initAuth();
        return $this->authSetting;
    }

}
