<?php
/**
 * DateTime: 2018/5/5 11:10
 * Author: John
 * Email: 2639816206@qq.com
 */
namespace app\osys\api\service;
use app\osys\lib\exception\TokenException;
use think\Cache;
use think\Exception;
use think\facade\Request;

class Token
{
    public static function generateToken()
    {
        //32个字符组成一组随机字符串
        $randChars = get_rand_char(32);
        //用三组字符串，进行md5加密
        $timestamp = $_SERVER['REQUEST_TIME'];
        //salt 盐
        $salt = config('secure.token_salt');
        return md5($randChars.$timestamp.$salt);
    }

    public static function getCurrentTokenVar($key)
    {
        $token = Request::header('token');
        $vars = Cache::get($token);
        if (!$vars)
        {
            throw new TokenException();
        }else
        {
            if (!is_array($vars))
            {
                $vars = json_decode($vars,true);
            }
            if (array_key_exists($key,$vars))
            {
                return $vars[$key];
            }else
            {
                throw new Exception('尝试获取的Token变量并不存在');
            }
        }
    }
    public static function getCurrentUid()
    {
        $uid = self::getCurrentTokenVar('uid');
        return $uid;
    }
}