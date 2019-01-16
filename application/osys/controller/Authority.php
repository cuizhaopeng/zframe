<?php
/**
 * Created by PhpStorm.
 * User: cuizhaopeng
 * Date: 18-12-19
 * Time: 下午12:18
 */

namespace app\osys\controller;


use app\osys\lib\exception\ErrorMessage;
use app\osys\lib\exception\SuccessMessage;
use app\osys\osys_init\Omodel;
use app\osys\service\OT;
use app\osys\validate\Authority as AuthorityValidate;

class Authority    // extends Omodel
{
    public function checks()
    {
        // 验证字段
        $getParams = (new AuthorityValidate())->goCheck();
        // 用户识别以及权限判断
        // 验证token
        try{
            $getTokenBody = OT::decode($getParams['token'], config('app.secure.token_salt'), array('HS256'));
            // if (empty($getTokenBody)) throw new ErrorMessage(['msg'=>'Token 不存在']);
            // $this->globalId = $getTokenBody->uid;
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
        */
        // 返回结果
        throw new SuccessMessage(['data'=>['uid'=>$getTokenBody->id]]);
    }
}