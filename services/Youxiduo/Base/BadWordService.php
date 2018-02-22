<?php
namespace Youxiduo\Base;


class BadWordService
{
    //读取
    public static function readBadword(){
        $file_dir = storage_path() . '/meta/';
        $filename = 'keywords.txt';
        $newfile = $file_dir.$filename;
        if(!file_exists($newfile)){ //检测log.txt是否存在
            touch($newfile);
            chmod($newfile, 0777);
            $basicfile = __DIR__.'/../../../config/'.$filename;
            $badwords = file_get_contents($basicfile);
            return @file_put_contents($newfile,$badwords,FILE_APPEND);
        }
        $badwords = file($newfile,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $badwords;
    }
}
?>