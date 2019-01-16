<?php
/**
 * DateTime: 2018/5/7 9:51
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\controller;


use app\osys\lib\exception\ErrorMessage;
use app\osys\lib\exception\ParameterException;
use app\osys\model\Oauthority;
use app\osys\model\OsysUser;
use app\osys\service\AesSecurity;
use app\osys\service\UserToken;
use app\osys\validate\Token as TokenValidate;
use app\osys\lib\exception\SuccessMessage;
use think\facade\Cache;
use think\facade\Request;
use app\osys\service\OT;
use app\osys\model\User as UserModel;

class Token
{
    public function initAuth()
    {
        //$this->setMenuAuth("Token获取");
    }
    //获取token

    /**
     * 生成Token 和 refresh_token
     * Token 有效期2小时 ， refresh_token 有效期根据is_remember_pass字段设置默认10天
     * 验证流程
     * 先验证Token,Token失效发送refresh_token进行验证，验证失败重新登录
     * @throws ParameterException
     * @throws SuccessMessage
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getToken()
    {
        // 验证参数是否合法
        $getParams = (new TokenValidate())->goCheck('login'); //检查参数是否合法  合法返回接收到的参数

        // 验证--验证码是否正确
        // if (cache($getParams['username'])>2 && cache($getParams['username'].'_char') != $getParams['code']) throw new ParameterException(['msg'=>lang('code is error')]);

        // 验证用户名密码是否正确
        $resUser = (new UserModel())->where(['username'=>$getParams['username'],'password'=>AesSecurity::encrypt($getParams['password'], config('app.secure.login'))])->find();

        if (!$resUser) {
            // cache($getParams['username'],cache($getParams['username'])+1);
            throw new SuccessMessage(['errCode' => 1, 'msg'=> '用户名或密码错误']);
        }

        // 获取token
        $data = ['id' => $resUser['id'],'exp' => time()+7200];
        $token = OT::encode($data, config('app.secure.token_salt'));

        // 生成reToken  fixme  retoken需要重构
        // reToken 需要保存uid

        if (!empty($getParams['is_remember_pass']) && $getParams['is_remember_pass'] == 'true') $reTokenData = ['id' => $resUser['id'],'re_token'=> 1,'exp' => time()+864000];
        else $reTokenData = ['id' => $resUser['id'],'re_token'=> 10,'exp' => time()+7200];
        //halt($reTokenData);

        $reToken = OT::encode($reTokenData, config('app.secure.token_salt'));

        // 返回数据类型
        $res = $this->baseAuth($resUser['id']);
        $res['token'] = $token;
        $res['refresh_token'] = $reToken;

        throw new SuccessMessage(['data'=> $res]);
    }

    // 刷新Token   fixme  需要修正
    public function refreshToken()
    {
        $refreshToken = Request::param('refresh_token');
        //halt($refreshToken);
        $bodyInfo = OT::decode($refreshToken, config('app.secure.token_salt'), ['HS256']);
        if (time() > $bodyInfo->exp) throw new ErrorMessage(['errCode' => 40010, 'msg' => 'token刷新已过期，请重新登录']);

        $data = [
            'uid' => $bodyInfo->id,
            'exp' => time()+7200
        ];
        $token = OT::encode($data, config('app.secure.token_salt'));
        $data = ['token'=>$token];
        throw new SuccessMessage(['data'=> $data]);
    }

    /**
     * 生成验证码，有效期60秒
     * @throws ErrorMessage
     * @throws ParameterException
     * @throws SuccessMessage
     */
    public function verCode()
    {
        // 验证参数
        $username = Request::param('username');
        if (empty($username)) throw new ParameterException(['errCode' => 1, 'msg'=> '用户名不能为空']);

        // 生成验证码
        $verCode = get_rand_char(4);
        $res = cache($username.'_char', $verCode, 60);

        // 返回
        if ($res) throw new SuccessMessage(['data'=>['ver_code'=> $verCode]]);
        throw new ErrorMessage(['msg'=>lang('cache error')]);
    }

    // test 测试
    public function test()
    {
        /*echo model('osys_user')->save([
            'username' => 'cuizhaopeng',
            'password' => 'asdhfkajshjkdf',
            'phone_number' => '1234',
            'email' => '1234'
        ]);*/
        echo 123;
        dump(model('osys_user')->where('id',6)->find());
    }

    // 获取权限列表
    public function baseAuth($getId)
    {
        // 获取到该用户的权限
        // 根据权限获取相应的菜单项
        // fixme 权限省略，暂时获取所有权限
        // 获取菜单
        $data = $this->getMenu($getId);
        foreach ($data as $key =>$datum) {
            $datas['L1'][$key]['code'] = $datum['code'];
            $datas['L1'][$key]['name'] = $datum['name'];
            $datas['L1'][$key]['prerequisite'] = $datum['prerequisite'];
            $datas['L1'][$key]['select'] = $datum['select'];
            foreach ($datum['leaves'] as $datumk => $leaves) {
                $datas['L2'][$datum['code']][$datumk]['code'] = $leaves['code'];
                $datas['L2'][$datum['code']][$datumk]['name'] = $leaves['name'];
                $datas['L2'][$datum['code']][$datumk]['prerequisite'] = $leaves['prerequisite'];
                $datas['L2'][$datum['code']][$datumk]['select'] = $leaves['select'];
                foreach ($leaves['leaves'] as $leavesk => $leaf) {
                    $datas['L3'][$leaf['code']][$leavesk]['code'] = $leaf['code'];
                    $datas['L3'][$leaf['code']][$leavesk]['name'] = $leaf['name'];
                    $datas['L3'][$leaf['code']][$leavesk]['prerequisite'] = $leaf['prerequisite'];
                    $datas['L3'][$leaf['code']][$leavesk]['select'] = $leaf['select'];
                }
            }
        }
        // 返回菜单
        return $datas;
        //throw new SuccessMessage(['data'=> $datas]);
    }

    // 获取所有菜单项  fixme   需要根据实际用户权限获取菜单项
    public function getMenu($id)
    {
        //$refind = (new UserModel())->field('power')->where(['id' => $id])->find();
        $reSelect = (new UserModel())->field('power')->where(['id' => $id])->find();
        $authArray = unserialize($reSelect['power']);
        //halt($authArray);
        $resTree = (new Oauthority())->getAuthTree($authArray);
        return $resTree;
    }


}