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
    $str = preg_replace("/<script[\s\S]*<\/script>/i", "", $str);//去js
    $str = htmlspecialchars($str, ENT_QUOTES);
    return $str;
}

/**
 * 过滤富文本
 */
function gpxs($str)
{
    $str = trim($str);
    $str = preg_replace("/<script[\s\S]*<\/script>/i", "", $str);//去js
    $str = htmlspecialchars($str, ENT_QUOTES);
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
    if (is_array($data) || is_object($data)) {
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
    $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

    //记录日志
    return _logs(($dbt[1]['function'] ?? ""), ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'file' => $e->getFile() . ":" . $e->getLine()], $dir, $path);
}


/**
 * Notes:验证手机运营商号码段
 * User: Guo
 * Time: 2020/3/18 16:15
 * @param string $phone 手机号11位
 * @return int 1中国移动，2中国联通  3中国电信  0未知
 * 支持号码段：
 * 1、中国移动
 * 支持号段：134|135|136|137|138|139|147|148|150|151|152|157|158|159|165|172|178|182|183|184|187|188|195|198
 * 其中虚拟号段：165|170号段中的1703/1705/1706
 * 2、中国联通
 * 支持号段：130|131|132|145|146|155|156|166|167|171|175|176|185|186
 * 其中虚拟号段：167|171|170号段中的1704/1707/1708/1709
 * 3、中国电信
 * 支持号段：133|149|153|173|174|177|180|181|189|191|199
 * 其中虚拟号段：162|170号段中的1700/1701/1702
 * 其中物联网号段：149
 */
function getPhoneTypeVirtual($phone)
{
    $phone = trim($phone);
    $isChinaMobile = '/^170[356]\d{7}$|^(?:165)\d{8}$/'; //移动
    $isChinaUnion = '/^170[47-9]\d{7}$|^(?:167|171)\d{8}$/'; //联通
    $isChinaTelcom = '/^170[0-2]\d{7}$|^(?:162)\d{8}$/'; //电信
    if (preg_match($isChinaMobile, $phone)) {
        return 1;
    } elseif (preg_match($isChinaUnion, $phone)) {
        return 2;
    } elseif (preg_match($isChinaTelcom, $phone)) {
        return 3;
    } else {
        return 0;
    }
}

/**
 * 获取当天剩余秒数，用于redis设置过期时间
 */
function getDayEnd()
{
    return strtotime('23:59:59') - time() + 1;
}

/**
 * 获取当月剩余秒数，用于redis设置过期时间
 */
function getMonthEnd()
{
    return mktime(23, 59, 59, date('m'), date('t'), date('Y')) - time() + 1;
}

/**
 * CURL请求
 * @param string $url 请求url地址
 * @param array $data post数据数组，get请放在url里面拼接。
 * @param array $headers 请求header信息
 * @param int $timeout 抓取超时时间，默认7秒，7秒无响应返回false。请根据业务做好重新请求的预案。
 * @return mixed
 */
function httpRequest($url, $data = [], $headers = [], $timeout = 7)
{
    $ci = curl_init();
    curl_setopt($ci, CURLOPT_URL, $url);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    //指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    if (!empty($data)) {
        curl_setopt($ci, CURLOPT_POST, true);
        $datastr = is_array($data) ? http_build_query($data) : $data;
        curl_setopt($ci, CURLOPT_POSTFIELDS, $datastr);
    }
    if (preg_match('/^https:\/\//i', $url)) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE);
    }
    $response = curl_exec($ci);
    curl_close($ci);
    return $response;
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
    return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * @return array
 * 对象转数组
 */
function objectToArray($object)
{
    if (empty($object)) return [];
    if (is_array($object)) return $object;
    return get_object_vars($object);
}
