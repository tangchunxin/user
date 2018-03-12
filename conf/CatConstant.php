<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\conf;

class CatConstant
{

	const C_VERSION = '0.0.1';
	const CONF_VERSION = '0.0.1';
	const SECRET = 'Keep it simple stupid!';
	const CDKEY  = 'God bless you!';
	const LOG_FILE = './log/user.log';
	const CACHE_TYPE = '\bigcat\inc\CatMemcache';
	const C_VERSION_CHECK = true;
	
	const OK = 0;
	const ERROR = 1;
	const ERROR_MC = 2;
	const ERROR_INIT = 3;
	const ERROR_UPDATE = 4;
	const ERROR_VERIFY = 5;
	const ERROR_ARGUMENT = 6;
	const ERROR_VERSION = 7;
	
	const MODELS = array('Business' => '\bigcat\controller\Business');
	const UNCHECK_C_CERSION_ACT = array('Business' => ['get_conf']);
	const UNCHECK_VERIFIED_ACT = array('Business' => ['get_conf'
		                                            , 'send_num'
		                                            , 'login'
		                                            , 'login_check'
		                                            , 'login_bind'

		                                            ,'updata'
		                                            , 'out_bind'
		                                            , 'logout'
		                                            , 'set_user'
		                                            , 'get_user'
		                                            
		                                            ,'login_num_check'
		                                            ,'get_jsapi_ticket'
		                                            //,'set_user_status'


		                                            ]);	

	const SUB_DESC = array(
	'Business_send_num' => array('sub_code_1'=>'短信验证码已经发生,请稍等!','sub_code_2'=>'图形验证码错误','sub_code_3'=>'图形验证码未存入缓存','sub_code_4'=>'请检查写手机号码是否正确','sub_code_5'=>'请填写图形验证码')
	,'Business_login' => array('sub_code_2'=>'无法获取openid','sub_code_1'=>'用户未绑定手机号','sub_code_3'=>'用户已查封或被删除')
	,'Business_login_bind' => array('sub_code_1'=>'非推广人员','sub_code_2'=>'手机号已经被绑定','sub_code_3'=>'openid已经被绑定','sub_code_4'=>'无法获取微信用户信息','sub_code_5'=>'非推广人员或缺少上级推广员','sub_code_6'=>'改账号已被查封','sub_code_7'=>'该功能已关闭,暂时无法添加人员')
	,'Business_get_jsapi_ticket' => array('sub_code_1'=>'登录错误','sub_code_2'=>'取得ticket失败','sub_code_3'=>'构造sign_obj失败')
	
	);

}
