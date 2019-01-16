<?php
/**
 * Created by PhpStorm.
 * User: cuizhaopeng
 * Date: 18-12-19
 * Time: 下午3:50
 */

namespace app\osys\validate;


class TrialApplication extends BaseValidate
{
    protected $rule = [
        'trial_name' => 'require',
        'trial_phone_number' => 'require',
        'trial_company_name' => 'require'
    ];
}