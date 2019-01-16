<?php
/**
 * Created by 七月
 * Author: 七月
 * Date: 2017/2/18
 * Time: 15:44
 */

namespace app\osys\lib\exception;

/**
 * 创建成功（如果不需要返回任何消息）
 * 201 创建成功，202需要一个异步的处理才能完成请求
 */
class SuccessMessage extends BaseException
{
    public $errCode = 0;
    public $msg = 'ok';
    public $data = '';

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
        if(array_key_exists('data',$params)){
            $this->data = $params['data'];
        }
    }
}