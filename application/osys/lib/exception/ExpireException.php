<?php
/**
 * DateTime: 2018/8/7 10:23
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\lib\exception;


class ExpireException extends BaseException
{
    public $errCode = 400400;
    public $msg = "Expired token";

    /**
     * 构析函数  接受一个关联数组
     * ParameterException constructor.
     * @param array $params
     */
    public function __construct($params=[])
    {
        if(!is_array($params)){
            return false;
        }
        if(array_key_exists('msg',$params)){
            $this->msg = $params['msg'];
        }
        if(array_key_exists('errCode',$params)){
            $this->errCode = $params['errCode'];
        }
    }
}