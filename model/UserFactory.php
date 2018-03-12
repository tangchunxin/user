<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class UserFactory extends Factory
{
    const objkey = 'gfplay_mahjong_user_multi_';
    private $sql;
    public function __construct($dbobj, $uid) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$uid;
        $this->sql = "select
            `uid`
            , `key`
            , `status`
            , `wx_openid`
            , `wx_pic`
            , `name`

            , `sex`
            , `province`
            , `city`
            , `init_time`
            , `update_time`

            , `login_time`
            , `real_name_reg`

            from `user`
            where `uid`=".intval($uid)."";

        parent::__construct($dbobj, $serverkey, $objkey);
        return true;
    }

    public function retrive() 
    {
        $records = BaseFunction::query_sql_backend($this->sql);
        if( !$records ) 
        {
            return null;
        }

        $obj = null;
        while ( ($row = $records->fetch_row()) != false ) 
        {
            $obj = new User;

            $obj->uid = intval($row[0]);
            $obj->key = ($row[1]);
            $obj->status = intval($row[2]);
            $obj->wx_openid = ($row[3]);
            $obj->wx_pic = ($row[4]);
            $obj->name = ($row[5]);

            $obj->sex = intval($row[6]);
            $obj->province = ($row[7]);
            $obj->city = ($row[8]);
            $obj->init_time = intval($row[9]);
            $obj->update_time = intval($row[10]);

            $obj->login_time = intval($row[11]);
            $obj->real_name_reg = ($row[12]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

