<?php
/**
 * DateTime: 2018/11/10 12:19
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\controller;

use app\osys\lib\exception\ErrorMessage;
use app\osys\lib\exception\ParameterException;
use app\osys\lib\exception\SuccessMessage;
use app\osys\model\User as UserModel;
use app\osys\service\AesSecurity;
use app\osys\validate\User as UserValidate;
use app\osys\model\UnitUser as UnitUserModel;
use app\osys\model\Oauthority;
use think\facade\Request;
use think\facade\Cache;

class User extends Base
{
    public function initAuth()
    {
        $this->setMenuAuth("用户权限");
        $this->setApiAuth("添加用户","addUser");
        $this->setApiAuth("编辑用户","editUser");
        $this->setApiAuth("删除用户","delUser");
        $this->setApiAuth("查询列表","selectUser");
        $this->setApiAuth("权限列表","selectAuth");
    }

    /**
     * 添加用户（注册用户）
     * @throws ErrorMessage
     * @throws ParameterException
     * @throws SuccessMessage
     * @throws \think\Exception
     */
    public function add()
    {
        // 验证用户信息是否符合规则
        $getParams = (new UserValidate())->goCheck();
        // 验证手机号--验证码是否正确
        if ($getParams['pin'] != Cache::get($getParams['phone_number'])) throw new ErrorMessage(['msg' => lang('Verification code error')]);
        // 逻辑处理（写入数据库)
        $data  = [
            'username' => $getParams['username'],
            'password' => AesSecurity::encrypt($getParams['password'], config('app.secure.login')),
            'created_by' => 0,
            'phone_number' => $getParams['phone_number'],
            'enterprise_name' => $getParams['enterprise_name'],
            'invite_code' => $getParams['invite_code']
        ];
        $resUser = (new UserModel())->save($data);
        // 返回结果
        if ($resUser) throw new SuccessMessage();
        throw new ErrorMessage();
    }

    /**
     * 发送短信验证码
     * @throws ErrorMessage
     * @throws SuccessMessage
     */
    function sendMessage()
    {
        $phoneNumber = Request::param('phone_number');
        if (!$phoneNumber) throw new ErrorMessage(['msg' => lang('请输入手机号码！')]);
        if (Cache::get($phoneNumber.'-time')) throw new ErrorMessage(['msg' => lang('验证码发送频率过高，请稍后再试。')]);
        // 生成一个验证码 ，并缓存
        $code = rand(1000,9999);
        Cache::set($phoneNumber,$code,600);
        Cache::set($phoneNumber.'-time',1,60);
        // 发送前的配置信息
        $accessKeyId = "LTAIp75FRoFjEUf2";
        $accessKeySecret = "8tEQ1bakjiFudJTzW3D2skHGlJEW6j";
        $params["PhoneNumbers"] = $phoneNumber;
        $params["SignName"] = "橙智科技";
        $params["TemplateCode"] = "SMS_154951612";
        $params['TemplateParam'] = Array (
            "code" => $code
        );
        $res = $this->send($accessKeyId,$accessKeySecret,$params);
        if ($res->Code == 'OK') throw new SuccessMessage();
        throw new ErrorMessage(['msg'=> lang($res->Message)]);
    }

    // 验证手机号，是否属于本人
    public function checkPhoneNumber()
    {
        // 验证手机号码
        $getParams = (new UserValidate())->goCheck('phone');
        // 检查验证码是否正确
        if ($getParams['pin'] != Cache::get($getParams['phone_number'])) throw new ErrorMessage(['msg' => lang('Verification code error')]);
        // 对需要返回的信息进行加密处理
        $uid = (new UserModel())->where(['phone_number'=>$getParams['phone_number']])->value('id');
        $data = [
            'uid' => $uid,
            'exp_time' => time()+86400
        ];
        $res = AesSecurity::encrypt(serialize($data), config('app.secure.change_password'));
        // 返回什么结果?  加密结果
        if ($res) throw new SuccessMessage(['data' => ['cpn'=>$res]]);
        throw new ErrorMessage();
    }

    // 更改密码
    public function changePassword()
    {
        // 验证参数
        $getParams = (new UserValidate())->goCheck('password');
        // 解密加密密钥
        $deDatas = unserialize(AesSecurity::decrypt($getParams['cpn'], config('app.secure.change_password')));
        if ($deDatas['exp_time'] < time()) throw new ErrorMessage(['msg'=>lang('更改密码已过期！')]);
        // 更改密码
        // 需要判断密码是否跟原密码一致
        $userModel = (new UserModel())->get($deDatas['uid']);
        $password = AesSecurity::encrypt($getParams['password'], config('app.secure.login'));
        if ($userModel['password'] == $password) throw new ErrorMessage(['err_code'=>3000, 'msg' => '密码不能跟原密码一致！']);
        $userModel->password = $password;
        $res = $userModel->save();
        // 返回结果
        if ($res) throw new SuccessMessage();
        throw new ErrorMessage();
    }

    // 编辑用户信息
    public function editUser()
    {
        // 验证用户信息是否符合规则
        $getParams = (new UserValidate())->goCheck();
        // 逻辑处理（写入数据库） fixme:测试数据
        if (empty($getParams['uid'])) return json(['errCode'=>1,'msg'=>lang('参数错误')]);
        $userModel = (new UserModel())->get($getParams['uid']);
        $data  = [
            //'id'        => $getParams['uid'],
            'username' => $getParams['username'],
            'password' => $getParams['password'],
            'email' => $getParams['email'],
            'phone_number' => $getParams['phone_number'],
            //'user_status' => $getParams['user_status']
        ];
        //halt($data);
        $resUser = $userModel->save($data);
        //halt($resUser);
        // 返回结果
        if ($resUser){
            return json([
                'errCode' => 0,
                'msg'   => 'ok',
                'data'  => ''
            ]);
            //throw new SuccessMessage();
        }
        throw new ErrorMessage();
    }

    /**
     * 查询用户信息
     * @throws ErrorMessage
     * @throws SuccessMessage
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * retrun array
     */
    public function findUser()
    {
        // 逻辑处理（查询所需要的信息）
        $resFind = model('user')->field('id,username,unit_id,email,phone_number,user_status')->where([
            'id' => 16,
            //'id' => $this->globalId,  暂时修改，后期恢复
        ])->with('orgInfo')->find();

        // 返回结果
        if ($resFind) throw new SuccessMessage(['data'=>['body_data'=>$resFind]]);
        throw new ErrorMessage();
    }

    // 删除用户
    public function delUser()
    {
        // 验证用户信息是否符合规则
        $id = Request::param('id');
        // 逻辑处理（写入数据库）
        $reSelect = (new UserModel())->where('id',$id)->find()->delete();
        // 返回结果
        if ($reSelect) throw new SuccessMessage();
        throw new ErrorMessage();
    }

    // 查找所有用户
    public function selectUser()
    {
        // 验证用户信息是否符合规则
        $uid = Request::param('uid');
        // 逻辑处理（查询列表）
        $field = ['id,username,user_status,phone_number,email'];
        $reSelect = (new UserModel())->field($field)->where(['unit_id' => $uid])->select();
        //halt($reSelect);
        // 返回结果
        //if ($reSelect){
        return json([
            'errCode' => 0,
            'msg' => lang('ok'),
            'data'   => $reSelect
        ]);
        //throw new SuccessMessage();
        //}
    }

    // 获取总权限
    public function selectAuth()
    {
        $uid = Request::param('uid');
        // 逻辑处理（查询列表）
        $field = ['id,org_name,org_status,org_phone_number,org_email,org_deadline'];
        $refind = (new UserModel())->field('unit_id,power')->where(['id' => $uid])->find();
        $reSelect = (new UnitUserModel())->field('org_power')->where(['id' => $refind['unit_id']])->find();
        $orgPower = unserialize($reSelect['org_power']);
        $Power = unserialize($refind['power']);
        //halt($Power);
        $res = (new Oauthority())->getAuthTree($Power,$orgPower,true);
        foreach ($res as $item) {
            $aaTree[] = $item;
        }
        //halt($res);
        //$res = file_get_contents('D:\wamp64\www\orait_zero\test.json');
        return json([
            'errCode' =>0,
            'msg'   => 'ok',
            'data' => $aaTree
        ]);
    }

    // 获取用户权限  //根据token
    public function findAuth()
    {
        $uid = $this->globalId;
        // 逻辑处理（查询列表）
        $field = ['id,org_name,org_status,org_phone_number,org_email,org_deadline'];
        $refind = (new UserModel())->field('username,unit_id,power')->where(['id' => $uid])->find();
        /*$reSelect = (new UnitUserModel())->field('org_power')->where(['id' => $refind['unit_id']])->find();
        $orgPower = unserialize($reSelect['org_power']);
        $Power = unserialize($refind['power']);
        //halt($uid);
        $res = (new Oauthority())->getAuthTree($Power,$orgPower,true);
        foreach ($res as $item) {
            $aaTree[] = $item;
        }*/

        $data = $this->getMenu($uid);
        foreach ($data as $key =>$datum) {
            //$datas['L1'][$key]['code'] = $datum['code'];
            //$datas['L1'][$key]['name'] = $datum['name'];
            //$datas['L1'][$key]['prerequisite'] = $datum['prerequisite'];
            //$datas['L1'][$key]['select'] = $datum['select'];
            foreach ($datum['leaves'] as $datumk => $leaves) {
                //$datas['L2'][$datum['code']][$datumk]['code'] = $leaves['code'];
                //$datas['L2'][$datum['code']][$datumk]['name'] = $leaves['name'];
                //$datas['L2'][$datum['code']][$datumk]['prerequisite'] = $leaves['prerequisite'];
                //$datas['L2'][$datum['code']][$datumk]['select'] = $leaves['select'];
                foreach ($leaves['leaves'] as $leavesk => $leaf) {
                    $datas['L3'][$leaf['code']][$leavesk]['code'] = $leaf['code'];
                    $datas['L3'][$leaf['code']][$leavesk]['name'] = $leaf['name'];
                    $datas['L3'][$leaf['code']][$leavesk]['prerequisite'] = $leaf['prerequisite'];
                    $datas['L3'][$leaf['code']][$leavesk]['select'] = $leaf['select'];
                }
            }
        }

        //halt($aaTree);
        //$res = file_get_contents('D:\wamp64\www\orait_zero\test.json');
        return json([
            'errCode' =>0,
            'msg'   => 'ok',
            'data' => [
                'username' => $refind['username'],
                'auth_tree'=> $datas
            ]
        ]);
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

    // 更新权限
    public function updateAuth()
    {
        // 获取需要更新的code码
        $getCode = Request::param('code');
        $getUid = Request::param('uid');
        $getOperate = Request::param('operate');

        if (empty($getCode)||empty($getUid)){
            return json([
                'errCode' => 1,
                'msg'   => 'Code码错误',
                'data' => ''
            ]);
        }
        // 自动进行检测添加或者删除
        $UserModel = new UserModel();
        $resUser = $UserModel->field('unit_id,power')->where(['id'=>$getUid])->find();
        $resUnitUser = (new UnitUserModel())->field('org_power')->where(['id'=>$resUser['unit_id']])->find();
        $orgPower = unserialize($resUser['power']);
        $orgwPower = unserialize($resUnitUser['org_power']);
        //halt($orgwPower);
        /*if (empty($orgPower))
        {
            $updateCode = serialize([$getCode]);
            $UserModel->get($getUid)->save(['power'=>$updateCode]);
            //$this->upChildLeavesPlus($getCode, $orgPower, $getUid);
            return json([
                'errCode' => 0,
                'msg'   => 'ok',
                'data'  =>''
            ]);
        }*/
        if ($getOperate){
            $aTree = $this->arrCodePlus($getCode,$orgwPower);
        }else{
            $aTree = $this->arrCode($getCode,$orgwPower);
        }
        //$aTree = $this->arrCode($getCode,$orgwPower);
        foreach ($aTree as $item) {
            if ($getOperate){
                $res = array_search($item,$orgPower); //查找元素是否在数组中返回键值
                if ($res === false){
                    array_push($orgPower,$item);
                    $updateCode = serialize($orgPower);
                    //更新元素
                    $UserModel->get($getUid)->save(['power'=>$updateCode]);
                    //$this->upChildLeavesPlus($leavesResult["upper"], $authArray, $getUnitId);
                }
                //$this->upChildLeavesPlus($getCode, $orgPower, $getUnitId);
            }else{
                $res = array_search($item,$orgPower); //查找元素是否在数组中返回键值

                if ($res !== false){
                    array_splice($orgPower, $res, 1); //根据键值删除元素
                    $updateCode = serialize($orgPower);
                    $UserModel->get($getUid)->save(['power'=>$updateCode]); //更新元素
                }
                // $this->upChildLeavesMinus($getCode, $orgPower, $getUnitId);
            }
        }
        $resUnitUser = $UserModel->field('power')->where(['id'=>$getUid])->find();
        $orgPower = unserialize($resUnitUser['power']);
        $aTree = (new Oauthority())->getAuthTree($orgPower,$orgwPower);
        foreach ($aTree as $item) {
            $aaTree[] = $item;
        }

        return json([
            'errCode' => 0,
            'msg'   => 'ok',
            'data'  => $aaTree
        ]);
    }

    // 更新权限
    public function updateAuthnew ()
    {
        // 获取需要更新的code码

        $getCode = Request::param('code');
        $getUnitId = Request::param('unit_id');
        $getOperate = Request::param('operate');
        if (empty($getCode)||empty($getUnitId)){
            return json([
                'errCode' => 1,
                'msg'   => 'Code码错误',
                'data' => ''
            ]);
        }
        // 自动进行检测添加或者删除
        $UnitUserModel = new UnitUserModel();
        $resUnitUser = $UnitUserModel->field('org_power')->where(['id'=>$getUnitId])->find();
        $orgPower = unserialize($resUnitUser['org_power']);
        if (empty($orgPower))
        {
            $updateCode = serialize([$getCode]);
            $UnitUserModel->get($getUnitId)->save(['org_power'=>$updateCode]);
            return json([
                'errCode' => 0,
                'msg'   => 'ok',
                'data'  =>''
            ]);
        }
        if ($getOperate){
            $aTree = $this->arrCodePlus($getCode);
        }else{
            $aTree = $this->arrCode($getCode);
        }

        foreach ($aTree as $item) {
            if ($getOperate){
                $res = array_search($item,$orgPower); //查找元素是否在数组中返回键值

                if ($res === false){
                    array_push($orgPower,$item);
                    $updateCode = serialize($orgPower);
                    //更新元素
                    (new UnitUserModel())->get($getUnitId)->save(['org_power'=>$updateCode]);
                    //$this->upChildLeavesPlus($leavesResult["upper"], $authArray, $getUnitId);
                }
                //$this->upChildLeavesPlus($getCode, $orgPower, $getUnitId);
            }else{
                $res = array_search($item,$orgPower); //查找元素是否在数组中返回键值

                if ($res !== false){
                    array_splice($orgPower, $res, 1); //根据键值删除元素
                    $updateCode = serialize($orgPower);
                    (new UnitUserModel())->get($getUnitId)->save(['org_power'=>$updateCode]); //更新元素
                }
                // $this->upChildLeavesMinus($getCode, $orgPower, $getUnitId);
            }
        }

        $resUnitUser = $UnitUserModel->field('org_power')->where(['id'=>$getUnitId])->find();
        $orgPower = unserialize($resUnitUser['org_power']);
        $aTree = (new Oauthority())->getAuthTree($orgPower);

        return json([
            'errCode' => 0,
            'msg'   => 'ok',
            'data'  => $aTree
        ]);
    }

    // 获取code码，生成数组
    public function arrCode($code,$orgwPower)
    {
        $resType = (new Oauthority())->where(['code'=>$code])->value('type');
        //$UnitUserModel = new UnitUserModel();
        //$resUnitUser = $UnitUserModel->field('org_power')->where(['id'=>$getUnitId])->find();
        //$orgPower = unserialize($resUnitUser['org_power']);
        switch ($resType){
            case 'L1':
                $aArray[] = $code;
                $this->getChildLeaves($aArray,$code,$orgwPower);
                //halt($aArray);
                break;
            case 'L2':
                $aArray[] = $code;
                $this->getChildLeaves($aArray,$code,$orgwPower);
                break;
            case 'L3':
                $aArray[] = $code;
                $this->getChildLeaves($aArray,$code,$orgwPower);
                break;
            default:
                break;
        }
        return $aArray;
    }

    // 获取code码，生成数组
    public function arrCodePlus($code,$orgwPower)
    {
        $resType = (new Oauthority())->where(['code'=>$code])->value('type');
        //$UnitUserModel = new UnitUserModel();
        //$resUnitUser = $UnitUserModel->field('org_power')->where(['id'=>$getUnitId])->find();
        //$orgPower = unserialize($resUnitUser['org_power']);
        switch ($resType){
            case 'L1':
                $aArray[] = $code;
                $this->getChildLeaves($aArray,$code,$orgwPower);
                //halt($aArray);
                break;
            case 'L2':

                $aArray[] = $code;
                $this->upChildLeavesPlus($aArray,$code,$orgwPower);
                $this->getChildLeaves($aArray,$code,$orgwPower);
                break;
            case 'L3':

                $aArray[] = $code;
                $this->upChildLeavesPlus($aArray,$code,$orgwPower);
                //halt($aArray);
                break;
            default:
                break;
        }
        return $aArray;
    }

    public function getChildLeaves(&$bArray, $code,  $baseArray = array(), $all = true){
        $leavesResult = (new Oauthority())->where("upper", $code)->all();
        if ($leavesResult != NULL) {
            foreach ($leavesResult as $key => $t) {
                if (in_array($t["code"], $baseArray)) {
                    $leavesShow = 1;
                } else {
                    $leavesShow = 0;
                }
                if ($leavesShow == 1 && $all == true ) {
                    $bArray[] = $t["code"];
                    $this->getChildLeaves($bArray,$t["code"],$baseArray,$all);
                }
            }
        }
    }

    public function upChildLeavesPlus(&$bArray, $getCode,$baseArray = array(), $all = true){
        $leavesResult = (new Oauthority())->field('upper')->where("code", $getCode)->find();
        //halt($leavesResult);
        if ($leavesResult != NULL) {
            //halt($leavesResult);
            if (in_array($leavesResult["upper"], $baseArray)) {
                $leavesShow = 1;
            } else {
                $leavesShow = 0;
            }
            if ($leavesShow == 1 && $all == true ) {
                $bArray[] = $leavesResult["upper"];
                $this->upChildLeavesPlus($bArray,$leavesResult["upper"],$baseArray,$all);
            }
        }
    }
    // 更新用户状态信息   开启/禁用
    public function updateStatus()
    {
        // 更新状态不能为空
        $getStatus = Request::param('status');
        $getId = Request::param('id');
        if (empty($getStatus)) throw new ParameterException();

        // 更新状态
        $userStatus = (new UnitUserModel())->where('id', $getId)->value('user_status');
        if ($userStatus == $getStatus) throw new SuccessMessage();
        $reStatus = (new UserModel())->where('id', $getId)->update(['user_status' => $getStatus]);
        // 返回结果
        if ($reStatus) throw new SuccessMessage();
        throw new ErrorMessage();
    }

    //获得子节点//获得子节点  以下两个方法已舍弃，暂时保留做参考
    public function upChildLeavesPlusbak($getCode, $authArray = array(), $getUid){
        $leavesResult = (new Oauthority())->where("code", $getCode)->find();
        if ($leavesResult != NULL) {
            $res = array_search($leavesResult["code"],$authArray); //查找元素是否在数组中返回键值

            if ($res === false){
                array_push($authArray,$leavesResult["code"]);
                $updateCode = serialize($authArray);
                //更新元素
                (new UserModel())->get($getUid)->save(['power'=>$updateCode]);
            }
            $this->upChildLeavesPlus($leavesResult["upper"], $authArray, $getUid);
        }
    }

    public function upChildLeavesMinus($getCode, $authArray = array(), $getUid){
        //echo 123;
        //$leavesResult = (new Oauthority())->where("code", $getCode)->find();
        $leavesResult = (new Oauthority())->where("upper", $getCode)->all();
        if (empty($leavesResult)) {

            foreach ($leavesResult as $key => $t) {
                $res = array_search($t['code'],$authArray); //查找元素是否在数组中返回键值

                if ($res !== false){
                    array_splice($authArray, $res, 1); //根据键值删除元素
                    $updateCode = serialize($authArray);
                    (new UserModel())->get($getUid)->save(['power'=>$updateCode]); //更新元素
                }
                $this->upChildLeavesMinus($t['code'], $authArray = array(), $getUid);
            }
        }else{
            $res = array_search($getCode,$authArray); //查找元素是否在数组中返回键值
            if ($res !== false){
                array_splice($authArray, $res, 1); //根据键值删除元素

                $updateCode = serialize($authArray);
                (new UserModel())->get($getUid)->save(['power'=>$updateCode]); //更新元素
            }
        }
    }

    public function send($accessKeyId, $accessKeySecret, $params) {
        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        //$helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $this->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );
        return $content;
    }
    /**
     * 生成签名并发起请求
     *
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @param $security boolean 使用https
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    public function request($accessKeyId, $accessKeySecret, $domain, $params, $security=false) {
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }

        $stringToSign = "GET&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));

        $signature = $this->encode($sign);

        $url = ($security ? 'https' : 'http')."://{$domain}/?Signature={$signature}{$sortedQueryStringTmp}";

        try {
            $content = $this->fetchContent($url);
            return json_decode($content);
        } catch( \Exception $e) {
            return false;
        }
    }

    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    private function fetchContent($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));

        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $rtn = curl_exec($ch);

        if($rtn === false) {
            trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
        }
        curl_close($ch);

        return $rtn;
    }
}