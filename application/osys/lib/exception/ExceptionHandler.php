<?php
/**
 * DateTime: 2018/11/2 20:40
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\lib\exception;


use think\exception\Handle;
use Exception;
class ExceptionHandler extends Handle
{
    private $msg;
    private $errCode;
    private $data;

    public function render(Exception $e)
    {
        if ($e instanceof BaseException){
            //如果是自定义异常，则控制http状态码，不需要记录日志
            //因为这些通常是因为客户端传递参数错误或者是用户请求造成的异常
            //echo 123;
            $this->errCode = $e->errCode;
            $this->msg = $e->msg;
            $this->data = $e->data;
        }else{
            // 如果是服务器未处理的异常，将http状态码设置为500，并记录日志
            if(config('app_debug')){
                // 调试状态下需要显示TP默认的异常页面，因为TP的默认页面
                // 很容易看出问题
                return parent::render($e);
            }
            $this->msg = 'sorry，we make a mistake. (^o^)Y';
            $this->errCode = 999;
            //$this->recordErrorLog($e);
        }
        $result = [
            'msg'  => $this->msg,
            'err_code' => $this->errCode,
            'data' => $this->data
            //'request_url' => $request = Request::url()
        ];
        return json($result);
    }
}