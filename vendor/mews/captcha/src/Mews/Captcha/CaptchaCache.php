<?php
namespace Mews\Captcha;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Yxd\Modules\Core\CacheService;

class CaptchaCache
{
	/**
     * @var  Captcha  singleton instance of the Useragent object
     */
    protected static $singleton;

    /**
     * @var  Captcha config instance of the Captcha::$config object
     */
    public static $config = array();

    private static $id;
    private static $assets;
    private static $fonts = array();
    private static $backgrounds = array();
    private static $char;
	
    public static function instance()
    {

    	if ( ! CaptchaCache::$singleton)
    	{

    		self::$config = Config::get('captcha::config');
    		self::$assets = __DIR__ . '/../../../public/assets/';
    		self::$fonts = self::assets('fonts');
    		self::$backgrounds = self::assets('backgrounds');

    		CaptchaCache::$singleton = new CaptchaCache();

    	}

    	return CaptchaCache::$singleton;

    }
    
	public static function create($hashcode,$id=null)
	{
		static::$char = static::random(static::$config['length'], static::$config['type']);

        CacheService::put('captchaHash::'.$hashcode, Hash::make(static::$config['sensitive'] === true ? static::$char : Str::lower(static::$char)),15);

    	static::$id = $id ? $id : static::$config['id'];

        $bg_image = static::asset('backgrounds');

        $bg_image_info = getimagesize($bg_image);
        if ($bg_image_info['mime'] == 'image/jpg' || $bg_image_info['mime'] == 'image/jpeg')
        {
            $old_image = imagecreatefromjpeg($bg_image);
        }
        elseif ($bg_image_info['mime'] == 'image/gif')
        {
            $old_image = imagecreatefromgif($bg_image);
        }
        elseif ($bg_image_info['mime'] == 'image/png')
        {
            $old_image = imagecreatefrompng($bg_image);
        }

        $new_image = imagecreatetruecolor(static::$config['width'], static::$config['height']);
        $bg = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $bg);

        imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, static::$config['width'], static::$config['height'], $bg_image_info[0], $bg_image_info[1]);

        $bg = imagecolorallocate($new_image, 255, 255, 255);
        for ($i = 0; $i < strlen(static::$char); $i++)
        {
            $color_cols = explode(',', static::asset('colors'));
            $fg = imagecolorallocate($new_image, trim($color_cols[0]), trim($color_cols[1]), trim($color_cols[2]));
            imagettftext($new_image, static::asset('fontsizes'), rand(-10, 15), 10 + ($i * static::$config['space']), rand(static::$config['height'] - 10, static::$config['height'] - 5), $fg, static::asset('fonts'), static::$char[$i]);
        }
        imagealphablending($new_image, false);

        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Pragma: no-cache');
        header("Content-type: image/jpg");
        header('Content-Disposition: inline; filename=' . static::$id . '.jpg');
        imagejpeg($new_image, null, 80);
        imagedestroy($new_image);
	}
	
    public static function random($length,$type)
    {
    	if($type=='alpha'){
    		$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	}elseif($type=='alnum'){
    		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	}elseif($type=='custom'){
    		$pool = static::$config['custom_chars'];
    	}
    	
    	return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * Fonts
     *
     * @access  public
     * @param   string
     * @return  array
     */
    public static function assets($type = null) {

    	$files = array();

    	if ($type == 'fonts')
    	{
    		$ext = 'ttf';
    	}
    	elseif ($type == 'backgrounds')
    	{
    		$ext = 'png';
    	}

    	if ($type)
    	{
			foreach (glob(static::$assets . $type . '/*.' . $ext) as $filename)
			{
			    $files[] = $filename;
			}
		}

		return $files;

    }

    /**
     * Select asset
     *
     * @access  public
     * @param   string
     * @return  string
     */
    public static function asset($type = null)
    {

    	$file = null;

    	if ($type == 'fonts')
    	{
    		$file = static::$fonts[rand(0, count(static::$fonts) - 1)];
    	}
    	if ($type == 'backgrounds')
    	{
    		$file = static::$backgrounds[rand(0, count(static::$backgrounds) - 1)];
    	}
    	if ($type == 'fontsizes')
    	{
    		$file = static::$config['fontsizes'][rand(0, count(static::$config['fontsizes']) - 1)];
    	}
    	if ($type == 'colors')
    	{
    		$file = static::$config['colors'][rand(0, count(static::$config['colors']) - 1)];
    	}
        return $file;

    }
	
    public static function check($hashcode,$value)
    {

		$captchaHash = CacheService::get('captchaHash::'.$hashcode);

        $result = ($value != null && $captchaHash != null && Hash::check(static::$config['sensitive'] === true ? $value : Str::lower($value), $captchaHash));
        if($result===true) CacheService::forget('captchaHash::'.$hashcode);
        return $result;

    }
}