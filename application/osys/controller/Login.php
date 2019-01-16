<?php
/**
 * DateTime: 2019/1/16 15:54
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\controller;


class Login
{
    // 获取Token
    public function getToken()
    {
        // 验证参数是否合法
        $getParams = (new TokenValidate())->goCheck('login'); //检查参数是否合法  合法返回接收到的参数

        // 验证--验证码是否正确
        // if (cache($getParams['username'])>2 && cache($getParams['username'].'_char') != $getParams['code']) throw new ParameterException(['msg'=>lang('code is error')]);

        // 验证用户名密码是否正确
        $resUser = (new UserModel())->where(['username'=>$getParams['username'],'password'=>AesSecurity::encrypt($getParams['password'], config('app.secure.login'))])->find();

        if (!$resUser) {
            // cache($getParams['username'],cache($getParams['username'])+1);
            throw new SuccessMessage(['errCode' => 1, 'msg'=> '用户名或密码错误']);
        }

        // 获取token
        $data = ['id' => $resUser['id'],'exp' => time()+7200];
        $token = OT::encode($data, config('app.secure.token_salt'));

        // 生成reToken  fixme  retoken需要重构
        // reToken 需要保存uid

        if (!empty($getParams['is_remember_pass']) && $getParams['is_remember_pass'] == 'true') $reTokenData = ['id' => $resUser['id'],'refresh_token'=> 1,'exp' => time()+864000];
        else $reTokenData = ['id' => $resUser['id'],'refresh_token'=> 10,'exp' => time()+7200];
        //halt($reTokenData);

        $reToken = OT::encode($reTokenData, config('app.secure.token_salt'));

        // 返回数据类型
        //$res = $this->baseAuth($resUser['id']);
        $res['token'] = $token;
        $res['refresh_token'] = $reToken;

        throw new SuccessMessage(['data'=> $res]);
    }

    // 刷新Token   fixme  需要修正
    public function refreshToken()
    {
        $refreshToken = Request::param('refresh_token');
        //halt($refreshToken);
        $bodyInfo = OT::decode($refreshToken, config('app.secure.token_salt'), ['HS256']);
        if (time() > $bodyInfo->exp) throw new ErrorMessage(['errCode' => 40010, 'msg' => 'token刷新已过期，请重新登录']);

        $data = ['id' => $bodyInfo->id, 'exp' => time()+7200];
        $token = OT::encode($data, config('app.secure.token_salt'));
        $data = ['token'=>$token];
        throw new SuccessMessage(['data'=> $data]);
    }
}