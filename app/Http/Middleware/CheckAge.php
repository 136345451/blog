<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAge
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (empty($request->age)) {
            print_r("中间件返回信息：你还没有输入年龄\n");
        }elseif ($request->age <= 20) {
            // return redirect('home'); 重定向
            print_r("中间件返回信息：你的年龄太小了\n");
        }

        return $next($request);
    }
}
