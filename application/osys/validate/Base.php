<?php
/**
 * DateTime: 2018/11/2 14:52
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\validate;

use app\osys\lib\exception\ParameterException;
use think\facade\Request;
use think\Validate;

class Base extends Validate
{
    /**
     * 参数校验，成功后返回接受的参数
     * @return string|array  返回接受的参数信息
     * @throws ParameterException 参数异常
     */
    public function goCheck($scene='')
    {
        // 获取http|https传入的参数
        $getParams = Request::param();

        // 检测参数
        $result = $this->scene($scene)->check($getParams);

        // 返回结果
        if ($result) return $getParams;  //返回接受到的参数
        throw new ParameterException();  //抛出异常
    }
}