<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

class UserController extends Controller
{
    /**
     * Notes: 获取用户信息
     * UserController: fangcan
     * DateTime: 2023/2/20 11:34
     */
    public function getUserInfo()
    {
        return 1;
    }

    /**
     * 显示指定用户的简介
     *
     * @param  int  $id
     * @param  int  $age
     * @return \Illuminate\View\View
     */
    public function show($id = '', $age = '')
    {
        $user = [];
        if($id == '123'){
            $user = [
                'name' => '方fan/g'.User::testModels($id),
                'email' => '136345451@qq.com',
                'age' => $age
            ];
        }

//        return json_encode($user,320);
        return $user;
    }

    public function auth(){
        print_r('我是auth');
    }
}
