<?php
/**
 * DateTime: 2018/11/2 15:16
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\lib\exception;



class ParameterException extends BaseException
{
    public $errCode = 10000;
    public $msg = "invalid parameters";

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