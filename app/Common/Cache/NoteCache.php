<?php
/**
 * User: fangcan
 * DateTime: 2023/2/21 17:01
 */

namespace App\Common\Cache;


use Illuminate\Support\Facades\Cache;

class NoteCache extends BaseCache
{
    //笔记类别列表
    private $noteClassListKey = 'noteClassList';
    public function getNoteClassList()
    {
        return Cache::get($this->noteClassListKey);
    }
    public function putNoteClassList($noteClassList)
    {
        return Cache::put($this->noteClassListKey, $noteClassList);
    }
    public function pullNoteClassList()
    {
        return Cache::pull($this->noteClassListKey);
    }
}
