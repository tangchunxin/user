<?php
exit();



/*------- CREATE SQL---------*/
//DROP TABLE IF EXISTS  `user`;
CREATE TABLE `user` (
  `uid` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `key` char(8) NOT NULL DEFAULT '' COMMENT '登录key',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态(0:通过  1:已封号)',
  `wx_openid` char(64) NOT NULL COMMENT '微信openID',
  `wx_pic` varchar(256) NOT NULL DEFAULT '' COMMENT '用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。',
  `name` char(32) NOT NULL DEFAULT '' COMMENT '用户名字',
  `sex` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '用户的性别，值为1时是男性，值为2时是女性，值为0时是未知',
  `province` char(32) NOT NULL DEFAULT '' COMMENT '省',
  `city` char(32) NOT NULL DEFAULT '' COMMENT '市',
  `init_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `login_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `real_name_reg` varchar(64) NOT NULL COMMENT '实名制登记',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `wx_openid` (`wx_openid`) USING BTREE,
  KEY `init_time` (`init_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表'

/*------- CREATE SQL---------*/
//DROP TABLE IF EXISTS  `wx_openid`;
CREATE TABLE `wx_openid` (
  `uid` bigint(20) unsigned NOT NULL,
  `openid` char(64) NOT NULL DEFAULT '' COMMENT '名字',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='uid openid 对因对应表';

ALTER TABLE `user`
ADD COLUMN `status` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `key`;