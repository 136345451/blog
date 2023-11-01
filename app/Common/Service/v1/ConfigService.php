<?php
/**
 * User: fangcan
 * DateTime: 2023/2/22 14:14
 */

namespace App\Common\Service\V1;


use App\Common\Cache\ConfigCache;
use Illuminate\Support\Facades\DB;

class ConfigService
{
    /**
     * 获取系统配置信息，为键值对的一维数组
     */
    public function getConfigInfo()
    {
        try {
            //读取缓存数据
            $configCache = new ConfigCache();
            $configInfo = $configCache->getConfigInfo();
            if (is_null($configInfo)) {
                //读取数据表
                $configList = DB::table('config')->where(['config_status' => 1])->get()->toArray();
                if (!empty($configList)) {
                    $configInfo = array_column($configList, 'config_value', 'config_key');
                    $configCache->putConfigInfo($configInfo);
                }
            }
            foreach ($configInfo as $key => $val) {
                $configInfo[$key] = ('' !== $val) ? decrypt3Des($val) : $val;
            }
            return $configInfo;
        } catch (\Exception $e) {
            errorLogs($e);
            return [];
        }
    }

    /**
     * 获取系统配置信息，通过 $key 获取 $val
     */
    public function getConfigVal($key = '')
    {
        $configInfo = $this->getConfigInfo();
        $val = !empty($key) ? (isset($configInfo[$key]) ? $configInfo[$key] : '') : $configInfo;

        return $val;
    }
}
