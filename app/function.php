<?php

	use think\Db;

	/*
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *	工具方法
			 *	其他函数需要调用的公共方法
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 * */

	/**
	 * 通过闭包控制缓存
	 *
	 * @param          $key
	 * @param callable $func
	 * @param array    $params
	 * @param int      $time
	 * @param bool     $isForce
	 *
	 * @return mixed
	 */
	function autoCache($key , callable $func , $params = [] , $time = 1 , $isForce = false)
	{
		$result = cache($key);
		if(empty($result) || $isForce)
		{
			$result = call_user_func_array($func , $params);
			!empty($result) && cache($key , $result , $time);
		}

		return $result;
	}


	/**
	 * 闭包事物构造器
	 *
	 * @param array  $list           事物列表
	 * @param string $err            当事物执行失败时返回的错误信息
	 * @param null   $globalVariable 每个事物都可以作用到的共享变量，会push到每个事物元素的参数上
	 *
	 * @return bool
	 */
	function execClosureList($list = [] , &$err = null , &$globalVariable = null)
	{
		/*
		 * 		[
					[
						function() {
							//执行成功返回真
						} ,
						array(
							1 ,
							2 ,
						) ,
						'error massage'
					] ,
					[
						function() {
							//执行成功返回真
						} ,
						array(
							1 ,
							2 ,
						) ,
					] ,
				];

		 * */
		Db::startTrans();
		try
		{
			$flag = true;

			while (($flag !== false) && ($closure = array_shift($list)))
			{
				//没传参数设置参数为空数组
				!is_array($closure[1]) && $closure[1] = [];
				//传全局全局变量吧全局变量push到参数列表
				!is_null($globalVariable) && $closure[1][] = &$globalVariable;

				//执行闭包
				$flag = call_user_func_array($closure[0] , $closure[1]);

				($flag === false) && is_string($closure[2]) && ($err = $closure[2]);
			}

			($flag !== false) ? Db::commit() : Db::rollback();

			return $flag;
		} catch (\Exception $e)
		{
			Db::rollback();
			$err = $e->getMessage();

			return false;
		}
	}


	/**
	 * 格式化字节大小
	 *
	 * @param  number $size      字节数
	 * @param  string $delimiter 数字和单位分隔符
	 *
	 * @return string            格式化后的带单位的大小
	 */
	function formatBytes($size , $delimiter = '')
	{

		$units = [
			'B' ,
			'KB' ,
			'MB' ,
			'GB' ,
			'TB' ,
			'PB' ,
		];

		for ($i = 0; $size >= 1024 && $i < 5; $i++)
		{
			$size /= 1024;
		}

		return round($size , 2) . $delimiter . $units[$i];
	}


	/**
	 * 从带命名空间的类名类把类名提取出来
	 *
	 * @param object|string $class
	 *
	 * @return string
	 */
	function getClassBase($class)
	{
		$class = is_object($class) ? get_class($class) : $class;

		return basename(str_replace('\\' , '/' , $class));
	}


	/**
	 * 匹配出命名空间
	 * app\common\tool\permission\Rule
	 *
	 * @param object|string $class
	 *
	 * @return string
	 */
	function getNamespace($class)
	{
		preg_match('%^(.+)(?=[\\\\/][^\//]+$)%im' , $class , $result);

		return $result[1];
	}


	/*
	 *
	 *
	 * 						路径和url 相关
	 *
	 *
	 *
	 * */

	/*
	 *
	 *
	 * 计算路径
	 *
	 *
	 *
	 * */


	/**
	 * 计算文件路径
	 * 以常量为基础
	 *
	 * @param $path 20180717\a514e349fbb9233b3dc47d5ce2e0a82e.txt
	 *
	 * @return string 'F:\\localWeb\\public_local15\\public\\upload\\file\\20180717\a514e349fbb9233b3dc47d5ce2e0a82e.txt'
	 */
	function makeFilePath($path)
	{
		return replaceToSysSeparator(PATH_FILE . $path);
	}


	/**
	 * 计算图片路径
	 * 以常量为基础
	 *
	 * @param      $path    20180717\a514e349fbb9233b3dc47d5ce2e0a82e.png
	 * @param bool $isThumb 是否为缩略图路基
	 *
	 * @return string 'F:\\localWeb\\public_local15\\public\\upload\\file\\20180717\a514e349fbb9233b3dc47d5ce2e0a82e.png'
	 */
	function makeImgPath($path , $isThumb = false)
	{
		$path = $isThumb ? makeImgThumbName($path) : $path;

		return replaceToSysSeparator(PATH_PICTURE . $path);
	}

	/**
	 * 生成图片缩略图路径
	 *
	 * @param $path 20180717\a514e349fbb9233b3dc47d5ce2e0a82e.jpg
	 *
	 * @return null|string 20180717\thumb_a514e349fbb9233b3dc47d5ce2e0a82e.jpg
	 */
	function makeImgThumbName($path)
	{
		return preg_replace('%([^\\\\/]+$)%m' , 'thumb_$1' , $path);
	}

	/*
	 *
	 *
	 * 计算url
	 *
	 *
	 *
	 * */


	/**
	 * 计算文件url
	 * 以常量为基础
	 *
	 * @param      $path 20180717\a514e349fbb9233b3dc47d5ce2e0a82e.txt
	 *
	 * @return string 'http://local15.cc/upload/file/20180717\a514e349fbb9233b3dc47d5ce2e0a82e.txt'
	 */
	function makeFileUrl($path)
	{
		return replaceToUrlSeparator(URL_FILE . $path);
	}


	/**
	 * 计算图片url
	 * 以常量为基础
	 *
	 * @param      $path 20180717\a514e349fbb9233b3dc47d5ce2e0a82e.png
	 * @param bool $isThumb
	 *
	 * @return string 'http://local15.cc/upload/file/20180717\a514e349fbb9233b3dc47d5ce2e0a82e.png'
	 */
	function makeImgUrl($path , $isThumb = false)
	{
		$path = $isThumb ? makeImgThumbName($path) : $path;

		return replaceToUrlSeparator(URL_PICTURE . $path);
	}


	/**
	 * 自动计算头像url
	 *
	 * @param      $path 20180717\a514e349fbb9233b3dc47d5ce2e0a82e.txt
	 * @param int  $isThumb
	 *
	 * @return string 'http://local15.cc/upload/file/20180717\a514e349fbb9233b3dc47d5ce2e0a82e.txt'
	 */
	function generateProfilePicPath($path , $isThumb = 1)
	{
		$profile_pic = isImgExists($path , $isThumb) ? $path : config('default_profile_pic');

		return makeImgUrl($profile_pic , $isThumb);
	}

	/*
	 *
	 *
	 * 通用
	 *
	 *
	 *
	 * */


	/**
	 *    根据路径删除图片和缩略图
	 *
	 * @param      $path
	 */
	function delImg($path)
	{
		isImgExists($path , 0) && unlink(makeImgPath($path , 0));
		isImgExists($path , 1) && unlink(makeImgPath($path , 1));
	}


	/**
	 *    根据路径删除文件
	 *
	 * @param      $path
	 */
	function delFile($path)
	{
		isFileExists($path) && unlink(makeFilePath($path));
	}


	/**
	 *    根据路径判断指定图片文件在不在
	 *
	 * @param      $path
	 * @param bool $isThumb
	 *
	 * @return bool
	 */
	function isImgExists($path , $isThumb = false)
	{
		return is_file(makeImgPath($path , $isThumb));
	}

	/**
	 *    根据路径判断指定文件在不在
	 *
	 * @param      $path
	 *
	 * @return bool
	 */
	function isFileExists($path)
	{
		return is_file(makeFilePath($path));
	}

	/**
	 * 地址里的分隔符替换为url里的符号
	 *
	 * @param      $path
	 *
	 * @return string 'http://local15.cc/upload/file/20180717\a514e349fbb9233b3dc47d5ce2e0a82e.png'
	 */
	function replaceToUrlSeparator($path)
	{
		return strtr($path , [
			'\\' => '/' ,
		]);
	}

	/**
	 * 地址里的分隔符替换为系统分隔符
	 *
	 * @param      $path
	 *
	 * @return string 'http://local15.cc/upload/file/20180717\a514e349fbb9233b3dc47d5ce2e0a82e.png'
	 */
	function replaceToSysSeparator($path)
	{
		return strtr($path , [
			'\\' => DS ,
			'/'  => DS ,
		]);
	}


	/*
	 *
	 *
	 * 密码相关
	 *
	 *
	 *
	 * */


	/**
	 * 构造用户密码
	 *
	 * @param string $pwd 用户密码
	 *
	 * @return array
	 */
	function buildPwd($pwd)
	{
		$salt = substr(md5(microtime()) , rand(0 , 10) , 6);
		$nwePwd = makePwd($pwd , $salt);

		return array(
			'salt' => $salt ,
			'pwd'  => $nwePwd ,
		);
	}


	/**
	 * 密码验证
	 *
	 * @param string $userPwd 库里密码
	 * @param string $pwd     输入密码
	 * @param string $salt    库里盐
	 *
	 * @return bool
	 */
	function checkPwd($userPwd , $pwd , $salt)
	{
		return makePwd($pwd , $salt) == $userPwd;
	}

	/**
	 * @param  string $pwd  输入密码
	 * @param string  $salt 盐
	 *
	 * @return string
	 */
	function makePwd($pwd , $salt)
	{
		return md5(md5($pwd) . md5($salt));
	}


	/*
	 *
	 *
	 * session
	 *
	 *
	 *
	 * */


	/**
	 * 是否为全站管理员id
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	function isGlobalManagerId($id)
	{
		return $id == ADMIN_ID;
	}


	/**
	 * 是否为全站管理员登陆
	 * @return array|string|bool
	 */
	function isGlobalManager()
	{
		return isGlobalManagerId(getAdminSessionInfo(SESSOIN_TAG_USER , 'id'));
	}

	/**
	 * 指定平台是否登录
	 * @return array|string|bool
	 */
	function isAdminLogin()
	{
		return getAdminSessionInfo(SESSOIN_TAG_USER);
	}

	/**
	 * 获取用户session
	 *
	 * @param string $tag sesion 信息标签
	 * @param        $key
	 *
	 * @return array|string|bool
	 */
	function getAdminSessionInfo($tag , $key = null)
	{
		$info = session(SESSION_TAG_ADMIN . $tag);

		return isset($info[$key]) ? $info[$key] : $info;
	}


	/*
	 *
	 *
	 * 菜单相关
	 *	app\common\tool\permission\Auth
	 *
	 *
	 * */


	//传入id，获取所有子级id
	/**
	 * @param     $data
	 * @param int $id
	 *
	 * @return array
	 */
	function getSonId($data , $id = 0)
	{
		static $ids = [];
		foreach ($data as $k => $v)
		{
			if($v['pid'] == $id)
			{
				$ids[] = $v['id'];
				getSonId($data , $v['id']);
			}
		}

		return $ids;
	}


	//传入数组和id，获取返回所有子级
	/**
	 * @param $data
	 * @param $id
	 *
	 * @return array
	 */
	function getSon($data , $id)
	{
		$result = [];
		foreach ($data as $k => $v)
		{
			if($v['pid'] == $id)
			{
				$result[] = $v;
			}
		}

		return $result;
	}

	//添加等级段
	/**
	 * @param        $data
	 * @param int    $id
	 * @param int    $level
	 * @param string $parentField
	 *
	 * @return array
	 */
	function makeTree($data , $id = 0 , $level = 1 , $parentField = 'pid')
	{
		return \app\common\tool\permission\Auth::getInstance()::addLevel($data , $id , $level , $parentField);

	}
	/**统一格式化菜单
	 *
	 * @param $a
	 * @param $b
	 * @param $c
	 *
	 * @return string
	 */
	function formatMenu($a , $b , $c)
	{
		return \app\common\tool\permission\Auth::getInstance()::formatMenu($a , $b , $c);
	}

	//构造菜单的路径
	/**
	 * @param $data
	 *
	 * @return string
	 */
	function buildPath($data)
	{
		return strtolower($data['action']) == 'none' ? '#' : url($data['path']);
	}

	/**是否为默认菜单
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	function isDefault($data)
	{
		return strtolower($data['action']) == 'none';
	}

	/**
	 * 返回是否为菜单@param $data
	 * @return mixed
	 */
	function isMenu($data)
	{
		return $data['is_menu'];
	}

	/**
	 *        杂项
	 */


	/**权限列表里使用
	 *
	 * @param     $data
	 * @param int $level
	 *
	 * @return string
	 */
	function indentationByLevel($data , $level = 0)
	{
		$s = '';
		$margin = (($level - 1) * 30);
		($level > 1) && $s = '╘══ ';

		return "<span style='margin-left: " . $margin . "px;'>" . $s . $data . '</span>';
	}

	//option选项里使用
	/**
	 * @param     $data
	 * @param int $level
	 *
	 * @return string
	 */
	function indentationOptionsByLevel($data , $level = 0)
	{
		$s = '';
		($level > 0) && $s = '╘══ ';

		return str_repeat('&nbsp;' , ($level) * 8) . $s . $data;
	}


	/**
	 *        工具函数
	 */

	/**
	 * formatTime 格式化时间
	 *
	 * @param      $time
	 * @param bool $isTime
	 *
	 * @return false|string
	 */
	function formatTime($time , $isTime = true)
	{
		$format = 'Y-m-d';
		$isTime && $format .= ' H:i:s';

		return date($format , $time);
	}


	/**
	 * 计算时间间隔
	 *
	 * @param $timestamp
	 *
	 * @return false|string
	 */
	function humandate($timestamp)
	{
		$seconds = $_SERVER['REQUEST_TIME'] - $timestamp;
		if($seconds > 31536000)
		{
			return date('Y-n-j' , $timestamp);
		}
		elseif($seconds > 2592000)
		{
			return floor($seconds / 2592000) . '月前';
		}
		elseif($seconds > 86400)
		{
			return floor($seconds / 86400) . '天前';
		}
		elseif($seconds > 3600)
		{
			return floor($seconds / 3600) . '小时前';
		}
		elseif($seconds > 60)
		{
			return floor($seconds / 60) . '分钟前';
		}
		else
		{
			return $seconds . '秒前';
		}
	}

	/**
	 *     * 计算时间间隔
	 *
	 * @param $timestamp
	 *
	 * @return false|string
	 */
	function humandateA($timestamp)
	{
		$seconds = $_SERVER['REQUEST_TIME'] - $timestamp;
		if($seconds > 31536000)
		{
			return date('Y年m月d日' , $timestamp);
		}
		elseif($seconds > 2592000)
		{
			return floor($seconds / 2592000) . '月前';
		}
		elseif($seconds > 86400)
		{
			return floor($seconds / 86400) . '天前';
		}
		elseif($seconds > 3600)
		{
			return floor($seconds / 3600) . '小时前';
		}
		elseif($seconds > 60)
		{
			return floor($seconds / 60) . '分钟前';
		}
		else
		{
			return $seconds . '秒前';
		}
	}

	/**
	 * 更可靠的pathinfo函数代替
	 *
	 * @param $path
	 *
	 * @return mixed
	 */
	function pathinfo_($path)
	{
		preg_match("%^(?'dirname'.*?(?=[^.]+\.[^.]+$))(?'filename'[^\\\\/]*?)\.(?'extension'[a-z\d]*?)$%im" , $path , $res);
		$res['basename'] = $res['filename'] . '.' . $res['extension'];

		return $res;
	}


	/**
	 * 原生查询
	 *
	 * @param $sql
	 *
	 * @return mixed
	 */
	function querySql($sql)
	{
		return Db::query($sql);
	}

	/**
	 * 原生执行
	 *
	 * @param $sql
	 *
	 * @return int
	 */
	function executeSql($sql)
	{
		return Db::execute($sql);
	}


	//汉字转unicode
	function toUnicode($subject , $encoding = 'utf-8')
	{
		$result = preg_replace_callback('/[\x{4e00}-\x{9fff}]/ium' , function($group) use ($encoding) {
			$hex = ($group[0]);
			$t = iconv($encoding , 'UCS-2' , $hex);
			preg_match_all('/[\s\S]/' , $t , $res);

			$tmp = array_map(function($v) {
				return sprintf("%'02s" , base_convert(ord($v) , 10 , 16));
			} , $res[0]);

			return '\\u' . implode('' , $tmp);
		} , $subject);

		return $result;
	}


	//unicode转汉字
	function formUnicode($subject)
	{
		$result = preg_replace_callback('/\\\\u([a-z\d]{4})/im' , function($group) {
			$hex = $group[1];
			$a = base_convert($hex[0] . $hex[1] , 16 , 10);
			$b = base_convert($hex[2] . $hex[3] , 16 , 10);
			$c = chr($a) . chr($b);
			$c = iconv('UCS-2' , 'UTF-8' , $c);

			return $c;
		} , $subject);

		return $result;
	}


	/**
	 * 钩子
	 *
	 * @param string $tag
	 * @param array  $params
	 */
	function hook($tag = '' , $params = [])
	{
		\think\Hook::listen($tag , $params);
	}


	/**
	 * 跳过登陆的方法
	 *
	 * @param string $tag
	 *
	 * @return bool
	 */
	function skipAuth($tag)
	{
		return strtolower(substr($tag , -2 , 2)) == 'cc';
	}


	/**广度优先
	 *
	 * @param          $path
	 * @param callable $dirCallback
	 * @param callable $fileCallback
	 */

	function loop2($path , callable $dirCallback , callable $fileCallback)
	{
		$dirs = [realpath($path)];
		static $originPath = null;
		!$originPath && $originPath = $path;
		do
		{
			$item = array_shift($dirs);
			if(!$item) continue;

			if(is_dir($item))
			{
				!in_array(substr($item , -1) , [
					'/' ,
					'\\' ,
				]) && ($item .= DIRECTORY_SEPARATOR);
				$relativePath = str_replace($originPath , '' , $item);

				$dirs_ = scandir($item);
				$res = $dirCallback($item , $dirs_ , $relativePath , $originPath);
				if($res)
				{
					$dirs_ = array_map(function($v) use ($item) {
						return (!in_array($v , [
							'.' ,
							'..' ,
						])) ? $item . $v : '';
					} , $dirs_);
					$dirs = array_merge($dirs , $dirs_);
				}
				else
				{
					break;
				}
			}
			elseif(is_file($item))
			{
				$relativePath = str_replace($originPath , '' , $item);
				$fileCallback($item , pathinfo($item) , $relativePath , $originPath);
			}

			// file_put_contents('dd.txt', $item."\r\n", FILE_APPEND|LOCK_EX);
		} while (count($dirs));
	}


	/**深度优先
	 *
	 * @param          $path
	 * @param callable $dirCallback
	 * @param callable $fileCallback
	 */
	function loop1($path , callable $dirCallback , callable $fileCallback)
	{
		static $dep = 0;
		!in_array(substr($path , -1) , [
			'/' ,
			'\\' ,
		]) && ($path .= DIRECTORY_SEPARATOR);
		static $originPath = null;
		($dep === 0) && (!$originPath) && ($originPath = $path);
		$dep++;

		$dirs = scandir($path);
		foreach ($dirs as $k => $v)
		{

			if(!in_array($v , [
				'.' ,
				'..' ,
			]))
			{
				$fullPath = $path . $v;
				$relativePath = str_replace($originPath , '' , $fullPath);

				if(is_dir($fullPath))
				{
					$res = $dirCallback($fullPath , $dirs , $relativePath , $originPath);

					if($res)
					{
						loop1($fullPath , $dirCallback , $fileCallback);
					}
				}
				elseif(is_file($fullPath))
				{
					$fileCallback($fullPath , pathinfo($fullPath) , $relativePath , $originPath);
				}
				//file_put_contents('cc.txt', $fullPath."\r\n", FILE_APPEND|LOCK_EX);
			}
		}
		$dep--;

		($dep === 0) && ($originPath = null);
	}


	/**
	 * 自动创建目录
	 *
	 * @param $path
	 *
	 * @return bool
	 */
	function mkdir_($path)
	{
		return !is_dir(($path)) && mkdir(($path) , 777 , 1);
	}


	//递归复制文件夹-通用
	/**
	 * @param $config
	 *
	 * @return bool
	 */
	function cp($config)
	{
		$destinationDir = $config['des'];
		$originDir = $config['source'];
		!in_array(substr($originDir , -1) , [
			'/' ,
			'\\' ,
		]) && ($originDir .= DIRECTORY_SEPARATOR);
		!in_array(substr($destinationDir , -1) , [
			'/' ,
			'\\' ,
		]) && ($destinationDir .= DIRECTORY_SEPARATOR);
		/*
				print_r('-' .  $originDir);;;
				echo "\r\n";
				print_r('*' .  $destinationDir);;;
				echo "\r\n";
				echo "\r\n";
		*/


		loop1($originDir , function($path , $dirs_ , $relativePath , $originPath) use ($config , $destinationDir) {
			$pathinfo = pathinfo($path);
			$baseName = (iconv('gbk' , 'utf-8//IGNORE' , $pathinfo['basename']));

			foreach ($config['skip_dir_reg'] as $k => $v)
			{
				$flag = preg_match($v , $baseName);
				if($flag) return 0;
			}
			/*
				echo '111111++ '.$originPath."\r\n";
				echo '222222++ '.$relativePath."\r\n";
				echo '333333++ '.$path."\r\n";
				echo "\r\n";
			*/

			//返回真继续遍历下层，否则停止遍历此文件夹
			return 1;


		} , function($path , $pathinfo , $relativePath , $originPath) use ($config , $destinationDir) {

			//或者文件夹名字，在self::$skipDirs里就跳过
			$baseName = (iconv('gbk' , 'utf-8//IGNORE' , $pathinfo['basename']));
			foreach ($config['skip_file_reg'] as $k => $v)
			{
				$flag = preg_match($v , $baseName);
				if($flag) return 0;
			}

			$dest = $destinationDir . $relativePath;
			mkdir_(dirname($dest));
			copy($path , $dest);


			echo "\r\n";
			echo '复制' . "\r\n";
			echo $path . "\r\n";
			echo '到' . "\r\n";
			echo $destinationDir . "\r\n";
			echo '和' . "\r\n";
			echo $relativePath . "\r\n";
			echo '和--' . "\r\n";
			echo $destinationDir . $relativePath . "\r\n";
			echo "\r\n";


			return 1;
		});

		return true;
	}

	/**
	 * 删除指定文件夹下空目录
	 *
	 * @param $path
	 */
	function rm_empty_dir($path)
	{
		if(is_dir($path) && (@$handle = opendir($path)) !== false)
		{
			while (($file = readdir($handle)) !== false)
			{
				if($file != '.' && $file != '..')
				{
					$curfile = $path . '/' . $file;// 当前目录
					if(is_dir($curfile) && is_readable($curfile))
					{
						rm_empty_dir($curfile);// 如果是目录则继续遍历
						$a = @scandir($curfile);

						if($a[0] == '.' && $a[1] == '..' && !isset($a[2]))
						{
							//目录为空,=2是因为.和..存在
							$msg = '删除 -- ' . $curfile . "\r\n";
							file_put_contents('C:\Users\Administrator\Desktop\log.txt' , $msg , FILE_APPEND);
							echo $msg;
							@rmdir($curfile);// 删除空目录
						}
					}
				}
			}
			closedir($handle);
		}
	}


	/*
		$p = 'D:\VMHD';
	// $p = 'F:\localWeb\local2\ex9\public\upload';
		loop1($p, function($path, $dirs_, $relativePath){
			echo '------'.$relativePath."\r\n";
			echo '------'.$path."\r\n";
			echo "\r\n";

			//返回真继续遍历下层，否则停止遍历此文件夹
			return 1;

		}, function($path, $pathinfo, $relativePath){

			echo '++++++'.$relativePath."\r\n";
			echo '++++++'.$path."\r\n";
			echo "\r\n";
		});

		loop2($p, function($path, $dirs_, $relativePath){
			echo '------'.$relativePath."\r\n";
			echo '------'.$path."\r\n";
			echo "\r\n";
			//返回真继续遍历下层，否则停止遍历此文件夹
			return 1;

		}, function($path, $pathinfo, $relativePath){

			echo '++++++'.$relativePath."\r\n";
			echo '++++++'.$path."\r\n";
			echo "\r\n";
		});


			cp([
				'source'    => $path,
				'des'       => $savePath,
				//正则过滤文件
				'skip_file_reg'     => [
					'#模板之家#u',
					'#3000套#u',
					'#readme.txt#',
					'#说明.txt#u',
				],
				//正则过滤文件夹
				'skip_dir_reg'     => [
					//'#ima(?=ge)#',
				],
			]);

	*/

