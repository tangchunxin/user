<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\controller;

use bigcat\conf\Config;
use bigcat\inc\BaseFunction;
use bigcat\conf\CatConstant;

use bigcat\model\User;
use bigcat\model\UserFactory;
use bigcat\model\UserListFactory;
use bigcat\model\UserMultiFactory;

use bigcat\model\WxOpenid;
use bigcat\model\WxOpenidFactory;
use bigcat\model\WxOpenidListFactory;
use bigcat\model\WxOpenidMultiFactory;

use bigcat\inc\WXTokenFactory;
use bigcat\inc\WXTicketFactory;
use bigcat\inc\WXTicket;

class Business
{
	private $log = CatConstant::LOG_FILE;
	private $check_num_key = 'user_check_num_';
	private $check_add_agent_num_key = 'add_agent_check_num_';
	private $login_timeout = 31536000;	//3600 * 24 * 365
	public  $cache_handler = null;
	private $check_login_pwd ='check_login_pwd_';

	public function __construct()
	{
		if(empty($this->cache_handler))
		{
			$tmp = CatConstant::CACHE_TYPE;
			$this->cache_handler = $tmp::get_instance();
		}
	}

	public function send_num($params)
	{
		session_start();
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$data = array();
		$guoji = false;

		do {
			if( !isset($params['uid']) || !$params['uid'] || $params['uid'] == 86
			)
			{
				$response['code'] = CatConstant::ERROR; $response['sub_code'] = 4;$response['desc'] = __line__; break;
			}

			//国际短信
			if(substr($params['uid'],0,2) == 86 || $params['uid'] == 11112223333)
			{
				$guoji = false;
			}
			else
			{
				$guoji = true;
			}

			if(empty($params['type']))
			{
				$params['type'] = 1;
			}

			if(!$this->cache_handler->setKeep($params['uid'], 1, 3))
			{
				$response['sub_code'] = 1; $response['desc'] = __line__; break;
			}

			//if(1 == $params['type'] )  //改成  只要发送短信验证码  就必须 验证图形验证码
			//{

				if( !isset($params['authcode']) || !$params['authcode'])
				{
					$response['code'] = CatConstant::ERROR; $response['sub_code'] = 5;$response['desc'] = __line__; break;
				}

				if(empty($_SESSION['authcode']))
				{
					//$_SESSION['authcode'] = 'ncbd1234567890';
					$response['sub_code'] = 3;
					$response['desc'] = __line__; break;
				}

				if(  strtoupper(str_replace(" ","",$params['authcode']))!=strtoupper($_SESSION['authcode']) )
				{
					$response['sub_code'] = 2;
					BaseFunction::logger($this->log, "【uid】:\n".var_export($params['uid'], true)."\n".__LINE__."\n");
					BaseFunction::logger($this->log, "【authcode】:\n".var_export($params['authcode'], true)."\n".__LINE__."\n");
					BaseFunction::logger($this->log, "【authcode】:\n".var_export(strtoupper(str_replace(" ","",$params['authcode'])), true)."\n".__LINE__."\n");
					BaseFunction::logger($this->log, "【authcode】:\n".var_export($_SESSION['authcode'], true)."\n".__LINE__."\n");
					$response['desc'] = __line__; break;
				}
			//}

			$check_num = 4321;
			if(Config::DEBUG || $params['uid'] == 11112223333)
			{
				//测试帐号
				$check_num = 4321;	//test
			}
			else
			{
				$check_num = mt_rand(1000,9999);
			}

			//发送短信
			if($guoji == false )
			{
				//国内
				BaseFunction::sms_cz_alidayu("SMS_36375183", json_encode(array('code'=>strval($check_num), 'product'=>'灵飞棋牌')), $params['uid']);
			}
			else
			{
				//国际
				BaseFunction::sms_cz_tianruiyun($check_num, $params['uid'],'灵飞棋牌');
			}

			if(1 == $params['type'] )
			{
				$strkey = $this->check_num_key.$params['uid'];
			}
			else if (2 == $params['type'] )
			{
				$strkey = $this->check_add_agent_num_key.$params['uid'];
			}
			else
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			if( !($this->cache_handler->set( $strkey, $strkey, $check_num, 86400 )) )
			{
				BaseFunction::logger($this->log, "【memcached_set】:\n".var_export($this->cache_handler->get_result(), true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	//登录 code  openid
	public function login($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$tmp = '';

		do {
			if( empty($params['code'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$wx_user_info = BaseFunction::code_get_openid($params['code'], Config::WX_APPID, Config::WX_APPSECRET);
			if(!$wx_user_info)
			{
				$response['sub_code'] = 2; $response['desc'] = __line__; break;
			}

			if(isset($wx_user_info['openid']) && isset($wx_user_info['access_token']))
			{
				$data['openid'] = $wx_user_info['openid'];
				$data['access_token'] = $wx_user_info['access_token'];
				$response['data'] = $data;

				$obj_user_list_factory = new UserListFactory($this->cache_handler, $wx_user_info['openid']);
				if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
				{
					$obj_user_multi_factory = new UserMultiFactory($this->cache_handler, $obj_user_list_factory);
					if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
					{

						$obj_user_multi = $obj_user_multi_factory->get();
						$obj_user_multi_item = current($obj_user_multi);

						//判断是否查封或删除
						if($obj_user_multi_item->status == 1)
						{
							$response['sub_code'] = 3; $response['desc'] = __line__; break;
						}
						$key = substr(md5($itime), 0, 6);
						$obj_user_multi_item->key = $key;
						$obj_user_multi_item->login_time = $itime;
						$tmp = $obj_user_multi_item;

						$rawsqls[] = $obj_user_multi_item->getUpdateSql();
					}
					else
					{
						$response['sub_code'] = 1; $response['desc'] = __line__; break;//用户为绑定
					}

				}
				else
				{
					$response['sub_code'] = 1; $response['desc'] = __line__; break;//用户为绑定
				}
			}
			else
			{
				$response['sub_code'] = 2; $response['desc'] = __line__; break;//openid 未获取到
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			if(isset($obj_user_multi_factory))
			{
				$obj_user_multi_factory->writeback();
			}

			if(isset($obj_user_list_factory))
			{
				$obj_user_list_factory->clear();
			}
			$data['user'] = $tmp;
			$response['data'] = $data;

		}while(false);
		return $response;
	}

	public function login_bind($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$tmp = array();
		$is_use_agent = 2;
		$is_chafeng = false;

		do {
			if(empty($params['uid'])
			||empty($params['name'])
			||empty($params['openid'])
			||empty($params['access_token'])
			||empty($params['num'])
			||empty($params['provinces'])
			||empty($params['city'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			//验证短信验证码是否正确
			if(true)
			{
				$strkey = $this->check_num_key.$params['uid'];
				$check_num = $this->cache_handler->get( $strkey, $strkey );
				if( $check_num != str_replace(" ","",$params['num']))
				{
					BaseFunction::logger($this->log, "【login_dsafsacheck】:\n" . var_export($check_num, true) . "\n" . __LINE__ . "\n");
					BaseFunction::logger($this->log, "【login_check】:\n" . var_export($params['num'], true) . "\n" . __LINE__ . "\n");
					BaseFunction::sms_cz_alidayu("SMS_36375183", json_encode(array('code'=>$check_num.$params['num'], 'product'=>'灵飞棋牌')), '8618911554496');
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;//验证码错误
				}
			}

			$obj_user_list_factory = new UserListFactory($this->cache_handler, null , $params['uid']);
			if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
			{
				$response['sub_code'] = 2; $response['desc'] = __line__; break;//手机号码已经绑定
			}
			else
			{
				$obj_user_list_factory->clear();
			}

			$obj_user_list_factory = new UserListFactory($this->cache_handler, $params['openid']);
			if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
			{
				$response['sub_code'] = 3; $response['desc'] = __line__; break;//openid已经绑定
			}
			else
			{
				$obj_user_list_factory->clear();
			}

			if(!empty($params['is_use_agent']))
			{
				$is_only_user = $params['is_use_agent'];
			}

			if($is_use_agent == 2) //2需要兼容agent_info表
			{
				$result = $this->_agent_info($params);
				if (!$result || !isset($result['code']) || $result['code'] != 0 || (isset($result['sub_code']) && $result['sub_code'] != 0))
				{
					BaseFunction::logger($this->log, "【result】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
					$response['sub_code'] = $result['sub_code']; $response['desc'] = __line__; break;
				}

			}

			$key = substr(md5($itime), 0, 6);
			//绑定openid 和uid
			$user = new User();
			$user->uid = $params['uid'];
			$user->key = $key;
			$user->wx_openid = $params['openid'];

			//此时作用域  无法获取用户头像等信息
			// $wx_user_info = BaseFunction::code_get_wx_user($params['access_token'],$params['openid']);
			// if(!$wx_user_info)
			// {
			// 	$response['sub_code'] = 4; $response['desc'] = __line__; break;
			// }
			// if(isset($wx_user_info["nickname"]) && isset($wx_user_info["sex"]) && isset($wx_user_info["headimgurl"]) && isset($wx_user_info["city"]) && isset($wx_user_info["province"]))
			// {
			// 	$user->wx_pic = $wx_user_info['headimgurl'];
			// 	$user->name = $wx_user_info['nickname'];
			// 	$user->sex = $wx_user_info['sex'];
			// 	$user->city = $wx_user_info['city'];
			// 	$user->province = $wx_user_info['province'];
			// }
			$tmp_params['aid'] = $params['uid'];
			if($this->_judge_is_chafeng($tmp_params))
			{
				//如果账号被查封 ,则status=1;
				$user->status = 1;
				$is_chafeng = true;
			}
			else
			{
				$user->status = 0;
			}

			$user->init_time = $itime;
			$user->update_time = $itime;
			$user->login_time = $itime;
			$rawsqls[] = $user->getInsertSql();

			$tmp['uid'] = $user->uid;
			$tmp['key'] = $user->key;
			$tmp['openid'] = $user->wx_openid;

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			if($is_chafeng)
			{
				$response['sub_code'] = 6; $response['desc'] = __line__; break;
			}

			$data['user'] = $tmp;
			$response['data'] = $data;

		}while(false);
		return $response;
	}

	private function _agent_info($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$add_wx_agent = false;
		$update_agent_info = false;

		do{
			//判断是否为推广人员,模块调用
				$data_request = array(
				'mod' => 'Business'
				, 'act' => 'agent_info_test'
				, 'platform' => 'gfplay'
				, 'aid' => $params['uid']
				, 'is_form_user_wx' => 1    //证明来自user_wx  而不是  游戏客户端
			);
				$randkey = BaseFunction::encryptMD5($data_request);
				$url = Config::AGENT_URL . "?randkey=" . $randkey . "&c_version=0.0.1";
				$result = json_decode(BaseFunction::https_request($url, array('parameter' => json_encode($data_request))));
				if (!$result || !isset($result->code) || $result->code != 0 || (isset($result->sub_code) && $result->sub_code != 0))
				{
					if(empty($params['p_aid']))
					{

						$response['sub_code'] = 5; $response['desc'] = __line__; break;
					}
					else
					{
						$add_wx_agent = true;
					}
				}
				else
				{
					$update_agent_info = true;
				}
				$params['wx_id'] = !empty($params['wx_id']) ? $params['wx_id'] : ''; //沧州后台 后添加的功能
		
				if($add_wx_agent || $update_agent_info)
				{
					$data_request = array(
					'mod' => 'Business'
					, 'act' => 'add_or_update'
					, 'platform' => 'gfplay'
					, 'form' => 'wx_user'
					, 'aid' => $params['uid']
					, 'name' => $params['name']
					, 'provinces' => $params['provinces']
					, 'city' => $params['city']
					, 'p_aid' => $params['p_aid']
					, 'wx_id' => $params['wx_id']

					);
					$randkey = BaseFunction::encryptMD5($data_request);
					$url = Config::AGENT_URL . "?randkey=" . $randkey . "&c_version=0.0.1";
					$result = json_decode(BaseFunction::https_request($url, array('parameter' => json_encode($data_request))));
					if (!$result || !isset($result->code) || $result->code != 0 || (isset($result->sub_code) && $result->sub_code != 0))
					{
						BaseFunction::logger($this->log, "【data_request】:\n" . var_export($data_request, true) . "\n" . __LINE__ . "\n");
						BaseFunction::logger($this->log, "【agent_check】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
						$response['code'] = $result->code; $response['sub_code'] = $result->sub_code;  $response['desc'] = __line__; break;
					}
				}


		}while(false);
		return $response;
	}

	public function out_bind($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$tmp = array();

		do {
			if(empty($params['uid'])
			||empty($params['key'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_multi_factory = new UserMultiFactory($this->cache_handler,null, $params['uid']);
			if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
			{
				$obj_user_multi = $obj_user_multi_factory->get();
				$obj_user_multi_item = current($obj_user_multi);
				if($obj_user_multi_item->key = $params['key'])
				{
					$rawsqls[] = $obj_user_multi_item->getDelSql();
				}
				else
				{
					$response['sub_code'] = 1; $response['desc'] = __line__; break;
				}
			}
			else
			{
				$response['sub_code'] = 1; $response['desc'] = __line__; break;
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			if(isset($obj_user_multi_factory))
			{
				$obj_user_multi_factory->clear();
			}

			$response['data'] = $data;

		}while(false);
		return $response;
	}

	public function login_check($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			|| !isset($params['key']) || !$params['key']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$is_login = 0;

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$obj_user = $obj_user_factory->get();
			if( isset($obj_user->key) && $obj_user->key == $params['key'] && ($itime - $obj_user->login_time) < $this->login_timeout)
			{
				//判断是否查封或删除
				if($obj_user->status == 1)
				{
					$response['sub_code'] = 3; $response['desc'] = __line__; break;
				}
				$is_login = 1;

			}
			else
			{
				BaseFunction::logger($this->log, "【obj_user】:\n".var_export($params, true)."\n".__LINE__."\n");
				BaseFunction::logger($this->log, "【obj_user】:\n".var_export($itime - $obj_user->login_time, true)."\n".__LINE__."\n");
				BaseFunction::logger($this->log, "【obj_user】:\n".var_export($obj_user, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$data['openid'] = '';
			// $obj_wx_openid_multi_factory = new WxOpenidMultiFactory($this->cache_handler, null, $params['uid']);
			// if($obj_wx_openid_multi_factory->initialize() && $obj_wx_openid_multi_factory->get())
			// {
			// 	$obj_wx_openid_multi = $obj_wx_openid_multi_factory->get();
			// 	if($obj_wx_openid_multi && is_array($obj_wx_openid_multi))
			// 	{
			// 		foreach ($obj_wx_openid_multi as $obj_wx_openid_multi_item)
			// 		{
			// 			$data['openid'] = $obj_wx_openid_multi_item;
			// 		}
			// 	}
			// }

			$data['is_login'] = $is_login;
			$data['user'] = $obj_user;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function logout($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			|| !isset($params['key']) || !$params['key']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$obj_user = $obj_user_factory->get();
			// if( !isset($obj_user->key) || $obj_user->key != $params['key'])
			// {
			// 	$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			// }
			$obj_user->key = '';
			$obj_user->up_time = $itime;

			$rawsqls[] = $obj_user->getUpdateSql();
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}
			$obj_user_factory->writeback();
			$response['data'] = $data;

		}while(false);

		return $response;
	}

    public function set_user($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			|| !isset($params['name']) || !$params['name']
			|| !isset($params['key']) || !$params['key']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$obj_user = $obj_user_factory->get();
			if( !isset($obj_user->key) || !$obj_user->key == $params['key'] || ($itime - $obj_user->login_time) > $this->login_timeout)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user->name = $params['name'];
			$obj_user->up_time = $itime;

			$rawsqls[] = $obj_user->getUpdateSql();
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}
			$obj_user_factory->writeback();

			$data['user'] = $obj_user;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function get_user($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$data['openid'] = null;
			$obj_wx_openid_multi_factory = new WxOpenidMultiFactory($this->cache_handler, null, $params['uid']);
			if($obj_wx_openid_multi_factory->initialize() && $obj_wx_openid_multi_factory->get())
			{
				$obj_wx_openid_multi = $obj_wx_openid_multi_factory->get();
				if($obj_wx_openid_multi && is_array($obj_wx_openid_multi))
				{
					foreach ($obj_wx_openid_multi as $obj_wx_openid_multi_item)
					{
						$data['openid'] = $obj_wx_openid_multi_item;
					}
				}
			}

			$obj_user = $obj_user_factory->get();

			$data['user'] = $obj_user;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function get_conf()
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$data = array();
		do {
			$data['wx_appid'] = Config::WX_APPID;
			$data['wx_appsecret'] = Config::WX_APPSECRET;
			$data['wx_mchid'] = Config::WX_MCHID;
			$data['wx_key'] = Config::WX_KEY;
			
			$response['data'] = $data;
		} while (false);

		return $response;
	}


	public function login_num_check($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$data = array();

		do {
			if( !isset($params['num']) || !$params['num']
			|| !isset($params['aid']) || !$params['aid']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			if(empty($params['type'] ))
			{
				$params['type'] = 2;
			}

			$need_check_num = true;

			$data_uid = $params['aid'];
			if($need_check_num && $data_uid)
			{
				if(2 == $params['type'])
				{
					$strkey = $this->check_add_agent_num_key.$data_uid;
				}
				else
				{
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
				$check_num = $this->cache_handler->get( $strkey, $strkey );
				if( $check_num != str_replace(" ","",$params['num']))
				{
					BaseFunction::logger($this->log, "【login_check】:\n" . var_export($check_num, true) . "\n" . __LINE__ . "\n");
					BaseFunction::logger($this->log, "【login_check】:\n" . var_export($params['num'], true) . "\n" . __LINE__ . "\n");
					BaseFunction::sms_cz_alidayu("SMS_36375183", json_encode(array('code'=>$check_num.$params['num'], 'product'=>'灵飞棋牌')), '8618911554496');
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
			}

			$data[] = $check_num;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function get_jsapi_ticket($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if (empty($params['uid'])
				|| empty($params['key'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$login_result = $this->login_check($params);
			if ($login_result['code'] != 0 || $login_result['sub_code'] != 0)
			{
				$response['sub_code'] = 1;$response['desc'] = __line__;	break;
			}

			$sign_obj = null;

			//取得ticket
			$obj_wxticket_factory = new WXTicketFactory($this->cache_handler);
			if($obj_wxticket_factory->initialize() && $obj_wxticket_factory->get())
			{
				$obj_wxticket_tmp = $obj_wxticket_factory->get();
			}
			else
			{
				$obj_wxticket_factory->clear();
				$response['sub_code'] = 2; $response['desc'] = __line__; break;
			}
			if(isset($obj_wxticket_factory))
			{
				$obj_wxticket_factory->writeback();
			}

			//构造一个sign
			$obj_wxticket = new WXTicket($this->cache_handler);
			$result = $obj_wxticket->get_sign($obj_wxticket_tmp->js_ticket);
			if(!$result)
			{
				$response['sub_code'] = 3; $response['desc'] = __line__; break;
			}

			$data['sign_obj'] = $result;

			$response['data'] = $data;
		} while (false);

		return $response;
	}

	public function set_user_status($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$data = array();

		do {
			if( !isset($params['type']) || !$params['type']
			|| !isset($params['aid']) || !$params['aid']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_list_factory = new UserListFactory($this->cache_handler, null ,$params['aid']);
			if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
			{
				$obj_user_multi_factory = new UserMultiFactory($this->cache_handler, $obj_user_list_factory);
				if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
				{
					$obj_user_multi = $obj_user_multi_factory->get();
					$obj_user_multi_item = current($obj_user_multi);
					//判断是否查封或删除  type   1删除   2查封   3解封

					if($params['type'] == 1)
					{
						$rawsqls[] = $obj_user_multi_item->getDelSql();
					}
					elseif($params['type'] == 2)
					{
						$obj_user_multi_item->status = 1;  //查封
						$rawsqls[] = $obj_user_multi_item->getUpdateSql();
					}
					elseif($params['type'] == 3)
					{
						$obj_user_multi_item->status = 0;   //解封正常状态
						$rawsqls[] = $obj_user_multi_item->getUpdateSql();
					}
					elseif($params['type'] == 4) //清空微信openid,保留旧号码,,这样同一个微信就可以再次绑定
					{
						$obj_user_multi_item->status = 1;   //查封
						$obj_user_multi_item->wx_openid = '';   ///清空微信openid
						$rawsqls[] = $obj_user_multi_item->getUpdateSql();
					}
				}
				else
				{
					$response['sub_code'] = 1; $response['desc'] = __line__; break;
				}
			}
			else
			{
				$response['sub_code'] = 1; $response['desc'] = __line__; break;
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			if(isset($obj_user_multi_factory))
			{
				$obj_user_multi_factory->writeback();
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//判断是否查封
	private function _judge_is_chafeng($params)
	{
		$rawsqls = array();
		$itime = time();
		$data = array();

		do{
			//判断是否为推广人员,模块调用
				$data_request = array(
				'mod' => 'Business'
				, 'act' => 'judge_is_chafeng'
				, 'platform' => 'gfplay'
				, 'aid' => $params['aid']
				);
				$randkey = BaseFunction::encryptMD5($data_request);
				$url = Config::AGENT_URL . "?randkey=" . $randkey . "&c_version=0.0.1";
				$result = json_decode(BaseFunction::https_request($url, array('parameter' => json_encode($data_request))));
				if (!$result || !isset($result->code) || $result->code != 0 || (isset($result->sub_code) && $result->sub_code != 0))
				{
					BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($result, true)."\n".__LINE__."\n");
					BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($randkey, true)."\n".__LINE__."\n");
					if($result->sub_code != 0)  //sub_code!= 0 已被查封
					{
						return true;
					}
				}

		}while(false);
		return false;
	}




}