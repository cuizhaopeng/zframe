<?php
/**
 * DateTime: 2018/4/14 11:27
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\validate;


class Token extends BaseValidate
{
    protected $rule = [
        'username' => 'require',
        'password' => 'require',
        'refresh_token' => 'require'
    ];

    protected $scene = [
        'login'  =>  ['username','password'],
        'retoken'  =>  ['refresh_token']
    ];

}