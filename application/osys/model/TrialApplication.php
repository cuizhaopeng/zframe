<?php
/**
 * Created by PhpStorm.
 * User: cuizhaopeng
 * Date: 18-12-19
 * Time: 下午3:17
 */

namespace app\osys\model;


use app\osys\osys_init\Omodel;

class TrialApplication extends Omodel
{
    public function cols($item){
        $item->col("trial_name")->name("申请人名称")->type("char(32)");
        $item->col("trial_phone_number")->name("申请人电话号码")->type("char(11)");
        $item->col("trial_company_name")->name("组织名称")->type("varchar(64)")->def("");
    }
}