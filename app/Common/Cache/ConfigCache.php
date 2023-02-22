<?php
/**
 * User: fangcan
 * DateTime: 2023/2/22 14:15
 */

namespace App\Common\Cache;


use Illuminate\Support\Facades\Cache;

class ConfigCache extends BaseCache
{
    //系统配置数组（参数名 =》 参数值）
    private $configInfoKey = 'configInfo';
    public function getConfigInfo()
    {
        return Cache::get($this->configInfoKey);
    }
    public function putConfigInfo($configInfo)
    {
        return Cache::put($this->configInfoKey, $configInfo);
    }
    public function pullConfigInfo()
    {
        return Cache::pull($this->configInfoKey);
    }
}
