<?php
/**
 * DateTime: 2018/11/2 15:18
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\lib\exception;



use think\Exception;

/**
 * 自定义异常类的基类
 * Class BaseException
 * @package app\lib\exception
 */
class BaseException extends Exception
{
    public $errCode = 999;
    public $msg = '服务器内部错误！';
    public $data = '';

    /**
     * 构造函数，接受一个关联数组
     * BaseException constructor.
     * @param array $params  关联数组只应关联errCode 和 msg，且不应该为空值
     */
    public function __construct($params = [])
    {
        if (!is_array($params)) {
            return false;
        }
        if (array_key_exists('msg',$params)){
            $this->msg = $params['msg'];
        }
        if (array_key_exists('errCode',$params)){
            $this->errCode = $params['errCode'];
        }
        if (array_key_exists('data',$params)){
            $this->data = $params['data'];
        }
    }
}