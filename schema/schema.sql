/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `api_stats`
--

DROP TABLE IF EXISTS `api_stats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `api_stats` (
  `apikey` smallint(6) unsigned NOT NULL,
  `apiuser` varchar(20) NOT NULL,
  `hour` tinyint(2) unsigned NOT NULL default '0',
  `minute` tinyint(2) unsigned NOT NULL default '0',
  `requests` int(7) unsigned NOT NULL default '0',
  `hreq` smallint(4) unsigned NOT NULL default '0',
  `mreq` smallint(4) unsigned NOT NULL default '0',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`apikey`),
  UNIQUE KEY `apiuser` (`apiuser`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `battle_results`
--

DROP TABLE IF EXISTS `battle_results`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `battle_results` (
  `gametype` char(1) NOT NULL,
  `bot_id` int(5) unsigned NOT NULL,
  `bot_score` smallint(4) unsigned NOT NULL,
  `bot_bulletdmg` smallint(4) unsigned NOT NULL,
  `bot_survival` smallint(4) unsigned NOT NULL,
  `vs_id` int(5) unsigned NOT NULL,
  `vs_score` smallint(4) unsigned NOT NULL,
  `vs_bulletdmg` smallint(4) unsigned NOT NULL,
  `vs_survival` smallint(4) unsigned NOT NULL,
  `state` char(1) NOT NULL default '1',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `user_id` smallint(4) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL default '0000-00-00 00:00:00',
  `millisecs` smallint(3) unsigned NOT NULL,
  PRIMARY KEY  (`gametype`,`bot_id`,`vs_id`,`timestamp`,`millisecs`),
  KEY `scoring` (`gametype`,`bot_id`,`state`),
  KEY `versus` (`gametype`,`vs_id`,`state`),
  KEY `uploads` (`gametype`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `bot_data`
--

DROP TABLE IF EXISTS `bot_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `bot_data` (
  `bot_id` int(5) unsigned NOT NULL auto_increment,
  `full_name` varchar(70) NOT NULL,
  `package_name` varchar(20) NOT NULL,
  `bot_name` varchar(50) NOT NULL,
  `bot_version` varchar(20) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY  (`bot_id`),
  UNIQUE KEY `full_name` (`full_name`),
  KEY `family` (`package_name`,`bot_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `game_pairings`
--

DROP TABLE IF EXISTS `game_pairings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `game_pairings` (
  `gametype` char(1) NOT NULL,
  `bot_id` int(5) unsigned NOT NULL,
  `vs_id` int(5) unsigned NOT NULL,
  `battles` smallint(4) unsigned NOT NULL,
  `score_pct` int(5) unsigned NOT NULL,
  `score_dmg` int(5) unsigned NOT NULL,
  `score_survival` int(5) unsigned NOT NULL,
  `count_wins` smallint(4) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `state` char(1) NOT NULL default '1',
  PRIMARY KEY  (`gametype`,`bot_id`,`vs_id`),
  KEY `scoring` (`gametype`,`bot_id`,`state`),
  KEY `versus` (`gametype`,`vs_id`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `participants`
--

DROP TABLE IF EXISTS `participants`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `participants` (
  `gametype` char(1) NOT NULL,
  `state` char(1) NOT NULL default '1',
  `bot_id` int(5) unsigned NOT NULL,
  `battles` smallint(4) unsigned NOT NULL default '0',
  `score_pct` int(5) unsigned NOT NULL default '0',
  `rating_glicko` int(5) NOT NULL default '0',
  `score_dmg` int(5) unsigned NOT NULL default '0',
  `score_survival` int(5) unsigned NOT NULL default '0',
  `rd_glicko` int(5) unsigned NOT NULL default '0',
  `count_wins` smallint(4) unsigned NOT NULL default '0',
  `pairings` smallint(4) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL,
  `rating_classic` int(5) NOT NULL default '0',
  `rating_glicko2` int(5) NOT NULL default '0',
  `rd_glicko2` int(5) unsigned NOT NULL default '0',
  `vol_glicko2` int(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`gametype`,`bot_id`),
  KEY `active` (`gametype`,`state`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `participants_archive`
--

DROP TABLE IF EXISTS `participants_archive`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `participants_archive` (
  `gametype` char(1) NOT NULL,
  `state` char(1) NOT NULL default '1',
  `bot_id` int(5) unsigned NOT NULL,
  `battles` smallint(4) unsigned NOT NULL default '0',
  `score_pct` int(5) unsigned NOT NULL default '0',
  `rating_glicko` int(5) NOT NULL default '0',
  `score_dmg` int(5) unsigned NOT NULL default '0',
  `score_survival` int(5) unsigned NOT NULL default '0',
  `rd_glicko` int(5) unsigned NOT NULL default '0',
  `count_wins` smallint(4) unsigned NOT NULL default '0',
  `pairings` smallint(4) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL,
  `rating_classic` int(5) NOT NULL default '0',
  `rating_glicko2` int(5) NOT NULL default '0',
  `rd_glicko2` int(5) unsigned NOT NULL default '0',
  `vol_glicko2` int(5) unsigned NOT NULL default '0',
  `archived` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`gametype`,`bot_id`,`timestamp`),
  KEY `active` (`gametype`,`state`),
  KEY `archive` (`gametype`,`archived`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `properties` (
  `name` varchar(20) NOT NULL,
  `value` varchar(20) NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `upload_stats`
--

DROP TABLE IF EXISTS `upload_stats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `upload_stats` (
  `gametype` char(1) NOT NULL,
  `user_id` smallint(4) unsigned NOT NULL,
  `date` date NOT NULL,
  `battles` int(7) unsigned NOT NULL default '0',
  PRIMARY KEY  (`gametype`,`date`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `upload_users`
--

DROP TABLE IF EXISTS `upload_users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `upload_users` (
  `user_id` smallint(4) unsigned NOT NULL auto_increment,
  `username` varchar(20) NOT NULL,
  `ip_addr` varchar(15) NOT NULL,
  `version` varchar(20) NOT NULL,
  `battles` int(7) unsigned NOT NULL default '0',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`user_id`),
  KEY `user` (`username`,`ip_addr`,`version`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

