<?php
namespace bigcat\conf;
class Config
{

	const DEBUG = true;
	const LANGUAGE = 'cn';
	const PLATFORM = 'gfplay';

	const APP_NAME = 'ToCar';
	const APP_URL = 'http://test2.gfplay.cn';
    const AGENT_URL = 'http://127.0.0.1/mahjong/game_agent/fair_agent/index.php';
	const BASE_PATH = 'http://test2.gfplay.cn/user_wx/';
	//const STATIC_RESOURCES_URL = self::BASE_PATH;
	//const STATIC_RESOURCES_PATH = 'static';

	const MC_SERVERS = array(array('127.0.0.1',11211));

	const DB_HOST = '';
	const DB_USERNAME = '';
	const DB_PASSWD = '';
	const DB_DBNAME = '';
	const DB_PORT = '3306';

	const API_KEY = '';
	const RPC_KEY = '';

	//项目memcached 区分用的key前缀
	const KYE_NAME = 'chengde_';
	const HOST = 'test2.gfplay.cn';

	const WX_APPID = '';
    const WX_APPSECRET = '';
    const WX_MCHID = '';
	const WX_KEY = '';

	const IP_EXCEPTION = array(
	   '';
	);
}