<?php  

namespace bigcat\inc;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
use bigcat\conf\Config;
use bigcat\inc\Factory;
use bigcat\inc\WXTicket;

class WXTicketFactory extends Factory {
	const objkey = 'wx_js_ticket_key_';
	public function __construct($dbobj) {
		$objkey = self::objkey.Config::WX_APPID;

		parent::__construct($dbobj, $objkey, $objkey, 7200);
		return true;
	}

	public function retrive() {
		$obj = new WXTicket();
		
		$mcobj = BaseFunction::getMC();

		//取得服务端 token
		$obj_wx_token_factory = new WXTokenFactory($mcobj);
		if(!$obj_wx_token_factory->initialize() || !$obj_wx_token_factory->get())
		{
			$obj_wx_token_factory->clear();
			return $obj;
		}
		$obj_wx_token = $obj_wx_token_factory->get();
		if(!isset($obj_wx_token))
		{
			return $obj;
		}

		//取得ticket
		$wx_result = json_decode(BaseFunction::https_request("https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$obj_wx_token->access_token.""));
		if( !$wx_result || !isset($wx_result->ticket) ) {
			return $obj;
		}
		$obj->js_ticket = $wx_result->ticket;	
		return $obj;
	}
}

