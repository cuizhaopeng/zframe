<?php
/**
 * DateTime: 2018/11/5 14:13
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\lib\exception;


class PermissionException extends BaseException
{
    public $errCode = 40300;
    public $msg = "Permission denied";

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