<?php
/**
 * DateTime: 2018/5/7 15:18
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\service;

use app\osys\api\controller\OT;
use app\osys\api\model\AuthUser as AuthUserModel;
use app\osys\lib\exception\ParameterException;

class UserToken extends Token
{
    /**
     * 返回token值
     * @param $data 接受的参数
     * @return string
     * @throws ParameterException 参数规则错误
     */
    public function get($data)
    {
        //获取token
        return $this->grantToken($data);
    }

    /**
     * 验证用户名密码，通过发放token，失败返回错误信息
     * @param $data 参数
     * @return string
     * @throws ParameterException 参数异常抛出
     */
    private function grantToken($data){
        $username = $data['username'];
        $userRow = AuthUserModel::getUserData($username);
        //halt($user_row);
        //用户名验证
        if(empty($userRow)) {
            throw new ParameterException([
                'errCode' => 10001,
                'msg' =>lang('username does not exist')
            ]);
        }
        //密码验证
        /*$md_password = mduser($data['password']);
        if($userRow['password'] != $md_password) {
            throw new ParameterException([
                'errCode' => 10002,
                'msg' =>lang('password is wrong')
            ]);
        }*/
        //return $userRow['id'];
        //$token = $this->saveToCache($userRow['id']); //遗弃
        $data = [
            'uid' => $userRow['id'],
            'exp' => time()+7200
        ];
        $token = OT::encode($data, config('app.secure.token_salt'));
        return $token;
    }

    //遗弃
    private function saveToCache($cachedValue){
        $key = self::generateToken();
        //$value = json_encode($cachedValue);
        $expire_in = config('setting.token_expire_in');

        $request = cache($key, $cachedValue, $expire_in);
        if(!$request){
            throw new TokenException([
                'errorCode' => 10005,
                'message' => lang('service cache error')
            ]);
        }
        return $key;
    }
}