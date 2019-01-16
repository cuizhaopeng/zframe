<?php
/**
 * DateTime: 2018/11/19 15:06
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\service;


use think\facade\Request;

class Base
{
    // 验证Token  成功后返回id
    public function getToken()
    {
        // 获取Token
        $getToken = Request::header('Authorization');
        // 解析id
        $tokenBody = (new OT())->decode($getToken, config('app.secure.token_salt'), array('HS256'));
        // 返回
        // halt($tokenBody);
        return $tokenBody;
    }
}