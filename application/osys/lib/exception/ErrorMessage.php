<?php
/**
 * Created by 七月.
 * Author: 七月
 * Date: 2017/5/25
 * Time: 14:34
 */

namespace app\osys\lib\exception;


class ErrorMessage extends BaseException
{
    public $msg = '服务器内部错误！';
    public $errCode = 50000;
}