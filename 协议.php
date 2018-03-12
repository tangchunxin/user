<?php  
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

exit();

//北京地区测试地址(内网/公网)，如果是服务端调用可以用内网地址
http://10.163.36.55/user_wx/index.php
http://test2.gfplay.cn//user_wx/index.php

//图形验证码地址
http://test2.gfplay.cn//user_wx/plug/authcode.php

////////////////////////////////////////////////

//协议规则
urlencode的格式用户信息（源格式json的）

//生成 randkey 函数
function encryptMD5($data)
{
	$content = '';
	if(!$data || !is_array($data))
	{
		return $content;
	}
	ksort($data);
	foreach ($data as $key => $value);
	{
		$content = $content.$key.$value;
	}
	if(!$content)
	{
		return $content;
	}
	
	return sub_encryptMD5($content);
}

function sub_encryptMD5($content)
{
	global $RPC_KEY;
	$content = $content.$RPC_KEY;
	$content = md5($content);
	if( strlen($content) > 10 )
	{
		$content = substr($content, 0, 10);
	}
	return $content;
}

//例子
$data = array('mod'=>'Business', 'act'=>'login', 'platform'=>'gfplay', 'uid'=>'13671301110');
$randkey = encryptMD5($data);
$_REQUEST = array('randkey'=>$randkey, 'c_version'=>'0.0.1', 'parameter'=>json_encode($data) );

c

#send check number
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'send_num'
		platform:'gfplay'
		uid	//帐号
		type	1	// 1 登录并图形验证   2 其他功能的验证码不包括图形验证
		authcode:	//（微信版可以空）图形校验，图形地址：用户模块URL路径/authcode.php
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 1 一分钟内不可重复操作 2 图形验证错误
	sub_desc	//sub_code 描述	
	data:

#register & login     
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login'
		platform: 'gfplay'		
		code	//微信授权码
	
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功1 2 还没绑定手机号
	sub_desc	//sub_code 描述	
	data:
		user	//用户对象
		openid	//微信openid
		access_token

# 绑定openid   
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login_bind'
		platform: 'gfplay'
		uid	//帐号
		num //验证码
		openid	//微信openid
		access_token
		p_aid  //推荐人id  上级id
		is_only_user:2   //帮我多传个字段 值为2  
		name  //名字
		provinces  //省份
		city   //城市


#get_jsapi_ticket 微信分享
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'get_jsapi_ticket'
		platform:'gfplay'
		uid	//帐号
		key //登录key
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 1 一分钟内不可重复操作 2 图形验证错误
	sub_desc	//sub_code 描述	
	"data": {
		"sign_obj": 
		{
			"appId": "wx8749c88617a05cc1",
			"nonceStr": "wz4XvAM7gEKJXobs",
			"timestamp": 1493697693,
			"url": "",
			"signature": "",
			"rawString": "jsapi_ticket=kgt8ON7yVITDhtdwci0qeZmoJigruOgRO6YQe_8aK7iNovhxe6dGWcaZn5UBP66dhKciIEbfJmQABOD7OYJqaA&noncestr=wz4XvAM7gEKJXobs&timestamp=1493697693&url="
		}
	},
	"module": "Business",
	"action": "get_jsapi_ticket"





#register & login     
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login'
		platform: 'gfplay'		
		code	//微信授权码
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 
	sub_desc	//sub_code 描述	
	data:
		user	//用户对象
//解除绑定
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'out_bind'
		platform: 'gfplay'
		uid	//帐号
		key //密码
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 1 还没绑定手机号
	sub_desc	//sub_code 描述	
	data:
		

	
#Login status check
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login_check'
		platform: 'gfplay'
		uid	//帐号
		key	//登陆用的
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		is_login
		user
		openid


#logout
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'logout'
		platform: 'gfplay'
		uid	//帐号
		key	//登陆用的
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:


#Set user content (name ...)
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'set_user'
		platform: 'gfplay'
		name: ''	//用户姓名
		uid	//帐号
		key	//登陆用的
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		user
		
		
#Get user info
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'get_user'
		platform: 'gfplay'
		uid	//帐号
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		user
		openid

		
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login_num_check'
		platform: 'gfplay'
		aid	//帐号
		num //短信验证码
		type //验证类型 1登录验证 2其他验证
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
	
