USE roborumble;

DROP TABLE IF EXISTS `bot_data`;
CREATE TABLE `bot_data` (
	`bot_id` int(5) UNSIGNED AUTO_INCREMENT UNIQUE NOT NULL,
	`full_name` varchar(70) NOT NULL,
	`package_name` varchar(20) NOT NULL,
	`bot_name` varchar (50) NOT NULL,
	`bot_version` varchar (20) NOT NULL,
	`timestamp` datetime NOT NULL,
	PRIMARY KEY (`bot_id`),
	KEY `name` (`full_name`(10)),
	KEY `family` (`package_name`, `bot_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `participants`;
CREATE TABLE `participants` (
	`gametype` char(1) NOT NULL,
	`state` char(1) NOT NULL DEFAULT '1',
	`bot_id` int(5) UNSIGNED NOT NULL,
	`battles` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
	`score_pct` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`score_dmg` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`score_survival` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`rating_classic` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`rating_glicko` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`rd_glicko` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`rating_glicko2` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`rd_glicko2` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`vol_glicko2` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`count_wins` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
	`pairings` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
	`timestamp` datetime NOT NULL,
	PRIMARY KEY (`gametype`, `bot_id`),
	KEY `active` (`gametype`, `state`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `participants`
CHANGE COLUMN `score_elo` `rating_glicko` int(5) UNSIGNED NOT NULL DEFAULT '0',
CHANGE COLUMN `deviation` `rd_glicko` int(5) UNSIGNED NOT NULL DEFAULT '0',
ADD COLUMN `rating_classic` int(5) UNSIGNED NOT NULL DEFAULT '0',
ADD COLUMN `rating_glicko2` int(5) UNSIGNED NOT NULL DEFAULT '0',
ADD COLUMN `rd_glicko2` int(5) UNSIGNED NOT NULL DEFAULT '0',
ADD COLUMN `vol_glicko2` int(5) UNSIGNED NOT NULL DEFAULT '0';

DROP TABLE IF EXISTS `battles_old`;
CREATE TABLE `battles_old` (
	`version` varchar(8) NOT NULL,
	`user` varchar(20) NOT NULL,
	`ip_addr` varchar(15) NOT NULL,
	`timestamp` datetime NOT NULL,
	`millisecs` smallint(3) NOT NULL,
	`gametype` char(1) NOT NULL,
	`bot_id` int(5) UNSIGNED NOT NULL,
	`bot_score` smallint(4) UNSIGNED NOT NULL,
	`bot_bulletdmg` smallint(4) UNSIGNED NOT NULL,
	`bot_survival` smallint(4) UNSIGNED NOT NULL,
	`vs_id` int(5) UNSIGNED NOT NULL,
	`vs_score` smallint(4) UNSIGNED NOT NULL,
	`vs_bulletdmg` smallint(4) UNSIGNED NOT NULL,
	`vs_survival` smallint(4) UNSIGNED NOT NULL,
	`state` char(1) NOT NULL DEFAULT '1',
	PRIMARY KEY (`ip_addr`, `gametype`, `bot_id`, `vs_id`, `timestamp`, `millisecs`),
	KEY `scoring` (`gametype`, `bot_id`, `state`),
	KEY `versus` (`gametype`, `vs_id`, `state`),
	KEY `updates` (`state`, `timestamp`, `millisecs`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `upload_users`;
CREATE TABLE `upload_users` (
	`user_id` smallint(4) UNSIGNED AUTO_INCREMENT UNIQUE NOT NULL,
	`username` varchar(20) NOT NULL,
	`ip_addr` varchar(15) NOT NULL,
	`version` varchar(20) NOT NULL,
	`battles` int(7) UNSIGNED NOT NULL DEFAULT '0',
	`updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`created` timestamp NOT NULL,
	PRIMARY KEY (`user_id`),
	KEY `user` (`username`, `ip_addr`, `version`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	

DROP TABLE IF EXISTS `battle_results`;
CREATE TABLE `battle_results` (
	`gametype` char(1) NOT NULL,
	`bot_id` int(5) UNSIGNED NOT NULL,
	`bot_score` smallint(4) UNSIGNED NOT NULL,
	`bot_bulletdmg` smallint(4) UNSIGNED NOT NULL,
	`bot_survival` smallint(4) UNSIGNED NOT NULL,
	`vs_id` int(5) UNSIGNED NOT NULL,
	`vs_score` smallint(4) UNSIGNED NOT NULL,
	`vs_bulletdmg` smallint(4) UNSIGNED NOT NULL,
	`vs_survival` smallint(4) UNSIGNED NOT NULL,
	`state` char(1) NOT NULL DEFAULT '1',
	`created` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
	`user_id` smallint(4) UNSIGNED NOT NULL,
	`timestamp` timestamp DEFAULT 0 NOT NULL,
	`millisecs` smallint(3) UNSIGNED NOT NULL,
	PRIMARY KEY (`gametype`, `bot_id`, `vs_id`, `timestamp`, `millisecs`),
	KEY `scoring` (`gametype`, `bot_id`, `state`),
	KEY `versus` (`gametype`, `vs_id`, `state`),
	KEY `updates` (`state`, `created`),
	KEY `uploads` (`gametype`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `game_pairings`;
CREATE TABLE `game_pairings` (
	`gametype` char(1) NOT NULL,
	`bot_id` int(5) UNSIGNED NOT NULL,
	`vs_id` int(5) UNSIGNED NOT NULL,
	`battles` smallint(4) UNSIGNED NOT NULL,
	`score_pct` int(5) UNSIGNED NOT NULL,
	`score_dmg` int(5) UNSIGNED NOT NULL,
	`score_survival` int(5) UNSIGNED NOT NULL,
	`count_wins` smallint(4) UNSIGNED NOT NULL,
	`timestamp` datetime NOT NULL,
	`state` char(1) NOT NULL DEFAULT '1',
	PRIMARY KEY (`gametype`, `bot_id`, `vs_id`),
	KEY `scoring` (`gametype`, `bot_id`, `state`)
	KEY `updates` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `properties`;
CREATE TABLE `properties` (
	`name`  varchar(20) NOT NULL,
	`value` varchar(20) NOT NULL,
	PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
