<?php 
namespace libraries;

class MakeDir {
	public static function makeDirectory($directoryName) {
		$directoryName = str_replace("\\","/",$directoryName);
		$dirNames = explode('/', $directoryName);
		$total = count($dirNames) ;
		$temp = '';
		for($i=0; $i<$total; $i++) {
			$temp .= $dirNames[$i].'/';
			if (!is_dir($temp)) {
				$oldmask = umask(0);
				if (!mkdir($temp, 0777)) exit("不能建立目录 $temp");
					umask($oldmask);
			}
		}
		return true;
	}
}


?>