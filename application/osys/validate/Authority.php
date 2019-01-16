<?php
/**
 * Created by PhpStorm.
 * User: cuizhaopeng
 * Date: 18-12-19
 * Time: 下午12:27
 */

namespace app\osys\validate;


class Authority extends Base
{
    protected $rule = [
        'token' => 'require'
    ];
}