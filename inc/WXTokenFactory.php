<?php  

namespace bigcat\inc;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
use bigcat\conf\Config;
use bigcat\inc\Factory;

	class WXTokenFactory extends Factory {
		const objkey = 'wx_token_key_';
		private $sql;
		private $wx_appid;
		private $wx_appsecret;
		public function __construct($dbobj)
		{
			$objkey = self::objkey.Config::WX_APPID;
			$this->wx_appid = Config::WX_APPID;
			$this->wx_appsecret = Config::WX_APPSECRET;

			parent::__construct($dbobj, $objkey, $objkey, 7200);
			return true;
		}

		public function retrive()
		{

			$wx_result = json_decode(BaseFunction::https_request("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->wx_appid."&secret=".$this->wx_appsecret.""));

			if( !$wx_result || !isset($wx_result->access_token) ) {
				return null;
			}

			$obj = new WXToken();
			$obj->access_token = $wx_result->access_token;
			BaseFunction::logger("./log/user.log", "【access_token2】:\n" . var_export($obj, true) . "\n" . __LINE__ . "\n");
			return $obj;

		}
	}