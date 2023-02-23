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
use Illuminate\Support\Facades\Mail;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class LoginService extends BaseService
{
    /**
     * Notes: 发送验证码
     * User: fangcan
     * DateTime: 2023/2/22 17:20
     * @param $params
     * @param string email 邮箱
     * @param string mobile 手机号
     * @param string image_key 图形验证码 key
     * @param string image_code 图形验证码 code
     * @param int type 登录类型 1-邮箱 2-手机号
     * @return array
     */
    public function sendCode($params)
    {
        try {
            $loginValidate = new LoginValidate();
            if (!$loginValidate->scene('checkSendCode')->check($params)) {
                return ['code' => 1001, 'msg' => $loginValidate->getError()];
            }
            if (($params['login_type'] == 1 && empty($params['email'])) || ($params['login_type'] == 2 && empty($params['mobile']))) {
                return ['code' => 1002, 'msg' => '登录账号不能为空'];
            }

            // 防止重复请求验锁
            $baseCache = new BaseCache();
            $lock_name = 'sendCode:' . $params['mobile'];
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

            $account_key = $params['login_type'] == 1 ? 'email' : 'mobile';
            $account = encrypt3Des($params[$account_key]);
            // 验证今日是否错误5次
            $ve_date = date('Ymd');
            $error_cnt = DB::table('validate_error')->where(['account' => $account, 've_date' => $ve_date])->count();
            if ($error_cnt >= 5) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1004, 'msg' => '很抱歉，输入错误验证码超过上限，请明日再来'];
            }
            $num = random_int(100000, 999999);
            //1分钟内1个手机号只允许发送一次
            if (Cache::get("smsCodeMinuteSum:" . $params[$account_key])) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1005, 'msg' => '验证码发送太频繁，请稍后再试'];
            }
            //限制1天内1个手机号发送次数
            if (Cache::get("smsCodeDaySum:" . $params[$account_key]) >= 10) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1006, 'msg' => '今日获取验证码过多，请明日再来'];
            }
            $validateInsert = ['account' => $account, 'validate_code' => $num, 'validate_addtime' => time(), 'validate_type' => $params['login_type']];
            if (!DB::table('validate')->insert($validateInsert)) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1007, 'msg' => '数据异常，请稍后再试'];
            }

            // 发送验证码
            switch ($params['login_type']) {
                case 1://邮件验证码
                    $sentCodeParams = [
                        'to_email' => $params['email'],
                        'validate_code' => $num,
                    ];
                    $sendCodeResult = $this->sendEmailValidateCode($sentCodeParams);
                    break;
                case 2://短信验证码
                    break;
            }
            _logs('发送验证码', ['params' => $sentCodeParams ?? [], 'result' => $sendCodeResult ?? [], 'login/sendCode']);
            if (!isset($sendCodeResult) || !isset($sendCodeResult['code']) || $sendCodeResult['code'] != 1000) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1008, 'msg' => '数据异常，请稍后再试'];
            }

            Cache::put("smsCodeMinuteSum:" . $params[$account_key], 1, 60);
            if (null === Cache::get("smsCodeDaySum:" . $params[$account_key])) {
                Cache::put("smsCodeDaySum:" . $params[$account_key], 1, getDayEnd());
            } else {
                Cache::increment("smsCodeDaySum:" . $params[$account_key]);
            }
            $baseCache->pullLock($lock_name);
            return ['code' => 1000, 'msg' => '发送成功'];
        } catch (\Exception $e) {
            errorLogs($e);
            if (isset($lock_name)) $baseCache->pullLock($lock_name);
            return ['code' => 1300, 'msg' => config('exception_msg')];
        }
    }

    /**
     * Notes: 登录处理
     * User: fangcan
     * DateTime: 2023/2/22 11:47
     * @param $params
     * @param string email 邮箱
     * @param string mobile 手机号
     * @param string validate_code 验证码
     * @param int type 登录类型 1-邮箱 2-手机号
     * @return array
     */
    public function doLogin($params)
    {
        try {
            $loginValidate = new LoginValidate();
            if (!$loginValidate->scene('checkDoLogin')->check($params)) {
                return ['code' => 1001, 'msg' => $loginValidate->getError()];
            }
            $account_name = $params['login_type'] == 1 ? '邮箱' : '手机号';
            if (($params['login_type'] == 1 && empty($params['email'])) || ($params['login_type'] == 2 && empty($params['mobile']))) {
                return ['code' => 1002, 'msg' => '登录' . $account_name . '不能为空'];
            }

            // 防止重复请求验锁
            $baseCache = new BaseCache();
            $lock_name = 'doLogin:' . $params['mobile'];
            if ($baseCache->getLock($lock_name) > 1) {
                return ['code' => 1600, 'msg' => '操作过于频繁，请稍后再试'];
            }
            $baseCache->putLock($lock_name);

            // 图形验证码验证
//            $checkImageCodeResult = $this->checkImageCode($params);
//            if ($checkImageCodeResult['code'] != 1000) {
//                $baseCache->pullLock($lock_name);
//                return ['code' => 1003, 'msg' => $checkImageCodeResult['msg']];
//            }

            // 1天内登录错误5次禁止登录
            $account_key = $params['login_type'] == 1 ? 'email' : 'mobile';
            $account = encrypt3Des($params[$account_key]);
            $ve_date = date('Ymd');
            $error_cnt = DB::table('validate_error')->where(['account' => $account, 've_date' => $ve_date])->count();
            if ($error_cnt >= 5) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1004, 'msg' => '很抱歉，输入错误验证码超过上限，请明日再来'];
            }
            // 验证码验证
            $validateInfo = objectToArray(DB::table('validate')->select(['validate_code', 'validate_addtime'])->where(['account' => $account])->orderBy('validate_addtime', 'desc')->first());
            if (empty($validateInfo) || $validateInfo['validate_code'] != $params['validate_code']) {
                $validateErrorInsert['account'] = $account;
                $validateErrorInsert['ve_date'] = $ve_date;
                $validateErrorInsert['ve_addtime'] = time();
                if (!DB::table('validate_error')->insert($validateErrorInsert)) {
                    $baseCache->pullLock($lock_name);
                    return ['code' => 1102, 'msg' => '数据异常，请稍后再试'];
                }
                $baseCache->pullLock($lock_name);
                return ['code' => 1003, 'msg' => '验证码输入错误，今日剩余' . (4 - $error_cnt) . '次机会'];
            }
            if (time() > $validateInfo['validate_addtime'] + 600) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1004, 'msg' => $account_name . '验证码已过期'];
            }

            // 登录信息入库
            $userInsertArr[$account_key] = $account;
            if (!empty($params['openid'])) {
                $userInsertArr['openid'] = $params['openid'];
            }
            $userInsertArr['add_time'] = time();
            $userInfo = objectToArray(DB::table('user')->where([$account_key => $account])->first());
            if (empty($userInfo)) {
                if (!empty($params['openid']) && DB::table('user')->where(['openid' => $params['openid']])->count()) {
                    // 如果是邮箱登录，但检测到openid已存在，可能该openid已使用其他方式注册，需要验证该用户是否已绑定邮箱，如果没有则自动绑定。手机号登录同理。
                    $userInfo = objectToArray(DB::table('user')->where(['openid' => $params['openid']])->first());
                    if (!empty($userInfo[$account_key])) {
                        $baseCache->pullLock($lock_name);
                        return ['code' => 1003, 'msg' => '该微信号已绑定其他' . $account_name];
                    }
                    $userInsertArr[$account_key] = $account;
                    goto updateUserInfo;
                }
                $user_id = DB::table('user')->insertGetId($userInsertArr);
                if (!$user_id) {
                    $baseCache->pullLock($lock_name);
                    return ['code' => 1004, 'msg' => '数据异常，请稍后再试'];
                }
                goto loginSuccess;
            }
            if (!empty($params['openid']) && !empty($userInfo['openid']) && $params['openid'] != $userInfo['openid']) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1005, 'msg' => '该' . $account_name . '已绑定其他微信号'];
            }
            updateUserInfo:
            $user_id = $userInfo['user_id'];
            if (!DB::table('user')->where(['user_id' => $userInfo['user_id']])->update($userInsertArr)) {
                $baseCache->pullLock($lock_name);
                return ['code' => 1006, 'msg' => '数据异常，请稍后再试'];
            }

            loginSuccess:
            // 登录成功 jwt加密处理
            $jwtArr['user_id'] = $user_id;
            $jwtArr['account_key'] = $account_key;
            $jwtArr[$account_key] = $jwtArr['account'] = $params[$account_key];
            $jwtArr['login_type'] = $params['login_type'];
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

    /**
     * Notes: 发送邮件验证码
     * User: fangcan
     * DateTime: 2023/2/22 17:58
     * @param $params
     * @param string to_email 收件人
     * @param string validate_code 验证码
     */
    public function sendEmailValidateCode($params)
    {
        try {
            $loginValidate = new LoginValidate();
            if (!$loginValidate->scene('checkSendEmailValidateCode')->check($params)) {
                return ['code' => 1001, 'msg' => $loginValidate->getError()];
            }

            // 发送邮件
            $app_name = env('APP_NAME', '10e4t');
            $subject = "[$app_name]请验证您的设备";
            Mail::send(
                'email.login',    //视图地址
                ['verification_code' => $params['validate_code'], 'app_name' => $app_name],
                function ($message) use ($params, $subject) {
                    $message->to($params['to_email'])->subject($subject);
                }
            );

            return ['code' => 1000, 'msg' => '发送完毕'];
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1300, 'msg' => config('exception_msg')];
        }
    }
}
