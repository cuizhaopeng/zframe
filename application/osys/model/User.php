<?php
namespace app\osys\model;


use app\osys\osys_init\Omodel;

class User extends Omodel
{
    public function cols($item){
        $item->col("username")->name("登录用户名");
        $item->col("password")->name("登录密码")->type("char(32)");
        $item->col("phone_number")->name("电话号码")->type("char(11)");
        // $item->col("email")->name("邮箱")->type("varchar(32)")->def("");
        // $item->col("power")->name("权限")->def("");
        $item->col("user_status")->name("用户状态 0：正常，1：禁用")->type("smallint(1)")->def("0");
        $item->col("enterprise_name")->name("企业名称")->type("varchar(32)");
        $item->col("invite_code")->name("邀请码")->type("char(11)");
    }
}