<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::resource('photos', 'PhotoController');//资源路由要写在上面。
Route::get('/', function () {
    return view('welcome');
});

// PATH_INFO 为空时使用 REQUEST_URI
$path = empty($_SERVER['PATH_INFO']) ? (empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI']) : $_SERVER['PATH_INFO'];
if ($path) {
    // 解析出PATHINFO，explode把字符串打散为数组,array_filter去空值，array_values返回数组的所有值（非键名）
    list($module, $controller, $action) = array_values(array_filter(array_slice(explode('/', $path), -3)));
    // 不在模块分组中的统一绑定到默认模块
    if (!in_array($module, config('app.app_module_list'))) {
        $module = config('app.default_app_module');
    }
    // api版本控制
    $v = request()->api_version ? request()->api_version : config('app.api_version');
    $class = "App\\" . ucfirst(strtolower($module)) . "\\Controllers\\{$v}\\" . ucfirst(strtolower($controller)) . 'Controller';
    if (!class_exists($class)) return abort(404);
    Route::any("{module}/{controller}/{action}", [$class, $action]);
}

