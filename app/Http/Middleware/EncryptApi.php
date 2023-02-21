<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 14:56
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class EncryptApi
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $params = $request->post('params', '');
            if (empty($params)) {
                return jsonReturn(['code' => 1200, 'msg' => '非法操作']);
            }
            // 解密
            $data = json_decode(webDecrypt($params), true);
            if (empty($data) || !is_array($data)) {
                return jsonReturn(['code' => 1200, 'msg' => '非法操作']);
            }
            //如果token参数不为空，则解析
            if (!empty($data['token'])) {
                //解析token
                $parse = (new Parser())->parse($data['token']);
                $signer = new Sha256();
                //验证token合法性
                if (!$parse->verify($signer, config('app.jwt_key'))) {
                    return jsonReturn(['code' => 1100, 'msg' => '未登录']);
                }
                //验证是否已经过期
                if ($parse->isExpired()) {
                    return jsonReturn(['code' => 1099, 'msg' => '登录超时']);
                }
                //获取数据
                $tokenInfo = $parse->getClaims();
                foreach ($tokenInfo as $key => $val) {
                    if (isset($data[$key])) {
                        return jsonReturn(['code' => 1200, 'msg' => '非法参数' . $key]);
                    }
                    $data[$key] = $parse->getClaim($key);
                }
            }
            //过滤参数，防止XSS
            foreach ($data as $key => $val) {
                if (is_string($val)) {
                    if (in_array($val, config('app.richTextParams'))) {
                        $data[$key] = gpxs($val);
                    } else {
                        $data[$key] = gpx($val);
                    }
                }
            }
            $request->merge($data);
            return $next($request);
        } catch (\Exception $e) {
            errorLogs($e);
            return jsonReturn(['code' => 1300, 'msg' => config('app.exception_msg')]);
        }
    }
}
