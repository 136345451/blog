<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 15:28
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthApi
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
            $posts = $request->post();
            if (empty($posts['token']) || empty($posts['user_id'])) {
                return jsonReturn(['code' => 1100, 'msg' => '未登录']);
            }
            $request->merge($posts);
            $request->offsetUnset('token');
            return $next($request);
        } catch (\Exception $e) {
            errorLogs($e);
            return jsonReturn(['code' => 1300, 'msg' => config('app.exception_msg')]);
        }
    }
}
