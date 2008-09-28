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
	`score_elo` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`score_dmg` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`score_survival` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`deviation` int(5) UNSIGNED NOT NULL DEFAULT '0',
	`count_wins` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
	`pairings` smallint(4) UNSIGNED NOT NULL DEFAULT '0',
	`timestamp` datetime NOT NULL,
	PRIMARY KEY (`gametype`, `bot_id`),
	KEY `active` (`gametype`, `state`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `battle_results`;
CREATE TABLE `battle_results` (
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

DROP TABLE IF EXISTS `game_pairings`;
CREATE TABLE `game_pairings` (
	`gametype` char(1) NOT NULL,
	`bot_id` int(5) UNSIGNED NOT NULL,
	`vs_id` int(5) UNSIGNED NOT NULL,
	`battles` smallint(4) UNSIGNED NOT NULL,
	`score_pct` int(5) UNSIGNED NOT NULL,
	`score_elo` int(5) UNSIGNED NOT NULL,
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
