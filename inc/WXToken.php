<?php  

namespace bigcat\inc;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
use bigcat\conf\Config;


class WXToken extends BaseObject 
{
	public $access_token = '';	//
	public static function send_message($wx_openid, $message, $ac_token)
	{

		$data = array(
		'touser'=>$wx_openid,
		'msgtype'=>'text',
		'text'=>array('content'=>$message)
		);
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);

		$wx_result = json_decode(https_request("https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$ac_token, $data));

		return $wx_result;
	}
	
}