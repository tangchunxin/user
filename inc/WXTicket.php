<?php  

namespace bigcat\inc;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
use bigcat\conf\Config;


class WXTicket extends BaseObject 
{
	public $js_ticket = '';	//

	public static function get_sign($js_ticket)
	{
		$timestamp = time();
		$nonce_str = BaseFunction::create_nonce_str();
		$url = '';

		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$js_ticket&noncestr=$nonce_str&timestamp=$timestamp&url=$url";

		//$signature = sha1($string);

		$sign = array(
		"appId"     => Config::WX_APPID,
		"nonceStr"  => $nonce_str,
		"timestamp" => $timestamp,
		"url"       => $url,
		"signature" => '',
		"rawString" => $string
		);
		return $sign;
	}
}