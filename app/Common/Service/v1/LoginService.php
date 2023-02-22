<?php
/**
 * User: fangcan
 * DateTime: 2023/2/22 10:52
 */

namespace App\Common\Service\v1;


use App\Common\Cache\BaseCache;
use App\Common\Validate\LoginValidate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class LoginService extends BaseService
{
    /**
     * Notes: 登录处理
     * User: fangcan
     * DateTime: 2023/2/22 11:47
     * @param $params
     * @param string mobile 手机号
     * @param string image_key 图形验证码 key
     * @param int image_code 图形验证码 code
     * @return array
     */
    public function doLogin($params)
    {
        try {
            $loginValidate = new LoginValidate();
            if (!$loginValidate->scene('checkDoLogin')->check($params)) {
                return ['code' => 1001, 'msg' => $loginValidate->getError()];
            }

            // 防止重复请求验锁
            $baseCache = new BaseCache();
            $lock_name = 'doLogin:' . $params['mobile'];
            if ($baseCache->getLock($lock_name) > 1) {
                return ['code' => 1600, 'msg' => '操作过于频繁，请稍后再试'];
            }
            $baseCache->putLock($lock_name);

            // 图形验证码验证
            $checkImageCodeResult = $this->checkImageCode($params);
            if ($checkImageCodeResult['code'] != 1000) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1002, 'msg' => $checkImageCodeResult['msg']];
            }

            $mobile = encrypt3Des($params['mobile']);
            // 登录信息入库
            $userInsertArr['mobile'] = $mobile;
            if (!empty($params['openid'])) {
                $userInsertArr['openid'] = $params['openid'];
            }
            $userInsertArr['add_time'] = time();
            $userInfo = objectToArray(DB::table('user')->where(['mobile' => $mobile])->first());
            if (empty($userInfo)) {
                if (!empty($params['openid']) && DB::table('user')->where(['openid' => $params['openid']])->count()) {
                    $baseCache->pullLock($lock_name);
                    return ['code' => 1003, 'msg' => '该微信号已绑定其他手机号'];
                }
                $user_id = DB::table('user')->insertGetId($userInsertArr);
                if (!$user_id) {
                    $baseCache->pullLock($lock_name);
                    return ['code' => 1004, 'msg' => '数据异常，请稍后再试'];
                }
            }else{
                if (!empty($params['openid']) && !empty($userInfo['openid']) && $params['openid'] != $userInfo['openid']) {
                    $baseCache->pullLock($lock_name);
                    return ['code' => 1005, 'msg' => '该手机号已绑定其他微信号'];
                }
                $user_id = $userInfo['user_id'];
                if (!DB::table('user')->where(['user_id' => $userInfo['user_id']])->update($userInsertArr)) {
                    $baseCache->pullLock($lock_name);
                    return ['code' => 1006, 'msg' => '数据异常，请稍后再试'];
                }
            }

            //登录成功 jwt加密处理
            $jwtArr['user_id'] = $user_id;
            $jwtArr['mobile'] = $params['mobile'];
            $data = [
                'token' => $this->setToken($jwtArr)
            ];
            $baseCache->pullLock($lock_name);
            return ['code' => 1000, 'msg' => '登录成功', 'data' => $data];
        } catch (\Exception $e) {
            errorLogs($e);
            if (isset($lock_name)) $baseCache->pullLock($lock_name);
            return ['code' => 1300, 'msg' => config('exception_msg')];
        }
    }

    /**
     * Notes: 获取图形验证码
     * User: fangcan
     * DateTime: 2023/2/22 10:50
     */
    public function getImageCode()
    {
        try {
            $captcha = app("captcha")->create("default", true);
            return ['code' => 1000, 'msg' => '成功', 'data' => $captcha];
        } catch (\Exception $e) {
            errorLogs($e);
            return [];
        }
    }

    /**
     * 检测图形验证码是否正确
     * @param
     *      mobile 手机号，用于作为redis的键
     *      image_key 图形验证码的key，由获取图形验证码接口返回
     *      image_code 图形验证码的内容，由获取图形验证码接口返回
     */
    public function checkImageCode($params)
    {
        try {
            $configInfo = (new ConfigService())->getConfigInfo();
            if (!$configInfo['image_code_on']) {
                return ['code' => 1000, 'msg' => '无需验证'];
            }
            $loginValidate = new LoginValidate();
            if (!$loginValidate->scene('checkImageCode')->check($params)) {
                return ['code' => 1001, 'msg' => $loginValidate->getError()];
            }
            if (Cache::get("imageCodeError:" . $params['mobile']) > 10) {
                return ['code' => 1002, 'msg' => '很抱歉，输入错误图形验证码超过上限，请明日再来'];
            }
            if (!captcha_api_check($params['image_code'], $params['image_key'])) {
                if (null === Cache::get("imageCodeError:" . $params['mobile'])) {
                    Cache::put("imageCodeError:" . $params['mobile'], 1, getDayEnd());
                } else {
                    Cache::increment("imageCodeError:" . $params['mobile']);
                }
                return ['code' => 1003, 'msg' => '图形验证码错误'];
            }
            return ['code' => 1000, 'msg' => '验证成功'];
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1003, 'msg' => '图形验证码异常'];
        }
    }

    /**
     * 设置token
     */
    public function setToken($params)
    {
        //登录成功 jwt加密处理
        $builder = new Builder();
        $signer = new Sha256();
        foreach ($params as $key => $val) {
            $builder->set($key, $val);
        }
        //防止token固定不变，需要在jwt里面设置一个变量。
        $builder->set('set_time', time());
        //设置签名
        $builder->sign($signer, config('app.jwt_key'));
        //获取加密后的token，转为字符串
        return (string)$builder->getToken();
    }
}
