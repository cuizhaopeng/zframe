<?php
/**
 * DateTime: 2018/11/10 14:05
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\validate;



class User extends BaseValidate
{
    protected $rule = [
        'username'  => 'require',
        'password'  => 'require',
        'pin'     => 'require',
        'phone_number' => 'require|number',
        'enterprise_name' => 'require',
        'invite_code' => 'require'
    ];

    protected $scene = [
        'phone'  =>  ['phone_number','pin'],
        'password'  =>  ['password','cpn']
    ];
}