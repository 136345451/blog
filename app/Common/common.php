<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 15:44
 */

/**
 * 过滤字符串
 */
function gpx($str)
{
    $str = trim($str);
    $str = strip_tags($str);//去标签
    $str = preg_replace("/<script[\s\S]*<\/script>/i","", $str);//去js
    $str = htmlspecialchars($str,ENT_QUOTES);
    return $str;
}

/**
 * 过滤富文本
 */
function gpxs($str)
{
    $str = trim($str);
    $str = preg_replace("/<script[\s\S]*<\/script>/i","", $str);//去js
    $str = htmlspecialchars($str,ENT_QUOTES);
    return $str;
}

/**
 * 敏感数据（如手机号）加密
 */
function encrypt3Des($str, $key = null, $iv = null)
{
    $key = $key ?? config("app.default_des_key");
    $iv = $iv ?? config("app.default_des_iv");
    return base64_encode(openssl_encrypt($str, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, $iv));
}

/**
 * 敏感数据（如手机号）解密
 */
function decrypt3Des($str, $key = null, $iv = null)
{
    $key = $key ?? config("app.default_des_key");
    $iv = $iv ?? config("app.default_des_iv");
    return openssl_decrypt(base64_decode($str), 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * web加密
 */
function webEncrypt($str)
{
    if (is_array($str)) {
        $str = json_encode($str, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    return encrypt3Des($str, config("app.web_des_key"), config("app.web_des_iv"));
}

/**
 * web解密
 */
function webDecrypt($str)
{
    return decrypt3Des($str, config("app.web_des_key"), config("app.web_des_iv"));
}

/**
 * 日志写入
 * @param string $msg 日志标题
 * @param string $data 日志内容
 * @param string $dir 日志子目录，一般以业务命名。
 * @param string $path 日志根目录，默认logs文件。
 * @return bool
 */
function _logs($msg = '', $data = '', $dir = 'log', $path = 'logs')
{
    $path = env('root_path') . $path;
    !is_dir($path) && mkdir($path, 0777, true);
    $file_path = $path . DIRECTORY_SEPARATOR . $dir;
    !is_dir($file_path) && mkdir($file_path, 0777, true);
    $filename = $file_path . DIRECTORY_SEPARATOR . date("Y-m-d") . '.log';
    $limit_file_size = 2097152;
    if (is_file($filename) && $limit_file_size <= filesize($filename)) {
        @rename($filename, dirname($filename) . DIRECTORY_SEPARATOR . time() . '-' . basename($filename));
    }
    if (is_array($data)) {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $mtimestamp = sprintf("%.3f", microtime(true));
    $timestamp = floor($mtimestamp); // 时间戳
    $milliseconds = round(($mtimestamp - $timestamp) * 1000);
    $datetime = date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;
    $content = '【' . $msg . '】' . PHP_EOL;
    $content .= '[' . $datetime . '] ' . request()->ip() . PHP_EOL;
    $content .= $data . PHP_EOL . PHP_EOL;
    return file_put_contents($filename, $content, FILE_APPEND);
}

/**
 * errorLogs 记录错误日志
 * @param exception $e 抛出的异常错误信息
 * @param string $dir 日志子目录，一般以业务命名。
 * @param string $path 日志根目录，默认logs文件。
 * @return bool
 * GYP
 * 2021-07-09
 */
function errorLogs($e, $dir = 'error', $path = 'logs')
{
    //获取调用该函数的方法名
    $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);

    //记录日志
    return _logs(($dbt[1]['function'] ?? ""), ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'file' => $e->getFile().":".$e->getLine()], $dir,$path);
}

/*
 * json 不转义
 * */
function jsonUnicode($data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/*
 * 返回 并 写入日志
 * */
function jsonReturn($data, $iden = '')
{
    if (isset($data['code']) && $data['code'] != 1000 && $iden) {
        _logs('apiReturn', ['iden' => $iden, 'request' => request()->input(), 'return' => $data], 'apiReturn');
    }
    if (isset($data['data'])) {
        $data['data'] = webEncrypt($data['data']);
    }
    return response()->json($data, 200, [],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * @return array
 * 获取控制器和方法名
 */
function getControllerAndFunction()
{
    $action = \Route::current()->getActionName();
    list($class, $method) = explode('@', $action);
    $controller = substr(strrchr($class, '\\'), 1);
    return ['controller' => $controller, 'method' => $method];
}
