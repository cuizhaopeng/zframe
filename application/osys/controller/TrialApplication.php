<?php
/**
 * Created by PhpStorm.
 * User: cuizhaopeng
 * Date: 18-12-19
 * Time: 下午3:15
 */

namespace app\osys\controller;


use app\osys\lib\exception\ErrorMessage;
use app\osys\lib\exception\SuccessMessage;
use app\osys\model\TrialApplication as TrialApplicationModel;
use app\osys\validate\TrialApplication as TrialApplicationValidate;


class TrialApplication extends Base
{

    public function initAuth()
    {
        $this->setMenuAuth("申请产品试用");
    }
    // 初始化数据表
    public function createDataTable()
    {
        new TrialApplicationModel();
    }
    // 查询列表
    public function index()
    {
        // 逻辑处理（查询列表）
        $field = ['id,trial_name,trial_phone_number,trial_company_name'];
        $reSelect = (new TrialApplicationModel())->field($field)->select();

        // 返回结果
        if ($reSelect) throw new SuccessMessage(['data'=> ['body_data' =>$reSelect]]);
        throw new ErrorMessage();
    }
    // 添加试用
    public function add()
    {
        // 验证用户信息是否符合规则
        $getParams = (new TrialApplicationValidate())->goCheck();

        // 逻辑处理（写入数据库)
        $data  = [
            'trial_name' => $getParams['trial_name'],
            'trial_phone_number' => $getParams['trial_phone_number'],
            'trial_company_name' => $getParams['trial_company_name'],
            'created_by'         => 0
        ];

        $resUser = (new TrialApplicationModel())->save($data);
        // 返回结果
        if ($resUser) throw new SuccessMessage();
        throw new ErrorMessage();
    }

    // 发送邮件
    public function sendEmail()
    {

    }
}