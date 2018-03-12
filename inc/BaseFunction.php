<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;
use bigcat\conf\Config;

class BaseFunction
{
	static $db_instance = null;

	public static function getMC()
	{
	     //单例
		global $gCache;
        $gCache = array();

		if( !isset($gCache['mcobj']) )
		{
			$mcobj = new CatMemcache(Config::MC_SERVERS);
			$gCache['mcobj'] = $mcobj;
		}

		return  $gCache['mcobj'];
	}


	//通过前端授权码code获得用户的微信openid
	public static function code_get_openid($code, $appid, $appsecret)
	{
		//获取openid
		$openid = '';
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
		$result = self::https_request($url);

		$jsoninfo = json_decode($result, true);
		if(isset($jsoninfo["errcode"]))
		{
			return false;//如果存在errcode  
		}
		return $jsoninfo;
	}

    //通过前端授权码code获得用户的微信openid
	public static function code_get_wx_user($access_token, $openid)
	{
		//获取openid
		$wx_user_info = [];
		//$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid";
		$result = self::https_request($url);

		$jsoninfo = json_decode($result, true);
		if(isset($jsoninfo["openid"]) && isset($jsoninfo["nickname"]) && isset($jsoninfo["sex"]) && isset($jsoninfo["headimgurl"]) && isset($jsoninfo["city"]) && isset($jsoninfo["province"]))
		{
			$wx_user_info = $jsoninfo;
		}
		return $wx_user_info;
	}

	//发短信函数 阿里大鱼
	public static function sms_cz_alidayu($templateCode, $sms_param, $phone, $signname = "灵飞棋牌")
	{
		$gearmanjson = array
		(
		'template_code'=>$templateCode
		, 'sms_param'=>$sms_param
		, 'phone'=>$phone
		, 'signname'=>$signname
		);

		try
		{
			$client= new \GearmanClient();
			$client->addServer('127.0.0.1', 4730);
			$client->doBackground('sms_cz', json_encode($gearmanjson));
		}catch(Exception $e)
		{
			self::logger('./log/sms.log', "【Exception】:\n" . var_export($e, true) . "\n" . __LINE__ . "\n");
			return false;
		}
		return true;
	}


	//生成随机串
	public static function create_nonce_str($length = 16)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		$chars_lenth = strlen($chars) - 1;
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, $chars_lenth), 1);
		}
		return $str;
	}

	public static function sms_cz_tianruiyun( $templateCode, $phone, $signname = "灵飞棋牌")
	{
		$gearmanjson = array
		(
		'template_code'=>$templateCode
		, 'phone'=>$phone
		, 'signname'=>$signname
		);

		try
		{
			$client= new \GearmanClient();
			$client->addServer('127.0.0.1', 4730);
			$client->doBackground('sms_cz_guoji', json_encode($gearmanjson));
		}catch(Exception $e)
		{
			self::logger('./log/sms.log', "【Exception】:\n" . var_export($e, true) . "\n" . __LINE__ . "\n");
			return false;
		}
		return true;
	}

	public static function time2str($itime)
	{
		if($itime)
		{
			return date('Y-m-d H:i:s', $itime);
		}
		return false;
	}

	public static function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	public static function output($response)
	{

		header('Cache-Control: no-cache, must-revalidate');
		header("Content-Type: text/plain; charset=utf-8");

		if(isset($_REQUEST['callback']) && $_REQUEST['callback'])
		{
			echo $_REQUEST['callback'].'('.json_encode($response).')';
		}
		else
		{
			echo json_encode($response);
		}
	}

	public static function output_html($html)
	{

		header('Cache-Control: no-cache, must-revalidate');
		header("Content-Type: text/html; charset=utf-8");

		echo ($html);
	}

	public static function encryptMD5($data)
	{
		$content = '';
		if(!$data || !is_array($data))
		{
			return $content;
		}
		ksort($data);
		foreach ($data as $key => $value)
		{
			$content = $content.$key.$value;
		}
		if(!$content)
		{
			return $content;
		}

		return self::sub_encryptMD5($content);
	}

	public static function sub_encryptMD5($content)
	{
		//global $RPC_KEY;
		$content = $content.Config::RPC_KEY;
		$content = md5($content);
		if( strlen($content) > 10 )
		{
			$content = substr($content, 0, 10);
		}
		return $content;
	}

	public static function https_request($url, $data = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	public static function logger($file,$word)
	{
		$fp = fopen($file,"a");
		flock($fp, LOCK_EX) ;
		fwrite($fp,"执行日期：".strftime("%Y-%m-%d %H:%M:%S",time())."\n".$word."\n\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}

	public static function get_client_ip()
	{
		$s_client_ip = '';

		if (isset($_SERVER['HTTP_X_REAL_IP']))
		{
			$s_client_ip = $_SERVER['HTTP_X_REAL_IP'];
		}
		elseif ($_SERVER['REMOTE_ADDR'])
		{
			$s_client_ip = $_SERVER['REMOTE_ADDR'];
		}
		elseif (getenv('REMOTE_ADDR'))
		{
			$s_client_ip = getenv('REMOTE_ADDR');
		}
		elseif (getenv('HTTP_CLIENT_IP'))
		{
			$s_client_ip = getenv('HTTP_CLIENT_IP');
		}
		else
		{
			$s_client_ip = 'unknown';
		}
		return $s_client_ip;
	}

	public static function getDB()
	{
		//单例
		if( empty(self::$db_instance) )
		{
			self::$db_instance = new \mysqli(Config::DB_HOST, Config::DB_USERNAME, Config::DB_PASSWD, Config::DB_DBNAME, Config::DB_PORT);
			if(empty(self::$db_instance) || !self::$db_instance->ping())
			{
				@self::$db_instance->close();
				if (!self::$db_instance->real_connect(Config::DB_HOST, Config::DB_USERNAME, Config::DB_PASSWD, Config::DB_DBNAME, Config::DB_PORT))
				{
					return false;
				}
			}
			self::$db_instance->query("set names 'utf8'");
			mb_internal_encoding('utf-8');
		}

		return  self::$db_instance;
	}

	public static function execute_sql_backend($rawsqls)
	{
		$result_arr = null;
		$is_rollback = false;

		if(!$rawsqls || !is_array($rawsqls))
		{
			return $result_arr;
		}

		$db_connect = self::getDB();
		$db_connect->autocommit(false);
		foreach ($rawsqls as $item_sql)
		{
			$result = null;
			$result = $db_connect->query($item_sql);
			if(!$result)
			{
				if($db_connect->rollback())
				{
					$is_rollback = true;
				}
				else
				{
					$db_connect->rollback();
					$is_rollback = true;
				}
				$result_arr = null;
				break;
			}
			if($db_connect->insert_id)
			{
				$result_arr[] = array('result'=>$result, 'insert_id'=>$db_connect->insert_id);
			}
			else
			{
				$result_arr[] = array('result'=>$result);
			}
		}

		if(!$is_rollback)
		{
			$db_connect->commit();
		}
		$db_connect->autocommit(true);
		return $result_arr;
	}

	public static function query_sql_backend($rawsql)
	{
		$db_connect = self::getDB();

		$result = $db_connect->query($rawsql);

		return $result;
	}


	/*
	* @inout $weights : array(1=>20, 2=>50, 3=>100);
	* @putput array
	*/
	public static function w_rand($weights)
	{

		$r = mt_rand(1, array_sum($weights));

		$offset = 0;
		foreach ( $weights as $k => $w )
		{
			$offset += $w;
			if ($r <= $offset)
			{
				return $k;
			}
		}

		return null;
	}

	public static function my_addslashes($str)
	{
		$str = str_replace(array("\r\n", "\r", "\n"), '', $str);
		return addslashes(stripcslashes($str));
	}

}