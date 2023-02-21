<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
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
