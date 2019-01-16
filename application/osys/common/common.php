<?php
/**
 * DateTime: 2018/11/10 11:35
 * Author: John
 * Email: 2639816206@qq.com
 */

/**
 * 随机生成一个字符串
 * @param $length int 生成的长度
 * @return null|string
 */
function get_rand_char($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0;$i < $length; $i++)
    {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}


// 发送邮件
function sendMail()
{

}
