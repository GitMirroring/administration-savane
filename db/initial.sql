-- Initial database.
--
-- Copyright (C) 1999, 2000 The SourceForge Crew
-- Copyright (C) 2000-2006 Mathieu Roy
-- Copyright (C) 2014, 2016, 2017 Assaf Gordon
-- Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
-- Copyright (C) 2013, 2014, 2017-2024 Ineiev
--
-- This file is part of Savane.
--
-- Code written before 2008-03-30 (commit 8b757b2565ff) is distributed
-- under the terms of the GNU General Public license version 3 or (at your
-- option) any later version; further contributions are covered by
-- the GNU Affero General Public license version 3 or (at your option)
-- any later version.  The license notices for the AGPL and the GPL follow.
--
-- Savane is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.
--
-- Savane is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
--
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.
--
-- Savane is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.
--
-- Savane is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS `bugs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs` (
  `bug_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `status_id` int(11) NOT NULL DEFAULT 100,
  `severity` int(11) NOT NULL DEFAULT 5,
  `privacy` int(2) NOT NULL DEFAULT 1,
  `discussion_lock` int(1) DEFAULT 0,
  `vote` int(11) NOT NULL DEFAULT 0,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `category_id` int(11) NOT NULL DEFAULT 100,
  `submitted_by` int(11) NOT NULL DEFAULT 100,
  `assigned_to` int(11) NOT NULL DEFAULT 100,
  `date` int(11) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `close_date` int(11) DEFAULT NULL,
  `bug_group_id` int(11) NOT NULL DEFAULT 100,
  `resolution_id` int(11) NOT NULL DEFAULT 100,
  `category_version_id` int(11) NOT NULL DEFAULT 100,
  `platform_version_id` int(11) NOT NULL DEFAULT 100,
  `reproducibility_id` int(11) NOT NULL DEFAULT 100,
  `size_id` int(11) NOT NULL DEFAULT 100,
  `fix_release_id` int(11) NOT NULL DEFAULT 100,
  `plan_release_id` int(11) NOT NULL DEFAULT 100,
  `hours` float(10,2) NOT NULL DEFAULT 0.00,
  `component_version` varchar(255) NOT NULL DEFAULT '',
  `fix_release` varchar(255) NOT NULL DEFAULT '',
  `plan_release` varchar(255) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT 5,
  `planned_starting_date` int(11) DEFAULT NULL,
  `planned_close_date` int(11) DEFAULT NULL,
  `percent_complete` int(11) NOT NULL DEFAULT 1,
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `release_id` int(11) NOT NULL DEFAULT 100,
  `release` varchar(255) NOT NULL DEFAULT '',
  `originator_name` varchar(255) NOT NULL DEFAULT '',
  `originator_email` varchar(255) NOT NULL DEFAULT '',
  `originator_phone` varchar(255) NOT NULL DEFAULT '',
  `custom_tf1` varchar(255) NOT NULL DEFAULT '',
  `custom_tf2` varchar(255) NOT NULL DEFAULT '',
  `custom_tf3` varchar(255) NOT NULL DEFAULT '',
  `custom_tf4` varchar(255) NOT NULL DEFAULT '',
  `custom_tf5` varchar(255) NOT NULL DEFAULT '',
  `custom_tf6` varchar(255) NOT NULL DEFAULT '',
  `custom_tf7` varchar(255) NOT NULL DEFAULT '',
  `custom_tf8` varchar(255) NOT NULL DEFAULT '',
  `custom_tf9` varchar(255) NOT NULL DEFAULT '',
  `custom_tf10` varchar(255) NOT NULL DEFAULT '',
  `custom_ta1` text DEFAULT NULL,
  `custom_ta2` text DEFAULT NULL,
  `custom_ta3` text DEFAULT NULL,
  `custom_ta4` text DEFAULT NULL,
  `custom_ta5` text DEFAULT NULL,
  `custom_ta6` text DEFAULT NULL,
  `custom_ta7` text DEFAULT NULL,
  `custom_ta8` text DEFAULT NULL,
  `custom_ta9` text DEFAULT NULL,
  `custom_ta10` text DEFAULT NULL,
  `custom_sb1` int(11) NOT NULL DEFAULT 100,
  `custom_sb2` int(11) NOT NULL DEFAULT 100,
  `custom_sb3` int(11) NOT NULL DEFAULT 100,
  `custom_sb4` int(11) NOT NULL DEFAULT 100,
  `custom_sb5` int(11) NOT NULL DEFAULT 100,
  `custom_sb6` int(11) NOT NULL DEFAULT 100,
  `custom_sb7` int(11) NOT NULL DEFAULT 100,
  `custom_sb8` int(11) NOT NULL DEFAULT 100,
  `custom_sb9` int(11) NOT NULL DEFAULT 100,
  `custom_sb10` int(11) NOT NULL DEFAULT 100,
  `custom_df1` int(11) NOT NULL DEFAULT 0,
  `custom_df2` int(11) NOT NULL DEFAULT 0,
  `custom_df3` int(11) NOT NULL DEFAULT 0,
  `custom_df4` int(11) NOT NULL DEFAULT 0,
  `custom_df5` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_id`),
  KEY `idx_bug_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `bugs_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_canned_responses` (
  `bug_canned_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `title` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `order_id` int(11) NOT NULL DEFAULT 50,
  PRIMARY KEY (`bug_canned_id`),
  KEY `idx_bug_canned_response_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `bugs_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_cc` (
  `bug_cc_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `added_by` int(11) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_cc_id`),
  KEY `bug_id_idx` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `bugs_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_dependencies` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id_artifact` varchar(255) NOT NULL DEFAULT '0',
  KEY `idx_item_dependencies_bug_id` (`item_id`),
  KEY `idx_item_is_dependent_on_item_id` (`is_dependent_on_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `bugs_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_field` (
  `bug_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(255) NOT NULL DEFAULT '',
  `display_type` varchar(255) NOT NULL DEFAULT '',
  `display_size` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `scope` char(1) NOT NULL DEFAULT '',
  `required` int(11) NOT NULL DEFAULT 0,
  `empty_ok` int(11) NOT NULL DEFAULT 0,
  `keep_history` int(11) NOT NULL DEFAULT 0,
  `special` int(11) NOT NULL DEFAULT 0,
  `custom` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_field_id`),
  KEY `idx_bug_field_name` (`field_name`)
) ENGINE=MyISAM AUTO_INCREMENT=606 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bugs_field` WRITE;
/*!40000 ALTER TABLE `bugs_field` DISABLE KEYS */;
INSERT INTO `bugs_field` VALUES (90,'bug_id','TF','6/10','Item ID','Unique item identifier','S',1,0,0,1,0),(91,'group_id','TF','','Group ID','Unique group identifier','S',1,0,0,1,0),(92,'submitted_by','SB','','Submitted by','User who originally submitted the item','S',1,1,0,1,0),(93,'date','DF','10/15','Submitted on','Date and time of the initial submission','S',1,0,0,1,0),(94,'close_date','DF','10/15','Closed on','Date and time when the item  status was changed to \'Closed\'','S',1,1,0,1,0),(101,'status_id','SB','','Open/Closed','Most basic status of the item: is the item considered as dealt with or not.','S',1,0,1,0,0),(102,'severity','SB','','Severity','Impact of the item on the system (Critical, Major,...)','S',0,1,1,0,0),(103,'category_id','SB','','Category','Generally high level modules or functionalities of the software (e.g. User interface, Configuration Manager, etc)','P',0,1,1,0,0),(104,'assigned_to','SB','','Assigned to','Who is in charge of handling this item','S',1,1,1,0,0),(105,'summary','TF','65/120','Summary','One line description of the item','S',1,0,1,1,0),(106,'details','TA','65/20','Original Submission','Full description of the item','S',1,1,1,1,0),(107,'bug_group_id','SB','','Item Group','Characterizes the nature of the item (e.g. Crash Error, Documentation Typo, Installation Problem, etc','P',0,1,1,0,0),(108,'resolution_id','SB','','Status','Current status of the item','P',1,1,1,0,0),(200,'category_version_id','SB','','Component Version','Version of the System Component (aka Item Category) impacted by the item','P',0,1,1,0,0),(201,'platform_version_id','SB','','Operating System','Operating System impacted by the issue','P',0,1,1,0,0),(202,'reproducibility_id','SB','','Reproducibility','How easy it is to reproduce the item','S',0,1,1,0,0),(203,'size_id','SB','','Size (loc)','Estimated size of the code to be developed or reworked to fix the item','S',0,1,1,0,0),(204,'fix_release_id','SB','','Fixed Release','Release in which the item was actually fixed','P',0,1,1,0,0),(205,'comment_type_id','SB','','Comment Type','Specify the nature of the follow up comment attached to this item','P',1,1,0,1,0),(206,'hours','TF','5/5','Effort','Number of hours of work needed to fix the item','S',0,1,1,0,0),(207,'plan_release_id','SB','','Planned Release','Release in which it is planned to have the item fixed','P',0,1,1,0,0),(208,'component_version','TF','10/40','Component Version','Version of the system component (or work product) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(209,'fix_release','TF','10/40','Fixed Release','Release in which the bug was actually fixed (<u>in free text</u>)','S',0,1,1,0,0),(210,'plan_release','TF','10/40','Planned Release','Release in which it is planned to have the item fixed (<u>in free text</u>)','S',0,1,1,0,0),(211,'priority','SB','','Priority','How quickly the item should be handled','S',1,0,1,0,0),(212,'keywords','TF','60/120','Keywords','List of comma separated keywords associated with this item','S',0,1,1,0,0),(213,'release_id','SB','','Release','Release (global version number) impacted by the item','P',0,1,1,0,0),(214,'release','TF','10/40','Release','Release (global version number) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(215,'originator_name','TF','20/40','Originator Name','Name of the person who submitted the item (if different from the submitter field)','S',0,1,1,0,0),(216,'originator_email','TF','20/40','Originator Email','Email address of the person who submitted the item (if different from the submitter field, add address to CC list)','S',0,1,1,0,0),(217,'originator_phone','TF','10/40','Originator Phone','Phone number of the person who submitted the item','S',0,1,1,0,0),(220,'percent_complete','SB','','Percent Complete','','S',0,1,1,0,0),(300,'custom_tf1','TF','10/15','Custom Text Field #1','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(301,'custom_tf2','TF','10/15','Custom Text Field #2','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(302,'custom_tf3','TF','10/15','Custom Text Field #3','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(303,'custom_tf4','TF','10/15','Custom Text Field #4','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(304,'custom_tf5','TF','10/15','Custom Text Field #5','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(305,'custom_tf6','TF','10/15','Custom Text Field #6','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(306,'custom_tf7','TF','10/15','Custom Text Field #7','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(307,'custom_tf8','TF','10/15','Custom Text Field #8','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(308,'custom_tf9','TF','10/15','Custom Text Field #9','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(309,'custom_tf10','TF','10/15','Custom Text Field #10','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(400,'custom_ta1','TA','60/3','Custom Text Area #1','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(401,'custom_ta2','TA','60/3','Custom Text Area #2','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(402,'custom_ta3','TA','60/3','Custom Text Area #3','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(403,'custom_ta4','TA','60/3','Custom Text Area #4','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(404,'custom_ta5','TA','60/3','Custom Text Area #5','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(405,'custom_ta6','TA','60/3','Custom Text Area #6','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(406,'custom_ta7','TA','60/3','Custom Text Area #7','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(407,'custom_ta8','TA','60/3','Custom Text Area #8','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(408,'custom_ta9','TA','60/3','Custom Text Area #9','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(409,'custom_ta10','TA','60/3','Custom Text Area #10','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(500,'custom_sb1','SB','','Custom Select Box #1','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(501,'custom_sb2','SB','','Custom Select Box #2','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(502,'custom_sb3','SB','','Custom Select Box #3','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(503,'custom_sb4','SB','','Custom Select Box #4','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(504,'custom_sb5','SB','','Custom Select Box #5','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(505,'custom_sb6','SB','','Custom Select Box #6','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(506,'custom_sb7','SB','','Custom Select Box #7','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(507,'custom_sb8','SB','','Custom Select Box #8','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(508,'custom_sb9','SB','','Custom Select Box #9','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(509,'custom_sb10','SB','','Custom Select Box #10','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(600,'custom_df1','DF','10/10','Custom Date Field #1','Customizable Date Field','P',0,1,1,0,1),(601,'custom_df2','DF','10/10','Custom Date Field #2','Customizable Date Field','P',0,1,1,0,1),(602,'custom_df3','DF','10/10','Custom Date Field #3','Customizable Date Field','P',0,1,1,0,1),(603,'custom_df4','DF','10/10','Custom Date Field #4','Customizable Date Field','P',0,1,1,0,1),(604,'custom_df5','DF','10/10','Custom Date Field #5','Customizable Date Field','P',0,1,1,0,1),(109,'privacy','SB','','Privacy','Determines whether the item can be seen by members of the group only or anybody.','S',0,1,1,0,0),(110,'vote','TF','6/10','Votes','How many votes this item received','S',0,1,0,1,0),(112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0),(605,'updated','DF','10/15','Updated','Last time the item was updated','S',1,1,0,1,0);
/*!40000 ALTER TABLE `bugs_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `bugs_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_field_usage` (
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `use_it` int(11) NOT NULL DEFAULT 0,
  `show_on_add` int(11) NOT NULL DEFAULT 0,
  `show_on_add_members` int(11) NOT NULL DEFAULT 0,
  `place` int(11) NOT NULL DEFAULT 0,
  `custom_label` varchar(255) DEFAULT NULL,
  `custom_description` varchar(255) DEFAULT NULL,
  `custom_display_size` varchar(255) DEFAULT NULL,
  `custom_empty_ok` int(11) DEFAULT NULL,
  `custom_keep_history` int(11) DEFAULT NULL,
  `transition_default_auth` char(1) NOT NULL DEFAULT 'A',
  KEY `idx_bug_fu_field_id` (`bug_field_id`),
  KEY `idx_bug_fu_group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bugs_field_usage` WRITE;
/*!40000 ALTER TABLE `bugs_field_usage` DISABLE KEYS */;
INSERT INTO `bugs_field_usage` VALUES (90,100,1,0,0,10,NULL,NULL,NULL,NULL,NULL,'A'),(91,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL,'A'),(92,100,1,0,0,20,NULL,NULL,NULL,NULL,NULL,'A'),(93,100,1,0,0,40,NULL,NULL,NULL,NULL,NULL,'A'),(94,100,1,0,0,50,NULL,NULL,NULL,NULL,NULL,'A'),(101,100,1,0,0,600,NULL,NULL,NULL,NULL,NULL,'A'),(102,100,1,0,1,200,NULL,NULL,NULL,NULL,NULL,'A'),(102,100,1,1,1,200,NULL,NULL,NULL,NULL,NULL,'A'),(102,101,1,2,0,200,NULL,NULL,NULL,0,1,'A'),(103,100,1,1,1,100,NULL,NULL,NULL,NULL,NULL,'A'),(103,101,1,2,1,102,NULL,NULL,NULL,0,1,'A'),(104,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,'A'),(105,100,1,1,1,700000,NULL,NULL,NULL,NULL,NULL,'A'),(106,100,1,1,1,700001,NULL,NULL,NULL,NULL,NULL,'A'),(107,100,0,1,1,300,NULL,NULL,NULL,NULL,NULL,'A'),(107,100,1,1,1,300,NULL,NULL,NULL,NULL,NULL,'A'),(108,100,1,0,1,400,NULL,NULL,NULL,NULL,NULL,'A'),(109,100,1,1,1,402,NULL,NULL,NULL,NULL,NULL,'A'),(110,100,1,1,1,405,NULL,NULL,NULL,NULL,NULL,'A'),(112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(200,100,0,0,0,1000,NULL,NULL,NULL,NULL,NULL,'A'),(201,100,0,0,0,1100,NULL,NULL,NULL,NULL,NULL,'A'),(202,100,0,0,0,1200,NULL,NULL,NULL,NULL,NULL,'A'),(203,100,0,0,0,1300,NULL,NULL,NULL,NULL,NULL,'A'),(204,100,0,0,0,1400,NULL,NULL,NULL,NULL,NULL,'A'),(204,101,0,3,1,1400,NULL,NULL,NULL,1,1,'A'),(205,100,1,0,0,1500,NULL,NULL,NULL,NULL,NULL,'A'),(206,100,0,0,0,1700,NULL,NULL,NULL,NULL,NULL,'A'),(207,100,0,0,0,1600,NULL,NULL,NULL,NULL,NULL,'A'),(208,100,0,0,0,1800,NULL,NULL,NULL,NULL,NULL,'A'),(209,100,0,0,0,1900,NULL,NULL,NULL,NULL,NULL,'A'),(210,100,0,0,0,2000,NULL,NULL,NULL,NULL,NULL,'A'),(211,100,0,0,0,250,NULL,NULL,NULL,NULL,NULL,'A'),(211,100,1,0,1,200,NULL,NULL,NULL,NULL,NULL,'A'),(212,100,0,0,0,3000,NULL,NULL,NULL,NULL,NULL,'A'),(213,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(213,101,1,3,1,800,NULL,NULL,NULL,1,1,'A'),(214,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(214,101,0,3,1,800,NULL,NULL,'10/40',1,1,''),(215,100,0,0,0,550,NULL,NULL,NULL,NULL,NULL,'A'),(216,100,0,0,0,560,NULL,NULL,NULL,NULL,NULL,'A'),(216,100,1,2,0,560,NULL,NULL,NULL,NULL,NULL,'A'),(216,101,1,2,0,560,NULL,NULL,'20/40',0,1,''),(217,100,0,0,0,570,NULL,NULL,NULL,NULL,NULL,'A'),(217,101,0,0,1,570,NULL,NULL,'10/40',1,1,''),(220,100,0,0,0,500,NULL,NULL,NULL,NULL,NULL,'A'),(300,100,0,0,0,30000,NULL,NULL,NULL,NULL,NULL,'A'),(300,101,1,0,0,3000,'&lt;b name=&quot;custom 1&quot;&gt;','&lt;a href=&quot;edited description&quot;&gt;','10/15',1,1,''),(301,100,0,0,0,30100,NULL,NULL,NULL,NULL,NULL,'A'),(302,100,0,0,0,30200,NULL,NULL,NULL,NULL,NULL,'A'),(303,100,0,0,0,30300,NULL,NULL,NULL,NULL,NULL,'A'),(304,100,0,0,0,30400,NULL,NULL,NULL,NULL,NULL,'A'),(305,100,0,0,0,30500,NULL,NULL,NULL,NULL,NULL,'A'),(306,100,0,0,0,30600,NULL,NULL,NULL,NULL,NULL,'A'),(307,100,0,0,0,30700,NULL,NULL,NULL,NULL,NULL,'A'),(308,100,0,0,0,30800,NULL,NULL,NULL,NULL,NULL,'A'),(309,100,0,0,0,30900,NULL,NULL,NULL,NULL,NULL,'A'),(400,100,0,0,0,40000,NULL,NULL,NULL,NULL,NULL,'A'),(401,100,0,0,0,40100,NULL,NULL,NULL,NULL,NULL,'A'),(402,100,0,0,0,40200,NULL,NULL,NULL,NULL,NULL,'A'),(403,100,0,0,0,40300,NULL,NULL,NULL,NULL,NULL,'A'),(404,100,0,0,0,40400,NULL,NULL,NULL,NULL,NULL,'A'),(405,100,0,0,0,40500,NULL,NULL,NULL,NULL,NULL,'A'),(406,100,0,0,0,40600,NULL,NULL,NULL,NULL,NULL,'A'),(407,100,0,0,0,40700,NULL,NULL,NULL,NULL,NULL,'A'),(408,100,0,0,0,40800,NULL,NULL,NULL,NULL,NULL,'A'),(409,100,0,0,0,40900,NULL,NULL,NULL,NULL,NULL,'A'),(500,100,0,0,0,50000,NULL,NULL,NULL,NULL,NULL,'A'),(500,101,1,0,0,50000,'&lt;select name=&quot;box1&quot;&gt;','&lt;customizable select &quot;box&quot;&gt;',NULL,1,1,'A'),(501,100,0,0,0,50100,NULL,NULL,NULL,NULL,NULL,'A'),(502,100,0,0,0,50200,NULL,NULL,NULL,NULL,NULL,'A'),(503,100,0,0,0,50300,NULL,NULL,NULL,NULL,NULL,'A'),(504,100,0,0,0,50400,NULL,NULL,NULL,NULL,NULL,'A'),(505,100,0,0,0,50500,NULL,NULL,NULL,NULL,NULL,'A'),(506,100,0,0,0,50600,NULL,NULL,NULL,NULL,NULL,'A'),(507,100,0,0,0,50700,NULL,NULL,NULL,NULL,NULL,'A'),(508,100,0,0,0,50800,NULL,NULL,NULL,NULL,NULL,'A'),(509,100,0,0,0,50900,NULL,NULL,NULL,NULL,NULL,'A'),(600,100,0,0,0,60000,NULL,NULL,NULL,NULL,NULL,'A'),(601,100,0,0,0,60100,NULL,NULL,NULL,NULL,NULL,'A'),(602,100,0,0,0,60200,NULL,NULL,NULL,NULL,NULL,'A'),(603,100,0,0,0,60300,NULL,NULL,NULL,NULL,NULL,'A'),(604,100,0,0,0,60400,NULL,NULL,NULL,NULL,NULL,'A'),(605,100,1,0,0,800000,NULL,NULL,NULL,NULL,NULL,'A');
/*!40000 ALTER TABLE `bugs_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `bugs_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_field_value` (
  `bug_fv_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `value_id` int(11) NOT NULL DEFAULT 0,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL DEFAULT 0,
  `status` char(1) NOT NULL DEFAULT 'A',
  `email_ad` text DEFAULT NULL,
  `send_all_flag` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`bug_fv_id`),
  KEY `idx_bug_fv_field_id` (`bug_fv_id`),
  KEY `idx_bug_fv_group_id` (`group_id`),
  KEY `idx_bug_fv_value_id` (`value_id`),
  KEY `idx_bug_fv_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=8010 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bugs_field_value` WRITE;
/*!40000 ALTER TABLE `bugs_field_value` DISABLE KEYS */;
INSERT INTO `bugs_field_value` VALUES (101,101,100,1,'Open','The bug has been submitted',20,'P',NULL,1),(102,101,100,3,'Closed','The bug is no longer active. See the Resolution field for details on how it was resolved.',400,'P',NULL,1),(131,102,100,1,'1 - Wish','Issue which is mainly a matter of taste',10,'A',NULL,1),(132,102,100,2,'1,5','',20,'H',NULL,1),(133,102,100,3,'2 - Minor','Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle',30,'A',NULL,1),(134,102,100,4,'2,5','',40,'H',NULL,1),(135,102,100,5,'3 - Normal','',50,'A',NULL,1),(136,102,100,6,'3,5','',60,'H',NULL,1),(137,102,100,7,'4 - Important','Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone',70,'A',NULL,1),(138,102,100,8,'5 - Blocker','Issue which makes the object in question unusable or mostly so, or causes data loss',80,'A',NULL,1),(139,102,100,9,'6 - Security','Issue which introduces a security breach',90,'A',NULL,1),(150,103,100,100,'None','',10,'P',NULL,1),(160,107,100,100,'None','',10,'P',NULL,1),(177,108,100,7,'Duplicate','This item is already covered by another item',120,'A',NULL,1),(176,108,100,6,'Works For Me','No problem found for the group team',60,'A',NULL,1),(174,108,100,4,'Postponed','The issue will be handled later',80,'A',NULL,1),(173,108,100,8,'Need Info','More information is need to be able to handle this item',90,'A',NULL,1),(178,108,100,3,'Wont Fix','The issue won\'t be fixed (see comments)',40,'A',NULL,1),(172,108,100,2,'Invalid','This item is not valid for some reason (see comments)',130,'A',NULL,1),(200,200,100,100,'None','',10,'P',NULL,1),(210,201,100,100,'None','',10,'P',NULL,1),(220,202,100,100,'None','',10,'P',NULL,1),(221,202,100,110,'Every Time','',20,'P',NULL,1),(222,202,100,120,'Intermittent','',30,'P',NULL,1),(223,202,100,130,'Once','',40,'P',NULL,1),(240,203,100,100,'None','',10,'P',NULL,1),(241,203,100,110,'Low <30','',20,'A',NULL,1),(242,203,100,120,'Medium 30 - 200','',30,'A',NULL,1),(243,203,100,130,'High >200','',40,'A',NULL,1),(250,204,100,100,'None','',10,'P',NULL,1),(260,205,100,100,'None','',10,'P',NULL,1),(270,207,100,100,'None','',10,'P',NULL,1),(281,211,100,1,'1 - Later','',10,'A',NULL,1),(282,211,100,2,'2','',20,'A',NULL,1),(283,211,100,3,'3 - Low','',30,'A',NULL,1),(284,211,100,4,'4','',40,'A',NULL,1),(285,211,100,5,'5 - Normal','',50,'A',NULL,1),(286,211,100,6,'6','',60,'A',NULL,1),(287,211,100,7,'7 - High','',70,'A',NULL,1),(298,211,100,8,'8','',80,'A',NULL,1),(299,211,100,9,'9 - Immediate','',90,'A',NULL,1),(300,213,100,100,'None','',10,'P',NULL,1),(400,500,100,100,'None','',10,'P',NULL,1),(401,501,100,100,'None','',10,'P',NULL,1),(402,502,100,100,'None','',10,'P',NULL,1),(403,503,100,100,'None','',10,'P',NULL,1),(404,504,100,100,'None','',10,'P',NULL,1),(405,505,100,100,'None','',10,'P',NULL,1),(406,506,100,100,'None','',10,'P',NULL,1),(407,507,100,100,'None','',10,'P',NULL,1),(408,508,100,100,'None','',10,'P',NULL,1),(409,509,100,100,'None','',10,'P',NULL,1),(719,220,100,1,'0%','',1,'A',NULL,1),(720,220,100,10,'10%','',10,'A',NULL,1),(721,220,100,20,'20%','',20,'A',NULL,1),(722,220,100,30,'30%','',30,'A',NULL,1),(723,220,100,40,'40%','',40,'A',NULL,1),(724,220,100,50,'50%','',50,'A',NULL,1),(725,220,100,60,'60%','',60,'A',NULL,1),(726,220,100,70,'70%','',70,'A',NULL,1),(727,220,100,80,'80%','',80,'A',NULL,1),(728,220,100,90,'90%','',90,'A',NULL,1),(729,220,100,100,'100%','',100,'A',NULL,1),(170,108,100,100,'None','',10,'P',NULL,1),(214,201,100,140,'Mac OS','',50,'A',NULL,1),(171,108,100,1,'Fixed','The bug was resolved',30,'A',NULL,1),(211,201,100,110,'GNU/Linux','',20,'A',NULL,1),(212,201,100,120,'Microsoft Windows','',30,'A',NULL,1),(213,201,100,130,'*BSD','',40,'A',NULL,1),(885,500,100,100,'None','',10,'P',NULL,1),(886,501,100,100,'None','',10,'P',NULL,1),(887,502,100,100,'None','',10,'P',NULL,1),(888,503,100,100,'None','',10,'P',NULL,1),(889,504,100,100,'None','',10,'P',NULL,1),(890,505,100,100,'None','',10,'P',NULL,1),(891,506,100,100,'None','',10,'P',NULL,1),(892,507,100,100,'None','',10,'P',NULL,1),(893,508,100,100,'None','',10,'P',NULL,1),(894,509,100,100,'None','',10,'P',NULL,1),(111,109,100,1,'Public','This item can be seen by everybody.',10,'A',NULL,1),(112,109,100,2,'Private','This item can be seen only by group members.',20,'A',NULL,1),(179,108,100,9,'In Progress','This item is currently being worked on',70,'A',NULL,1),(180,108,100,10,'Ready For Test','This item should be tested now',65,'A',NULL,1),(181,108,100,11,'Confirmed','The issue is confirmed',82,'A',NULL,1),(4861,112,100,0,'Unlocked','Comment can be added freely',20,'P',NULL,1),(4862,112,100,1,'Locked','Discussion about this item is over',30,'P',NULL,1),(7978,213,101,100,'None','',10,'P',NULL,1),(7977,204,101,102,'0.1','',10,'A',NULL,1),(7976,204,101,101,'0.0','',10,'A',NULL,1),(7975,204,101,100,'None','',10,'P',NULL,1),(7979,213,101,101,'0.0','',10,'A',NULL,1),(7980,213,101,102,'0.1','',10,'A',NULL,1),(7981,213,101,103,'0.2','',10,'A',NULL,1),(7982,103,101,100,'None','',10,'P','',1),(7983,103,101,101,'ant','',10,'A','lice',1),(7984,103,101,102,'bee','',10,'A','mosq',1),(7985,103,101,103,'firefly','kind of fly',13,'A','',1),(7986,103,101,104,'dragon &lt;a href=&quot;fly&#039;&gt;?','test &lt;b title=&quot;specialchars',1,'A','',1),(7987,102,101,1,'1 - Wish','Issue which is mainly a matter of taste',10,'A',NULL,1),(7988,102,101,2,'1,5','',20,'H',NULL,1),(7989,102,101,3,'2 - Minor','Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle',30,'A',NULL,1),(7990,102,101,4,'2,5','',40,'H',NULL,1),(7991,102,101,5,'3 - Normal','',50,'A',NULL,1),(7992,102,101,6,'3,5','',60,'H',NULL,1),(7993,102,101,7,'4 - Important','Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone',70,'A',NULL,1),(7994,102,101,8,'5 - Blocker','Issue which makes the object in question unusable or mostly so, or causes data loss',80,'A',NULL,1),(7995,102,101,9,'6 - Security','Issue which introduces a security breach',90,'A',NULL,1),(8001,500,101,101,'sb&quot;1&lt;','test selectbox value',5,'A',NULL,1),(8000,500,101,100,'None','',10,'P',NULL,1),(7999,500,101,100,'None','',10,'P',NULL,1),(8009,205,101,102,'fine','',10,'A',NULL,1),(8008,205,101,101,'cute','',10,'A',NULL,1),(8007,205,101,100,'None','',10,'P',NULL,1);
/*!40000 ALTER TABLE `bugs_field_value` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `bugs_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_history` (
  `bug_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `field_name` text NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `mod_by` int(11) NOT NULL DEFAULT 0,
  `date` int(11) DEFAULT NULL,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`bug_history_id`),
  KEY `idx_bug_history_bug_id` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `bugs_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_report` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 100,
  `user_id` int(11) NOT NULL DEFAULT 100,
  `name` varchar(80) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scope` char(3) NOT NULL DEFAULT 'I',
  PRIMARY KEY (`report_id`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `scope_idx` (`scope`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bugs_report` WRITE;
/*!40000 ALTER TABLE `bugs_report` DISABLE KEYS */;
INSERT INTO `bugs_report` VALUES (100,100,100,'Basic','The system default bug report','S'),(101,100,100,'Advanced','The second, more complex, default bug report','S'),(102,100,100,'Votes','The default plus the votes','S'),(103,100,100,'By Date','Based on dates','S');
/*!40000 ALTER TABLE `bugs_report` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `bugs_report_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bugs_report_field` (
  `report_id` int(11) NOT NULL DEFAULT 100,
  `field_name` varchar(255) DEFAULT NULL,
  `show_on_query` int(11) DEFAULT NULL,
  `show_on_result` int(11) DEFAULT NULL,
  `place_query` int(11) DEFAULT NULL,
  `place_result` int(11) DEFAULT NULL,
  `col_width` int(11) DEFAULT NULL,
  KEY `report_id_idx` (`report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bugs_report_field` WRITE;
/*!40000 ALTER TABLE `bugs_report_field` DISABLE KEYS */;
INSERT INTO `bugs_report_field` VALUES (101,'submitted_by',1,1,30,41,NULL),(101,'date',0,1,NULL,900,NULL),(101,'summary',1,1,501,20,NULL),(101,'resolution_id',1,1,15,23,NULL),(101,'status_id',1,0,10,NULL,NULL),(101,'bug_group_id',1,0,55,NULL,NULL),(101,'category_id',1,1,50,21,NULL),(101,'severity',1,1,401,22,NULL),(101,'priority',1,0,402,NULL,NULL),(101,'assigned_to',1,1,31,40,NULL),(101,'bug_id',0,1,NULL,1,NULL),(100,'date',0,1,NULL,50,NULL),(100,'summary',0,1,NULL,20,NULL),(100,'resolution_id',1,1,15,22,NULL),(100,'status_id',1,0,10,NULL,NULL),(100,'assigned_to',1,1,20,40,NULL),(100,'bug_group_id',1,0,55,NULL,NULL),(100,'category_id',1,0,50,NULL,NULL),(100,'bug_id',0,1,NULL,1,NULL),(102,'bug_id',0,1,NULL,1,NULL),(102,'vote',0,1,NULL,2,NULL),(102,'category_id',1,0,50,NULL,NULL),(102,'bug_group_id',1,0,55,NULL,NULL),(102,'assigned_to',1,1,20,40,NULL),(102,'status_id',1,0,10,NULL,NULL),(102,'resolution_id',1,1,15,22,NULL),(102,'summary',0,1,NULL,20,NULL),(102,'date',0,1,NULL,50,NULL),(103,'bug_id',0,1,NULL,1,NULL),(103,'updated',0,1,NULL,60,NULL),(103,'category_id',1,0,50,NULL,NULL),(103,'bug_group_id',1,0,55,NULL,NULL),(103,'assigned_to',1,1,20,40,NULL),(103,'status_id',1,0,10,NULL,NULL),(103,'resolution_id',1,1,15,22,NULL),(103,'summary',0,1,NULL,20,NULL),(103,'date',1,1,1,50,NULL),(101,'details',1,0,502,NULL,NULL);
/*!40000 ALTER TABLE `bugs_report_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `cookbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook` (
  `bug_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `status_id` int(11) NOT NULL DEFAULT 100,
  `severity` int(11) NOT NULL DEFAULT 5,
  `privacy` int(2) NOT NULL DEFAULT 1,
  `discussion_lock` int(1) DEFAULT 0,
  `vote` int(11) DEFAULT 0,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `category_id` int(11) NOT NULL DEFAULT 100,
  `submitted_by` int(11) NOT NULL DEFAULT 100,
  `assigned_to` int(11) NOT NULL DEFAULT 100,
  `date` int(11) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `close_date` int(11) DEFAULT NULL,
  `bug_group_id` int(11) NOT NULL DEFAULT 100,
  `resolution_id` int(11) NOT NULL DEFAULT 9,
  `category_version_id` int(11) NOT NULL DEFAULT 100,
  `platform_version_id` int(11) NOT NULL DEFAULT 100,
  `reproducibility_id` int(11) NOT NULL DEFAULT 100,
  `size_id` int(11) NOT NULL DEFAULT 100,
  `fix_release_id` int(11) NOT NULL DEFAULT 100,
  `plan_release_id` int(11) NOT NULL DEFAULT 100,
  `hours` float(10,2) NOT NULL DEFAULT 0.00,
  `component_version` varchar(255) NOT NULL DEFAULT '',
  `fix_release` varchar(255) NOT NULL DEFAULT '',
  `plan_release` varchar(255) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT 3,
  `planned_starting_date` int(11) DEFAULT NULL,
  `planned_close_date` int(11) DEFAULT NULL,
  `percent_complete` int(11) DEFAULT 1,
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `release_id` int(11) NOT NULL DEFAULT 100,
  `release` varchar(255) NOT NULL DEFAULT '',
  `originator_name` varchar(255) NOT NULL DEFAULT '',
  `originator_email` varchar(255) NOT NULL DEFAULT '',
  `originator_phone` varchar(255) NOT NULL DEFAULT '',
  `custom_tf1` varchar(255) NOT NULL DEFAULT '',
  `custom_tf2` varchar(255) NOT NULL DEFAULT '',
  `custom_tf3` varchar(255) NOT NULL DEFAULT '',
  `custom_tf4` varchar(255) NOT NULL DEFAULT '',
  `custom_tf5` varchar(255) NOT NULL DEFAULT '',
  `custom_tf6` varchar(255) NOT NULL DEFAULT '',
  `custom_tf7` varchar(255) NOT NULL DEFAULT '',
  `custom_tf8` varchar(255) NOT NULL DEFAULT '',
  `custom_tf9` varchar(255) NOT NULL DEFAULT '',
  `custom_tf10` varchar(255) NOT NULL DEFAULT '',
  `custom_ta1` text DEFAULT NULL,
  `custom_ta2` text DEFAULT NULL,
  `custom_ta3` text DEFAULT NULL,
  `custom_ta4` text DEFAULT NULL,
  `custom_ta5` text DEFAULT NULL,
  `custom_ta6` text DEFAULT NULL,
  `custom_ta7` text DEFAULT NULL,
  `custom_ta8` text DEFAULT NULL,
  `custom_ta9` text DEFAULT NULL,
  `custom_ta10` text DEFAULT NULL,
  `custom_sb1` int(11) NOT NULL DEFAULT 100,
  `custom_sb2` int(11) NOT NULL DEFAULT 100,
  `custom_sb3` int(11) NOT NULL DEFAULT 100,
  `custom_sb4` int(11) NOT NULL DEFAULT 100,
  `custom_sb5` int(11) NOT NULL DEFAULT 100,
  `custom_sb6` int(11) NOT NULL DEFAULT 100,
  `custom_sb7` int(11) NOT NULL DEFAULT 100,
  `custom_sb8` int(11) NOT NULL DEFAULT 100,
  `custom_sb9` int(11) NOT NULL DEFAULT 100,
  `custom_sb10` int(11) NOT NULL DEFAULT 100,
  `custom_df1` int(11) NOT NULL DEFAULT 0,
  `custom_df2` int(11) NOT NULL DEFAULT 0,
  `custom_df3` int(11) NOT NULL DEFAULT 0,
  `custom_df4` int(11) NOT NULL DEFAULT 0,
  `custom_df5` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_id`),
  KEY `idx_bug_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cookbook_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_canned_responses` (
  `bug_canned_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `title` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `order_id` int(11) NOT NULL DEFAULT 50,
  PRIMARY KEY (`bug_canned_id`),
  KEY `idx_bug_canned_response_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cookbook_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_cc` (
  `bug_cc_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `added_by` int(11) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_cc_id`),
  KEY `bug_id_idx` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cookbook_context2recipe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_context2recipe` (
  `context_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `audience_anonymous` int(1) DEFAULT 0,
  `audience_loggedin` int(1) DEFAULT 0,
  `audience_members` int(1) DEFAULT 0,
  `audience_technicians` int(1) DEFAULT 0,
  `audience_managers` int(1) DEFAULT 0,
  `context_project` int(1) DEFAULT 0,
  `context_homepage` int(1) DEFAULT 0,
  `context_cookbook` int(1) DEFAULT 0,
  `context_download` int(1) DEFAULT 0,
  `context_support` int(1) DEFAULT 0,
  `context_bugs` int(1) DEFAULT 0,
  `context_task` int(1) DEFAULT 0,
  `context_patch` int(1) DEFAULT 0,
  `context_news` int(1) DEFAULT 0,
  `context_mail` int(1) DEFAULT 0,
  `context_cvs` int(1) DEFAULT 0,
  `context_arch` int(1) DEFAULT 0,
  `context_svn` int(1) DEFAULT 0,
  `context_my` int(1) DEFAULT 0,
  `context_stats` int(1) DEFAULT 0,
  `context_siteadmin` int(1) DEFAULT 0,
  `context_people` int(1) DEFAULT 0,
  `subcontext_browsing` int(1) DEFAULT 0,
  `subcontext_postitem` int(1) DEFAULT 0,
  `subcontext_edititem` int(1) DEFAULT 0,
  `subcontext_search` int(1) DEFAULT 0,
  `subcontext_configure` int(1) DEFAULT 0,
  PRIMARY KEY (`context_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cookbook_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_dependencies` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id_artifact` varchar(255) NOT NULL DEFAULT '0',
  KEY `idx_item_dependencies_bug_id` (`item_id`),
  KEY `idx_item_is_dependent_on_item_id` (`is_dependent_on_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cookbook_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_field` (
  `bug_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(255) NOT NULL DEFAULT '',
  `display_type` varchar(255) NOT NULL DEFAULT '',
  `display_size` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `scope` char(1) NOT NULL DEFAULT '',
  `required` int(11) NOT NULL DEFAULT 0,
  `empty_ok` int(11) NOT NULL DEFAULT 0,
  `keep_history` int(11) NOT NULL DEFAULT 0,
  `special` int(11) NOT NULL DEFAULT 0,
  `custom` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_field_id`),
  KEY `idx_bug_field_name` (`field_name`)
) ENGINE=MyISAM AUTO_INCREMENT=510 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cookbook_field` WRITE;
/*!40000 ALTER TABLE `cookbook_field` DISABLE KEYS */;
INSERT INTO `cookbook_field` VALUES (90,'bug_id','TF','6/10','Item ID','Unique item identifier','S',1,0,0,1,0),(91,'group_id','TF','','Group ID','Unique group identifier','S',1,0,0,1,0),(92,'submitted_by','SB','','Submitted by','User who originally submitted the item','S',1,1,0,1,0),(93,'date','DF','10/15','Submitted on','Date and time of the initial item submission','S',1,0,0,1,0),(94,'close_date','DF','10/15','Closed on','Date and time when the item status was changed to \'Closed\'','S',1,1,0,1,0),(101,'status_id','SB','','Open/Closed','Most basic status','S',1,0,1,0,0),(102,'severity','SB','','Severity','Impact of the item on the system (Critical, Major,...)','S',0,1,1,0,0),(103,'category_id','SB','','Category','Generally high level modules or functionalities of the software (e.g. User interface, Configuration Manager, etc)','P',0,1,1,0,0),(104,'assigned_to','SB','','Assigned to','Who is in charge of handling the item','S',0,1,1,0,0),(105,'summary','TF','65/120','Summary/Question','One line description of the item','S',1,0,1,1,0),(106,'details','TA','65/45','Recipe','Answer to the question, recipe','S',1,0,1,1,0),(107,'bug_group_id','SB','','Item Group','Characterizes the nature of the item (e.g. Crash Error, Documentation Typo, Installation Problem, etc','P',0,1,1,0,0),(108,'resolution_id','SB','','Status','Current status of the item: only Approved Items will be shown to end-users','S',1,1,1,0,0),(109,'privacy','SB','','Privacy','Determines whether the item can be seen by members of the group only or anybody.','S',0,1,1,0,0),(110,'vote','TF','6/10','Votes','How many votes this item received','S',0,1,0,1,0),(200,'category_version_id','SB','','Component Version','Version of the System Component (aka Item Category) impacted by the item','P',0,1,1,0,0),(201,'platform_version_id','SB','','Operating System','Operating System impacted by the issue','P',0,1,1,0,0),(205,'comment_type_id','SB','','Comment Type','Nature of the comment','P',1,1,0,1,0),(211,'priority','SB','','Importance','How important is this recipe','S',1,1,1,0,0),(212,'keywords','TF','60/120','Keywords','List of comma separated keywords associated with an item','S',0,1,1,0,0),(213,'release_id','SB','','Release','Release (global version number) impacted by the item','P',0,1,1,0,0),(215,'originator_name','TF','20/40','Originator Name','Name of the person who submitted the item (if different from the submitter field)','S',0,1,1,0,0),(216,'originator_email','TF','20/40','Originator Email','Email address of the person who submitted the item (if different from the submitter field, add address to CC list)','S',0,1,1,0,0),(217,'originator_phone','TF','10/40','Originator Phone','Phone number of the person who submitted the item','S',0,1,1,0,0),(300,'custom_tf1','TF','10/15','Custom Text Field #1','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(301,'custom_tf2','TF','10/15','Custom Text Field #2','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(302,'custom_tf3','TF','10/15','Custom Text Field #3','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(303,'custom_tf4','TF','10/15','Custom Text Field #4','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(304,'custom_tf5','TF','10/15','Custom Text Field #5','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(305,'custom_tf6','TF','10/15','Custom Text Field #6','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(306,'custom_tf7','TF','10/15','Custom Text Field #7','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(307,'custom_tf8','TF','10/15','Custom Text Field #8','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(308,'custom_tf9','TF','10/15','Custom Text Field #9','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(309,'custom_tf10','TF','10/15','Custom Text Field #10','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(400,'custom_ta1','TA','60/3','Custom Text Area #1','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(401,'custom_ta2','TA','60/3','Custom Text Area #2','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(402,'custom_ta3','TA','60/3','Custom Text Area #3','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(403,'custom_ta4','TA','60/3','Custom Text Area #4','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(404,'custom_ta5','TA','60/3','Custom Text Area #5','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(405,'custom_ta6','TA','60/3','Custom Text Area #6','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(406,'custom_ta7','TA','60/3','Custom Text Area #7','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(407,'custom_ta8','TA','60/3','Custom Text Area #8','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(408,'custom_ta9','TA','60/3','Custom Text Area #9','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(409,'custom_ta10','TA','60/3','Custom Text Area #10','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(500,'custom_sb1','SB','','Custom Select Box #1','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(501,'custom_sb2','SB','','Custom Select Box #2','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(502,'custom_sb3','SB','','Custom Select Box #3','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(503,'custom_sb4','SB','','Custom Select Box #4','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(504,'custom_sb5','SB','','Custom Select Box #5','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(505,'custom_sb6','SB','','Custom Select Box #6','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(506,'custom_sb7','SB','','Custom Select Box #7','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(507,'custom_sb8','SB','','Custom Select Box #8','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(508,'custom_sb9','SB','','Custom Select Box #9','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(509,'custom_sb10','SB','','Custom Select Box #10','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0);
/*!40000 ALTER TABLE `cookbook_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `cookbook_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_field_usage` (
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `use_it` int(11) NOT NULL DEFAULT 0,
  `show_on_add` int(11) NOT NULL DEFAULT 0,
  `show_on_add_members` int(11) NOT NULL DEFAULT 0,
  `place` int(11) NOT NULL DEFAULT 0,
  `custom_label` varchar(255) DEFAULT NULL,
  `custom_description` varchar(255) DEFAULT NULL,
  `custom_display_size` varchar(255) DEFAULT NULL,
  `custom_empty_ok` int(11) DEFAULT NULL,
  `custom_keep_history` int(11) DEFAULT NULL,
  `transition_default_auth` char(1) NOT NULL DEFAULT 'A',
  KEY `idx_bug_fu_field_id` (`bug_field_id`),
  KEY `idx_bug_fu_group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cookbook_field_usage` WRITE;
/*!40000 ALTER TABLE `cookbook_field_usage` DISABLE KEYS */;
INSERT INTO `cookbook_field_usage` VALUES (90,100,1,0,0,10,NULL,NULL,NULL,NULL,NULL,'A'),(91,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL,'A'),(92,100,1,0,0,20,NULL,NULL,NULL,NULL,NULL,'A'),(93,100,1,0,0,40,NULL,NULL,NULL,NULL,NULL,'A'),(94,100,1,0,0,50,NULL,NULL,NULL,NULL,NULL,'A'),(101,100,1,0,0,600,NULL,NULL,NULL,NULL,NULL,'A'),(102,100,0,0,0,200,NULL,NULL,NULL,NULL,NULL,'A'),(103,100,1,1,1,100,NULL,NULL,NULL,NULL,NULL,'A'),(104,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,'A'),(105,100,1,1,1,700000,NULL,NULL,NULL,NULL,NULL,'A'),(106,100,1,1,1,700001,NULL,NULL,NULL,NULL,NULL,'A'),(107,100,0,1,1,300,NULL,NULL,NULL,NULL,NULL,'A'),(108,100,1,0,1,400,NULL,NULL,NULL,NULL,NULL,'A'),(109,100,1,0,1,402,NULL,NULL,NULL,NULL,NULL,'A'),(110,100,0,0,0,405,NULL,NULL,NULL,NULL,NULL,'A'),(112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(200,100,0,0,0,1000,NULL,NULL,NULL,NULL,NULL,'A'),(201,100,0,0,0,1100,NULL,NULL,NULL,NULL,NULL,'A'),(202,100,0,0,0,1200,NULL,NULL,NULL,NULL,NULL,'A'),(203,100,0,0,0,1300,NULL,NULL,NULL,NULL,NULL,'A'),(204,100,0,0,0,1400,NULL,NULL,NULL,NULL,NULL,'A'),(205,100,1,0,0,1500,NULL,NULL,NULL,NULL,NULL,'A'),(206,100,0,0,0,1700,NULL,NULL,NULL,NULL,NULL,'A'),(207,100,0,0,0,1600,NULL,NULL,NULL,NULL,NULL,'A'),(208,100,0,0,0,1800,NULL,NULL,NULL,NULL,NULL,'A'),(209,100,0,0,0,1900,NULL,NULL,NULL,NULL,NULL,'A'),(210,100,0,0,0,2000,NULL,NULL,NULL,NULL,NULL,'A'),(211,100,1,1,1,200,NULL,NULL,NULL,NULL,NULL,'A'),(212,100,0,0,0,3000,NULL,NULL,NULL,NULL,NULL,'A'),(213,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(214,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(215,100,0,0,0,550,NULL,NULL,NULL,NULL,NULL,'A'),(216,100,0,0,0,560,NULL,NULL,NULL,NULL,NULL,'A'),(217,100,0,0,0,570,NULL,NULL,NULL,NULL,NULL,'A'),(218,100,0,0,0,55,NULL,NULL,NULL,NULL,NULL,'A'),(219,100,0,0,0,56,NULL,NULL,NULL,NULL,NULL,'A'),(220,100,0,0,0,500,NULL,NULL,NULL,NULL,NULL,'A'),(300,100,0,0,0,30000,NULL,NULL,NULL,NULL,NULL,'A'),(301,100,0,0,0,30100,NULL,NULL,NULL,NULL,NULL,'A'),(302,100,0,0,0,30200,NULL,NULL,NULL,NULL,NULL,'A'),(303,100,0,0,0,30300,NULL,NULL,NULL,NULL,NULL,'A'),(304,100,0,0,0,30400,NULL,NULL,NULL,NULL,NULL,'A'),(305,100,0,0,0,30500,NULL,NULL,NULL,NULL,NULL,'A'),(306,100,0,0,0,30600,NULL,NULL,NULL,NULL,NULL,'A'),(307,100,0,0,0,30700,NULL,NULL,NULL,NULL,NULL,'A'),(308,100,0,0,0,30800,NULL,NULL,NULL,NULL,NULL,'A'),(309,100,0,0,0,30900,NULL,NULL,NULL,NULL,NULL,'A'),(400,100,0,0,0,40000,NULL,NULL,NULL,NULL,NULL,'A'),(401,100,0,0,0,40100,NULL,NULL,NULL,NULL,NULL,'A'),(402,100,0,0,0,40200,NULL,NULL,NULL,NULL,NULL,'A'),(403,100,0,0,0,40300,NULL,NULL,NULL,NULL,NULL,'A'),(404,100,0,0,0,40400,NULL,NULL,NULL,NULL,NULL,'A'),(405,100,0,0,0,40500,NULL,NULL,NULL,NULL,NULL,'A'),(406,100,0,0,0,40600,NULL,NULL,NULL,NULL,NULL,'A'),(407,100,0,0,0,40700,NULL,NULL,NULL,NULL,NULL,'A'),(408,100,0,0,0,40800,NULL,NULL,NULL,NULL,NULL,'A'),(409,100,0,0,0,40900,NULL,NULL,NULL,NULL,NULL,'A'),(500,100,0,0,0,50000,NULL,NULL,NULL,NULL,NULL,'A'),(501,100,0,0,0,50100,NULL,NULL,NULL,NULL,NULL,'A'),(502,100,0,0,0,50200,NULL,NULL,NULL,NULL,NULL,'A'),(503,100,0,0,0,50300,NULL,NULL,NULL,NULL,NULL,'A'),(504,100,0,0,0,50400,NULL,NULL,NULL,NULL,NULL,'A'),(505,100,0,0,0,50500,NULL,NULL,NULL,NULL,NULL,'A'),(506,100,0,0,0,50600,NULL,NULL,NULL,NULL,NULL,'A'),(507,100,0,0,0,50700,NULL,NULL,NULL,NULL,NULL,'A'),(508,100,0,0,0,50800,NULL,NULL,NULL,NULL,NULL,'A'),(509,100,0,0,0,50900,NULL,NULL,NULL,NULL,NULL,'A'),(600,100,0,0,0,60000,NULL,NULL,NULL,NULL,NULL,'A'),(601,100,0,0,0,60100,NULL,NULL,NULL,NULL,NULL,'A'),(602,100,0,0,0,60200,NULL,NULL,NULL,NULL,NULL,'A'),(603,100,0,0,0,60300,NULL,NULL,NULL,NULL,NULL,'A'),(604,100,0,0,0,60400,NULL,NULL,NULL,NULL,NULL,'A');
/*!40000 ALTER TABLE `cookbook_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `cookbook_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_field_value` (
  `bug_fv_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `value_id` int(11) NOT NULL DEFAULT 0,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL DEFAULT 0,
  `status` char(1) NOT NULL DEFAULT 'A',
  `email_ad` text DEFAULT NULL,
  `send_all_flag` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_fv_id`),
  KEY `idx_bug_fv_field_id` (`bug_fv_id`),
  KEY `idx_bug_fv_group_id` (`group_id`),
  KEY `idx_bug_fv_value_id` (`value_id`),
  KEY `idx_bug_fv_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=819 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cookbook_field_value` WRITE;
/*!40000 ALTER TABLE `cookbook_field_value` DISABLE KEYS */;
INSERT INTO `cookbook_field_value` VALUES (101,101,100,1,'Open','The item has been submitted',20,'P',NULL,0),(102,101,100,3,'Closed','The item is no longer active. See the Status field for details.',400,'P',NULL,0),(131,102,100,1,'1 - Wish','Issue which is mainly a matter of taste',10,'A',NULL,0),(132,102,100,2,'2','',20,'H',NULL,0),(133,102,100,3,'3 - Minor','Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to fix',30,'A',NULL,0),(134,102,100,4,'4','',40,'H',NULL,0),(135,102,100,5,'5 - Normal','',50,'A',NULL,0),(136,102,100,6,'6','',60,'H',NULL,0),(137,102,100,7,'7 - Important','Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone',70,'A',NULL,0),(138,102,100,8,'8 - Blocker','Issue which makes the object in question unusable or mostly so, or causes data loss',80,'A',NULL,0),(139,102,100,9,'9 - Security','Issue which introduces a security breach',90,'A',NULL,0),(150,103,100,100,'None','',10,'P',NULL,0),(160,107,100,100,'None','',10,'P',NULL,0),(171,108,100,1,'Approved','The recipe was approved and is currently active',30,'A',NULL,0),(172,108,100,2,'Refused/Outdated','The recipe was refused or is updated - it is not active',130,'A',NULL,0),(179,108,100,9,'Draft','This recipe is currently being worked on - it is not active',70,'A',NULL,0),(180,108,100,10,'Ready For Review','This recipe should be checked for approval - it is not active',65,'A',NULL,0),(200,200,100,100,'None','',10,'P',NULL,0),(210,201,100,100,'None','',10,'P',NULL,0),(211,201,100,110,'GNU/Linux','',20,'A',NULL,0),(212,201,100,120,'Microsoft Windows','',30,'A',NULL,0),(213,201,100,130,'*BSD','',40,'A',NULL,0),(214,201,100,140,'Mac OS','',50,'A',NULL,0),(220,202,100,100,'None','',10,'P',NULL,0),(221,202,100,110,'Every Time','',20,'P',NULL,0),(222,202,100,120,'Intermittent','',30,'P',NULL,0),(223,202,100,130,'Once','',40,'P',NULL,0),(240,203,100,100,'None','',10,'P',NULL,0),(241,203,100,110,'Low <30','',20,'A',NULL,0),(242,203,100,120,'Medium 30 - 200','',30,'A',NULL,0),(243,203,100,130,'High >200','',40,'A',NULL,0),(250,204,100,100,'None','',10,'P',NULL,0),(260,205,100,100,'None','',10,'P',NULL,0),(270,207,100,100,'None','',10,'P',NULL,0),(281,211,100,1,'1 - Low','',10,'A',NULL,0),(282,211,100,2,'2','',20,'A',NULL,0),(283,211,100,3,'3 - Normal','',30,'A',NULL,0),(284,211,100,4,'4','',40,'A',NULL,0),(285,211,100,5,'5 - High','',50,'A',NULL,0),(300,213,100,100,'None','',10,'P',NULL,0),(400,500,100,100,'None','',10,'P',NULL,0),(401,501,100,100,'None','',10,'P',NULL,0),(402,502,100,100,'None','',10,'P',NULL,0),(403,503,100,100,'None','',10,'P',NULL,0),(404,504,100,100,'None','',10,'P',NULL,0),(405,505,100,100,'None','',10,'P',NULL,0),(406,506,100,100,'None','',10,'P',NULL,0),(407,507,100,100,'None','',10,'P',NULL,0),(408,508,100,100,'None','',10,'P',NULL,0),(409,509,100,100,'None','',10,'P',NULL,0),(719,220,100,1,'0%','',1,'A',NULL,0),(720,220,100,10,'10%','',10,'A',NULL,0),(721,220,100,20,'20%','',20,'A',NULL,0),(722,220,100,30,'30%','',30,'A',NULL,0),(723,220,100,40,'40%','',40,'A',NULL,0),(724,220,100,50,'50%','',50,'A',NULL,0),(725,220,100,60,'60%','',60,'A',NULL,0),(726,220,100,70,'70%','',70,'A',NULL,0),(727,220,100,80,'80%','',80,'A',NULL,0),(728,220,100,90,'90%','',90,'A',NULL,0),(729,220,100,100,'100%','',100,'A',NULL,0),(111,109,100,1,'Public','This item can be seen by everybody.',10,'A',NULL,1),(112,109,100,2,'Private','This item can be seen only by group members.',20,'A',NULL,1),(742,112,100,0,'Unlocked','Comment can be added freely',20,'P',NULL,1),(743,112,100,1,'Locked','Discussion about this item is over.',30,'P',NULL,1),(817,103,101,100,'None','',10,'P','',0),(818,103,101,101,'xcat','',10,'A','',0);
/*!40000 ALTER TABLE `cookbook_field_value` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `cookbook_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_history` (
  `bug_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `field_name` text NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `mod_by` int(11) NOT NULL DEFAULT 0,
  `date` int(11) DEFAULT NULL,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`bug_history_id`),
  KEY `idx_bug_history_bug_id` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cookbook_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_report` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 100,
  `user_id` int(11) NOT NULL DEFAULT 100,
  `name` varchar(80) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scope` char(3) NOT NULL DEFAULT 'I',
  PRIMARY KEY (`report_id`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `scope_idx` (`scope`)
) ENGINE=MyISAM AUTO_INCREMENT=103 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cookbook_report` WRITE;
/*!40000 ALTER TABLE `cookbook_report` DISABLE KEYS */;
INSERT INTO `cookbook_report` VALUES (100,100,100,'Basic','The system default query form','S'),(101,100,100,'Advanced','The second, more complex, default query form','S'),(102,100,100,'By Date','Based on dates','S');
/*!40000 ALTER TABLE `cookbook_report` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `cookbook_report_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cookbook_report_field` (
  `report_id` int(11) NOT NULL DEFAULT 100,
  `field_name` varchar(255) DEFAULT NULL,
  `show_on_query` int(11) DEFAULT NULL,
  `show_on_result` int(11) DEFAULT NULL,
  `place_query` int(11) DEFAULT NULL,
  `place_result` int(11) DEFAULT NULL,
  `col_width` int(11) DEFAULT NULL,
  KEY `report_id_idx` (`report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cookbook_report_field` WRITE;
/*!40000 ALTER TABLE `cookbook_report_field` DISABLE KEYS */;
INSERT INTO `cookbook_report_field` VALUES (100,'bug_id',0,1,NULL,1,NULL),(100,'category_id',1,0,50,NULL,NULL),(100,'bug_group_id',1,0,55,NULL,NULL),(100,'assigned_to',1,1,20,40,NULL),(100,'status_id',1,0,10,NULL,NULL),(100,'resolution_id',1,1,15,22,NULL),(100,'summary',0,1,NULL,20,NULL),(101,'bug_id',0,1,NULL,1,NULL),(101,'category_id',1,1,50,21,NULL),(101,'bug_group_id',1,0,55,NULL,NULL),(101,'status_id',1,0,10,NULL,NULL),(101,'resolution_id',1,1,15,23,NULL),(101,'summary',1,1,501,20,NULL),(101,'close_date',0,1,NULL,879,NULL),(101,'submitted_by',1,1,30,41,NULL),(101,'assigned_to',1,1,31,40,NULL),(101,'priority',1,0,402,NULL,NULL),(102,'bug_id',0,1,NULL,1,NULL),(102,'vote',0,1,NULL,2,NULL),(102,'category_id',1,0,50,NULL,NULL),(102,'bug_group_id',1,0,55,NULL,NULL),(102,'assigned_to',1,1,20,40,NULL),(102,'status_id',1,0,10,NULL,NULL),(102,'resolution_id',1,1,15,22,NULL),(102,'summary',0,1,NULL,20,NULL),(101,'details',1,0,502,NULL,NULL);
/*!40000 ALTER TABLE `cookbook_report_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `cvs_hooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_hooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `repo_name` enum('sources','web') DEFAULT NULL,
  `match_type` enum('ALL','DEFAULT','dir_list') DEFAULT NULL,
  `dir_list` text DEFAULT NULL,
  `hook_name` varchar(255) DEFAULT NULL,
  `needs_refresh` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_needs_refresh` (`needs_refresh`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cvs_hooks_cia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_hooks_cia` (
  `hook_id` int(11) NOT NULL DEFAULT 0,
  `project_account` text DEFAULT NULL,
  PRIMARY KEY (`hook_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cvs_hooks_log_accum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_hooks_log_accum` (
  `hook_id` int(11) NOT NULL DEFAULT 0,
  `branches` text DEFAULT NULL,
  `emails_notif` text DEFAULT NULL,
  `enable_diff` tinyint(1) NOT NULL DEFAULT 1,
  `emails_diff` text DEFAULT NULL,
  PRIMARY KEY (`hook_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form` (
  `form_id` varchar(32) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum` (
  `msg_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_forum_id` int(11) NOT NULL DEFAULT 0,
  `posted_by` int(11) NOT NULL DEFAULT 0,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  `is_followup_to` int(11) NOT NULL DEFAULT 0,
  `thread_id` int(11) NOT NULL DEFAULT 0,
  `has_followups` int(11) DEFAULT 0,
  PRIMARY KEY (`msg_id`),
  KEY `idx_forum_group_forum_id` (`group_forum_id`),
  KEY `idx_forum_is_followup_to` (`is_followup_to`),
  KEY `idx_forum_thread_id` (`thread_id`),
  KEY `idx_forum_id_date` (`group_forum_id`,`date`),
  KEY `idx_forum_id_date_followup` (`group_forum_id`,`date`,`is_followup_to`),
  KEY `idx_forum_thread_date_followup` (`thread_id`,`date`,`is_followup_to`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum_group_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_group_list` (
  `group_forum_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `forum_name` text NOT NULL,
  `is_public` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`group_forum_id`),
  KEY `idx_forum_group_list_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum_monitored_forums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_monitored_forums` (
  `monitor_id` int(11) NOT NULL AUTO_INCREMENT,
  `forum_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`monitor_id`),
  KEY `idx_forum_monitor_thread_id` (`forum_id`),
  KEY `idx_forum_monitor_combo_id` (`forum_id`,`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum_saved_place`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_saved_place` (
  `saved_place_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `forum_id` int(11) NOT NULL DEFAULT 0,
  `save_date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`saved_place_id`)
) ENGINE=MyISAM AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum_thread_id`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_thread_id` (
  `thread_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`thread_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `group_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_history` (
  `group_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `field_name` text NOT NULL,
  `old_value` text NOT NULL,
  `mod_by` int(11) NOT NULL DEFAULT 0,
  `date` int(11) DEFAULT NULL,
  PRIMARY KEY (`group_history_id`),
  KEY `idx_group_history_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `group_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_preferences` (
  `group_id` int(11) NOT NULL DEFAULT 0,
  `preference_name` varchar(255) NOT NULL DEFAULT '',
  `preference_value` mediumtext DEFAULT NULL,
  PRIMARY KEY (`group_id`,`preference_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `group_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_type` (
  `type_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `admin_email_adress` varchar(128) DEFAULT NULL,
  `base_host` varchar(128) DEFAULT NULL,
  `mailing_list_host` varchar(255) DEFAULT NULL,
  `can_use_homepage` int(1) NOT NULL DEFAULT 1,
  `can_use_download` int(1) NOT NULL DEFAULT 1,
  `can_use_cvs` int(1) NOT NULL DEFAULT 1,
  `can_use_arch` int(1) NOT NULL DEFAULT 0,
  `can_use_svn` int(1) NOT NULL DEFAULT 0,
  `can_use_git` int(1) NOT NULL DEFAULT 0,
  `can_use_hg` int(1) NOT NULL DEFAULT 0,
  `can_use_bzr` int(1) NOT NULL DEFAULT 0,
  `can_use_license` int(1) NOT NULL DEFAULT 1,
  `can_use_devel_status` int(1) NOT NULL DEFAULT 1,
  `can_use_forum` int(1) NOT NULL DEFAULT 0,
  `can_use_mailing_list` int(1) NOT NULL DEFAULT 1,
  `can_use_patch` int(1) NOT NULL DEFAULT 0,
  `can_use_task` int(1) NOT NULL DEFAULT 1,
  `can_use_news` int(1) NOT NULL DEFAULT 1,
  `can_use_support` int(1) NOT NULL DEFAULT 1,
  `can_use_bug` int(1) NOT NULL DEFAULT 1,
  `is_menu_configurable_homepage` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_download` int(1) NOT NULL DEFAULT 0,
  `is_configurable_download_dir` int(1) NOT NULL DEFAULT 0,
  `homepage_scm` varchar(25) NOT NULL DEFAULT 'cvs',
  `is_menu_configurable_forum` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_support` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_mail` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_cvs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_cvs_viewcvs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_cvs_viewcvs_homepage` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_arch` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_arch_viewcvs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_svn` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_svn_viewcvs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_git` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_git_viewcvs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_hg` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_hg_viewcvs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_bzr` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_bzr_viewcvs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_bugs` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_task` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_patch` int(1) NOT NULL DEFAULT 0,
  `is_menu_configurable_extralink_documentation` int(1) NOT NULL DEFAULT 0,
  `dir_type_cvs` varchar(255) NOT NULL DEFAULT 'basiccvs',
  `dir_type_arch` varchar(255) NOT NULL DEFAULT 'basicdirectory',
  `dir_type_svn` varchar(255) NOT NULL DEFAULT 'basicsvn',
  `dir_type_git` varchar(255) NOT NULL DEFAULT 'basicgit',
  `dir_type_hg` varchar(255) NOT NULL DEFAULT 'basichg',
  `dir_type_bzr` varchar(255) NOT NULL DEFAULT 'basicbzr',
  `dir_type_homepage` varchar(255) NOT NULL DEFAULT 'basicdirectory',
  `dir_type_download` varchar(255) NOT NULL DEFAULT 'basicdirectory',
  `dir_homepage` varchar(255) DEFAULT '/',
  `dir_cvs` varchar(255) DEFAULT '/',
  `dir_arch` varchar(255) DEFAULT '/',
  `dir_svn` varchar(255) DEFAULT '/',
  `dir_git` varchar(255) DEFAULT '/',
  `dir_hg` varchar(255) DEFAULT '/',
  `dir_bzr` varchar(255) DEFAULT '/',
  `dir_download` varchar(255) DEFAULT '/',
  `url_homepage` varchar(255) DEFAULT 'http://',
  `url_download` varchar(255) DEFAULT 'http://',
  `url_cvs_viewcvs` varchar(255) DEFAULT 'http://',
  `url_arch_viewcvs` varchar(255) DEFAULT 'http://',
  `url_svn_viewcvs` varchar(255) DEFAULT 'http://',
  `url_git_viewcvs` varchar(255) DEFAULT 'http://',
  `url_hg_viewcvs` varchar(255) DEFAULT 'http://',
  `url_bzr_viewcvs` varchar(255) DEFAULT 'http://',
  `url_cvs_viewcvs_homepage` varchar(255) DEFAULT 'http://',
  `url_mailing_list_listinfo` varchar(255) DEFAULT 'http://',
  `url_mailing_list_subscribe` varchar(255) DEFAULT 'http://',
  `url_mailing_list_unsubscribe` varchar(255) DEFAULT 'http://',
  `url_mailing_list_archives` varchar(255) DEFAULT 'http://',
  `url_mailing_list_archives_private` varchar(255) DEFAULT 'http://',
  `url_mailing_list_admin` varchar(255) DEFAULT 'http://',
  `url_extralink_documentation` varchar(255) DEFAULT '',
  `license_array` text DEFAULT NULL,
  `devel_status_array` text DEFAULT NULL,
  `mailing_list_address` varchar(255) DEFAULT '@',
  `mailing_list_virtual_host` varchar(255) DEFAULT '',
  `mailing_list_format` varchar(255) NOT NULL DEFAULT '%NAME',
  `forum_flags` int(1) DEFAULT 2,
  `forum_rflags` int(1) DEFAULT 2,
  `bugs_flags` int(1) DEFAULT 2,
  `bugs_rflags` int(1) DEFAULT 2,
  `task_flags` int(1) DEFAULT 2,
  `task_rflags` int(1) DEFAULT 5,
  `patch_flags` int(1) DEFAULT 2,
  `cookbook_flags` int(1) DEFAULT 2,
  `patch_rflags` int(1) DEFAULT 2,
  `cookbook_rflags` int(1) DEFAULT 5,
  `support_flags` int(1) DEFAULT 2,
  `support_rflags` int(1) DEFAULT 2,
  `news_flags` int(1) DEFAULT 3,
  `news_rflags` int(1) DEFAULT 2,
  PRIMARY KEY (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `group_type` WRITE;
/*!40000 ALTER TABLE `group_type` DISABLE KEYS */;
INSERT INTO `group_type` VALUES (
    1, 'GNU', 'This group is part of the GNU Project.\r\n',
    'savannah-register-bar@example.net', 'i18n.savannah.gnu.org', '', 1, 1, 1,
    1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, '', 1, 1, 1, 1, 1, 1, 1,
    1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 'savannah-gnu', 'basicdirectory',
    'basicsvn', 'basicgit', 'basichg', 'basicbzr', 'savannah-gnu',
    'basicdirectory', '/web/%PROJECT', '/sources/%PROJECT',
    '/srv/arch/%PROJECT', '/srv/svn/%PROJECT', '/srv/git/%PROJECT.git',
    '/srv/hg/%PROJECT/', '/srv/bzr/%PROJECT', '/srv/download/%PROJECT',
    'http://www.gnu.org/software/%PROJECT/', 'http://ftp.gnu.org/gnu/%PROJECT/',
    'http://cvs.savannah.gnu.org/viewvc/?root=%PROJECT',
    'http://arch.sv.gnu.org/archives/%PROJECT/',
    'http://svn.savannah.gnu.org/viewcvs/?root=%PROJECT',
    'http://git.savannah.gnu.org/cgit/%PROJECT.git',
    'http://hg.savannah.gnu.org/hgweb/%PROJECT/',
    'http://bzr.savannah.gnu.org/lh/%PROJECT',
    'http://web.cvs.savannah.gnu.org/viewvc/?root=%PROJECT',
    'http://lists.gnu.org/mailman/listinfo/%LIST', 'http://', 'http://',
    'http://lists.gnu.org/archive/html/%LIST/',
    'http://lists.gnu.org/mailman/private/%LIST/',
    'http://lists.gnu.org/mailman/admin/%LIST', '', '', '', '%LIST@gnu.org',
    'lists.gnu.org',
    '%PROJECT-%NAME,%PROJECT,bug-%PROJECT,help-%PROJECT,info-%PROJECT', 2, 2,
    2, 2, 2, 5, 2, 2, 3, 5, 2, 2, 3, 5
  ), (
    2, 'NON-GNU', 'This group is not part of the GNU Project.',
    'savannah-register-bar@example.net', 'i18n.savannah.nongnu.org', '', 1,
    1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, '', 1, 1, 1, 1,
    1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 'savannah-nongnu',
    'basicdirectory', 'basicsvn', 'basicgit', 'basichg', 'basicbzr',
    'savannah-nongnu', 'basicdirectory', '/web/%PROJECT', '/sources/%PROJECT',
    '/srv/arch/%PROJECT', '/srv/svn/%PROJECT', '/srv/git/%PROJECT.git',
    '/srv/hg/%PROJECT/', '/srv/bzr/%PROJECT', '/srv/download/%PROJECT',
    'http://www.nongnu.org/%PROJECT/',
    'http://download.savannah.nongnu.org/releases/%PROJECT/',
    'http://cvs.savannah.nongnu.org/viewvc/?root=%PROJECT',
    'http://arch.sv.nongnu.org/archives/%PROJECT/',
    'http://svn.savannah.nongnu.org/viewcvs/?root=%PROJECT',
    '//git.savannah.nongnu.org/cgit/%PROJECT.git',
    'http://hg.savannah.nongnu.org/hgweb/%PROJECT/',
    'http://bzr.savannah.gnu.org/lh/%PROJECT',
    'http://web.cvs.savannah.nongnu.org/viewvc/?root=%PROJECT',
    'http://lists.nongnu.org/mailman/listinfo/%LIST', 'http://', 'http://',
    'http://lists.nongnu.org/archive/html/%LIST/',
    'http://lists.gnu.org/mailman/private/%LIST/',
    'http://lists.nongnu.org/mailman/admin/%LIST', '', '', '',
    '%LIST@nongnu.org', 'lists.nongnu.org',
    '%PROJECT-%NAME,info-%PROJECT,bug-%PROJECT', 2, 2, 2, 2, 2, 5, 2, 2, 3,
    5, 2, 2, 3, 5
  ), (
    3, 'www.gnu.org portions',
    'This group is related to www.gnu.org webmastering. Developers are '
    'GNU webmasters. It is part of the GNU Project.',
    'savannah-register-bar@example.net', 'savannah.gnu.org', '', 1, 0, 0, 0, 0,
    0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1, 0, 0, 'cvs', 0, 0, 0, 0, 0, 0, 0, 0,
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'basiccvs', 'basicdirectory',
    'basicsvn', 'basicdirectory', 'basichg', 'basicbzr', 'savannah-gnu',
    'basicdirectory', '/web/%PROJECT', '/', '/', '/', '/', '/', '/', '/',
    'http://www.gnu.org/software/%PROJECT/', 'http://', 'http://', 'http://',
    'http://', 'http://', 'http://', 'http://',
    'http://web.cvs.savannah.gnu.org/viewvc/?root=%PROJECT',
    'http://mail.gnu.org/mailman/listinfo/%LIST', 'http://', 'http://',
    'http://mail.gnu.org/archive/html/%LIST/',
    'http://lists.gnu.org/mailman/private/%LIST/',
    'http://mail.gnu.org/mailman/admin/%LIST', '', '', '',
    '%LIST@gnu.org', 'lists.gnu.org', '%NAME', 2, 2, 2, 2, 2, 5, 2, 2, 2, 5,
    2, 2, 3, 5
  ), (
    4, 'GUG', 'This meta-group is not part of the GNU Project.',
    'savannah-bar@example.net', 'savannah.nongnu.org', '', 1, 1, 0, 0, 0, 0, 0,
    0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1, 0, 0, 'cvs', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'basiccvs', 'basicdirectory', 'basicsvn',
    'basicdirectory', 'basichg', 'basicbzr', 'savannah-nongnu',
    'basicdirectory', '/webcvs/%PROJECT', '/cvsroot/%PROJECT', '/', '/', '/',
    '/', '/', '/srv/download/%PROJECT', 'http://www.nongnu.org/%PROJECT/',
    'http://download.savannah.nongnu.org/releases/%PROJECT/', 'http://',
    'http://', 'http://', 'http://', 'http://', 'http://',
    'http://web.cvs.savannah.nongnu.org/viewvc/?root=%PROJECT',
    'http://lists.nongnu.org/mailman/listinfo/%LIST', 'http://', 'http://',
    'http://lists.nongnu.org/archive/html/%LIST/',
    'http://lists.gnu.org/mailman/private/%LIST/',
    'http://lists.nongnu.org/mailman/admin/%LIST', '', '', '',
    '%LIST@nongnu.org', 'lists.nongnu.org', '%PROJECT-%NAME', 2, 2, 2, 2, 2,
    5, 2, 2, 2, 5, 2, 2, 3, 5
  ), (
    6, 'www.gnu.org translation teams',
    'This is a www.gnu.org translation team organisational group.',
    'savannah-bar@example.net', 'savannah.gnu.org', '', 1, 1, 1, 1, 1, 1, 0,
    0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 'cvs', 0, 0, 0, 0, 0, 0, 0, 0, 0,
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'savannah-gnu', 'basicdirectory',
    'basicsvn', 'basicgit', 'basichg', 'basicbzr', 'savannah-nongnu',
    'basicdirectory', '/webcvs/%PROJECT', '/cvsroot/%PROJECT',
    '/var/lib/arch/%PROJECT', '/srv/svn/%PROJECT', '/srv/git/%PROJECT.git',
    '/', '/', '/srv/download/%PROJECT',
    'http://www.gnu.org/server/standards/translations/%PROJECT/',
    'http://download.savannah.nongnu.org/releases/%PROJECT/',
    'http://cvs.savannah.gnu.org/viewvc/?root=%PROJECT',
    'http://arch.savannah.gnu.org/archives/%PROJECT/',
    'http://svn.savannah.nongnu.org/viewcvs/?root=%PROJECT',
    'http://git.savannah.nongnu.org/gitweb/?p=%PROJECT.git', 'http://',
    'http://', 'http://web.cvs.savannah.gnu.org/viewvc/?root=%PROJECT',
    'http://lists.gnu.org/mailman/listinfo/%LIST', 'http://', 'http://',
    'http://lists.gnu.org/archive/html/%LIST/',
    'http://lists.gnu.org/mailman/private/%LIST/',
    'http://lists.gnu.org/mailman/admin/%LIST', '', '', '', '%LIST@gnu.org',
    'lists.gnu.org', '%PROJECT-%NAME', 2, 2, 2, 2, 2, 5, 2, 2, 2, 5, 2, 2, 3, 5
  ), (
    7, 'conspirations', 'certain group type', 'test@savane.test', 'i18n', '',
    1, 1, 1, 0, 0, 0, 0, 0, 1, 1, 0, 1, 0, 1, 0, 1, 1, 0, 0, 0, '', 0, 0, 0, 0,
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'basiccvs',
    'basicdirectory', 'basicsvn', 'basicgit', 'basichg', 'basicbzr',
    'basicdirectory', 'basicdirectory', '/', '/', '/', '/', '/', '/', '/', '/',
    'http://', 'http://', 'http://', 'http://', 'http://', 'http://',
    'http://', 'http://', 'http://', 'http://', 'http://', 'http://',
    'http://', 'http://', 'http://', '', NULL, NULL, '@', '', '%NAME', 2, 2,
    2, 2, 2, 5, 2, 2, 2, 5, 2, 2, 3, 2
  );
/*!40000 ALTER TABLE `group_type` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) DEFAULT NULL,
  `type` int(11) NOT NULL DEFAULT 1,
  `is_public` int(11) NOT NULL DEFAULT 0,
  `status` char(1) NOT NULL DEFAULT 'A',
  `unix_group_name` varchar(30) NOT NULL DEFAULT '',
  `gidNumber` int(11) DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `license` varchar(16) DEFAULT NULL,
  `register_purpose` text DEFAULT NULL,
  `required_software` text DEFAULT NULL,
  `other_comments` text DEFAULT NULL,
  `license_other` text DEFAULT NULL,
  `register_time` int(11) NOT NULL DEFAULT 0,
  `use_homepage` char(1) DEFAULT '0',
  `use_mail` char(1) DEFAULT '0',
  `use_patch` char(1) DEFAULT '0',
  `use_task` char(1) DEFAULT '0',
  `use_forum` char(1) DEFAULT '0',
  `use_cvs` char(1) DEFAULT '0',
  `use_arch` char(1) DEFAULT '0',
  `use_svn` char(1) DEFAULT '0',
  `use_git` char(1) DEFAULT '0',
  `use_hg` char(1) DEFAULT '0',
  `use_bzr` char(1) DEFAULT '0',
  `use_news` char(1) DEFAULT '0',
  `use_support` char(1) DEFAULT '0',
  `use_download` char(1) DEFAULT '0',
  `use_bugs` char(1) DEFAULT '0',
  `use_extralink_documentation` char(1) DEFAULT '0',
  `bugs_preamble` text DEFAULT NULL,
  `task_preamble` text DEFAULT NULL,
  `patch_preamble` text DEFAULT NULL,
  `support_preamble` text DEFAULT NULL,
  `cookbook_preamble` text DEFAULT NULL,
  `long_description` text DEFAULT NULL,
  `devel_status` varchar(5) DEFAULT '0',
  `url_homepage` varchar(255) DEFAULT NULL,
  `url_download` varchar(255) DEFAULT NULL,
  `url_forum` varchar(255) DEFAULT NULL,
  `url_support` varchar(255) DEFAULT NULL,
  `url_mail` varchar(255) DEFAULT NULL,
  `url_cvs` varchar(255) DEFAULT NULL,
  `url_cvs_viewcvs` varchar(255) DEFAULT NULL,
  `url_cvs_viewcvs_homepage` varchar(255) DEFAULT NULL,
  `url_arch` varchar(255) DEFAULT NULL,
  `url_arch_viewcvs` varchar(255) DEFAULT NULL,
  `url_svn` varchar(255) DEFAULT NULL,
  `url_svn_viewcvs` varchar(255) DEFAULT NULL,
  `url_git` varchar(255) DEFAULT NULL,
  `url_git_viewcvs` varchar(255) DEFAULT NULL,
  `url_hg` varchar(255) DEFAULT NULL,
  `url_hg_viewcvs` varchar(255) DEFAULT NULL,
  `url_bzr` varchar(255) DEFAULT NULL,
  `url_bzr_viewcvs` varchar(255) DEFAULT NULL,
  `url_bugs` varchar(255) DEFAULT NULL,
  `url_task` varchar(255) DEFAULT NULL,
  `url_patch` varchar(255) DEFAULT NULL,
  `url_extralink_documentation` varchar(255) DEFAULT NULL,
  `dir_cvs` varchar(255) DEFAULT NULL,
  `dir_arch` varchar(255) DEFAULT NULL,
  `dir_svn` varchar(255) DEFAULT NULL,
  `dir_git` varchar(255) DEFAULT NULL,
  `dir_hg` varchar(255) DEFAULT NULL,
  `dir_bzr` varchar(255) DEFAULT NULL,
  `dir_homepage` varchar(255) DEFAULT NULL,
  `dir_download` varchar(255) DEFAULT NULL,
  `new_bugs_address` text DEFAULT NULL,
  `new_patch_address` text DEFAULT NULL,
  `new_support_address` text DEFAULT NULL,
  `new_task_address` text DEFAULT NULL,
  `new_news_address` text DEFAULT NULL,
  `new_cookbook_address` text DEFAULT NULL,
  `bugs_glnotif` int(11) NOT NULL DEFAULT 1,
  `support_glnotif` int(11) NOT NULL DEFAULT 1,
  `task_glnotif` int(11) NOT NULL DEFAULT 1,
  `patch_glnotif` int(11) NOT NULL DEFAULT 1,
  `cookbook_glnotif` int(11) NOT NULL DEFAULT 1,
  `send_all_bugs` int(11) NOT NULL DEFAULT 0,
  `send_all_patch` int(11) NOT NULL DEFAULT 0,
  `send_all_support` int(11) NOT NULL DEFAULT 0,
  `send_all_task` int(11) NOT NULL DEFAULT 0,
  `send_all_cookbook` int(11) NOT NULL DEFAULT 0,
  `bugs_private_exclude_address` text DEFAULT NULL,
  `task_private_exclude_address` text DEFAULT NULL,
  `support_private_exclude_address` text DEFAULT NULL,
  `patch_private_exclude_address` text DEFAULT NULL,
  `cookbook_private_exclude_address` text DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  KEY `idx_groups_status` (`status`),
  KEY `idx_groups_public` (`is_public`),
  KEY `idx_groups_unix` (`unix_group_name`),
  KEY `idx_groups_type` (`type`),
  KEY `is_public` (`is_public`),
  KEY `idx_gidNumber` (`gidNumber`)
) ENGINE=MyISAM AUTO_INCREMENT=102 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` SET
  group_id = 100, unix_group_name = 'none', type = 2, is_public = 1,
  status = 'X', group_name = 'None', license = 'website';
INSERT INTO `groups` SET
  group_id = 101, unix_group_name = 'administration', type = 2, is_public = 1,
  status = 'A', group_name = 'Savane administration', license = 'website',
  support_preamble =
    'Before posting,\n\n'
    '* Make sure you are logged in, so as to authenticate your request, and '
      'to be able to see or comment your private requests.  You can check '
      'whether you are logged in by looking at the top of the left menu.\n'
    '* Non logged-in users can use the Originator E-mail field.\n'
    '* This is the support for Savane administration, *not* for a hosted '
      'group.\n',
  long_description =
    'This is the first active group in the database. '
    'Its purpose is managing this Savane instance.',
  register_time = UNIX_TIMESTAMP (), devel_status = '5',
  use_homepage = 1, use_mail = 1, use_task = 1, use_support = 1, use_cvs = 1,
  use_git = 1, use_news = 1, use_download = 1, url_homepage = '/';
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `groups_default_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_default_permissions` (
  `groups_default_permissions_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `admin_flags` char(16) DEFAULT NULL,
  `forum_flags` int(11) DEFAULT NULL,
  `forum_rflags` int(1) DEFAULT NULL,
  `bugs_flags` int(11) DEFAULT NULL,
  `cookbook_flags` int(1) DEFAULT NULL,
  `bugs_rflags` int(1) DEFAULT NULL,
  `cookbook_rflags` int(1) DEFAULT NULL,
  `task_flags` int(11) DEFAULT NULL,
  `task_rflags` int(1) DEFAULT NULL,
  `patch_flags` int(11) DEFAULT NULL,
  `patch_rflags` int(1) DEFAULT NULL,
  `support_flags` int(11) DEFAULT NULL,
  `support_rflags` int(1) DEFAULT NULL,
  `news_flags` int(11) DEFAULT NULL,
  `news_rflags` int(1) DEFAULT NULL,
  PRIMARY KEY (`groups_default_permissions_id`),
  KEY `bugs_flags_idx` (`bugs_flags`),
  KEY `task_flags_idx` (`task_flags`),
  KEY `patch_flags_idx` (`patch_flags`),
  KEY `support_flags_idx` (`support_flags`),
  KEY `forum_flags_idx` (`forum_flags`),
  KEY `admin_flags_idx` (`admin_flags`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `mail_group_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_group_list` (
  `group_list_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `list_name` text DEFAULT NULL,
  `is_public` int(11) NOT NULL DEFAULT 0,
  `password` varchar(16) DEFAULT NULL,
  `list_admin` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`group_list_id`),
  KEY `idx_mail_group_list_group` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `news_bytes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_bytes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `submitted_by` int(11) NOT NULL DEFAULT 0,
  `is_approved` int(11) NOT NULL DEFAULT 0,
  `date` int(11) NOT NULL DEFAULT 0,
  `date_last_edit` int(11) NOT NULL,
  `forum_id` int(11) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_news_bytes_forum` (`forum_id`),
  KEY `idx_news_bytes_group` (`group_id`),
  KEY `idx_news_bytes_approved` (`is_approved`),
  KEY `idx_news_bytes_date` (`date`),
  KEY `idx_news_bytes_date_last_edit` (`date_last_edit`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `patch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch` (
  `bug_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `status_id` int(11) NOT NULL DEFAULT 100,
  `severity` int(11) NOT NULL DEFAULT 5,
  `privacy` int(2) NOT NULL DEFAULT 1,
  `discussion_lock` int(1) DEFAULT 0,
  `vote` int(11) NOT NULL DEFAULT 0,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `category_id` int(11) NOT NULL DEFAULT 100,
  `submitted_by` int(11) NOT NULL DEFAULT 100,
  `assigned_to` int(11) NOT NULL DEFAULT 100,
  `date` int(11) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `close_date` int(11) DEFAULT NULL,
  `bug_group_id` int(11) NOT NULL DEFAULT 100,
  `resolution_id` int(11) NOT NULL DEFAULT 100,
  `category_version_id` int(11) NOT NULL DEFAULT 100,
  `platform_version_id` int(11) NOT NULL DEFAULT 100,
  `reproducibility_id` int(11) NOT NULL DEFAULT 100,
  `size_id` int(11) NOT NULL DEFAULT 100,
  `fix_release_id` int(11) NOT NULL DEFAULT 100,
  `plan_release_id` int(11) NOT NULL DEFAULT 100,
  `hours` float(10,2) NOT NULL DEFAULT 0.00,
  `component_version` varchar(255) NOT NULL DEFAULT '',
  `fix_release` varchar(255) NOT NULL DEFAULT '',
  `plan_release` varchar(255) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT 5,
  `planned_starting_date` int(11) DEFAULT NULL,
  `planned_close_date` int(11) DEFAULT NULL,
  `percent_complete` int(11) DEFAULT 1,
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `release_id` int(11) NOT NULL DEFAULT 100,
  `release` varchar(255) NOT NULL DEFAULT '',
  `originator_name` varchar(255) NOT NULL DEFAULT '',
  `originator_email` varchar(255) NOT NULL DEFAULT '',
  `originator_phone` varchar(255) NOT NULL DEFAULT '',
  `custom_tf1` varchar(255) NOT NULL DEFAULT '',
  `custom_tf2` varchar(255) NOT NULL DEFAULT '',
  `custom_tf3` varchar(255) NOT NULL DEFAULT '',
  `custom_tf4` varchar(255) NOT NULL DEFAULT '',
  `custom_tf5` varchar(255) NOT NULL DEFAULT '',
  `custom_tf6` varchar(255) NOT NULL DEFAULT '',
  `custom_tf7` varchar(255) NOT NULL DEFAULT '',
  `custom_tf8` varchar(255) NOT NULL DEFAULT '',
  `custom_tf9` varchar(255) NOT NULL DEFAULT '',
  `custom_tf10` varchar(255) NOT NULL DEFAULT '',
  `custom_ta1` text DEFAULT NULL,
  `custom_ta2` text DEFAULT NULL,
  `custom_ta3` text DEFAULT NULL,
  `custom_ta4` text DEFAULT NULL,
  `custom_ta5` text DEFAULT NULL,
  `custom_ta6` text DEFAULT NULL,
  `custom_ta7` text DEFAULT NULL,
  `custom_ta8` text DEFAULT NULL,
  `custom_ta9` text DEFAULT NULL,
  `custom_ta10` text DEFAULT NULL,
  `custom_sb1` int(11) NOT NULL DEFAULT 100,
  `custom_sb2` int(11) NOT NULL DEFAULT 100,
  `custom_sb3` int(11) NOT NULL DEFAULT 100,
  `custom_sb4` int(11) NOT NULL DEFAULT 100,
  `custom_sb5` int(11) NOT NULL DEFAULT 100,
  `custom_sb6` int(11) NOT NULL DEFAULT 100,
  `custom_sb7` int(11) NOT NULL DEFAULT 100,
  `custom_sb8` int(11) NOT NULL DEFAULT 100,
  `custom_sb9` int(11) NOT NULL DEFAULT 100,
  `custom_sb10` int(11) NOT NULL DEFAULT 100,
  `custom_df1` int(11) NOT NULL DEFAULT 0,
  `custom_df2` int(11) NOT NULL DEFAULT 0,
  `custom_df3` int(11) NOT NULL DEFAULT 0,
  `custom_df4` int(11) NOT NULL DEFAULT 0,
  `custom_df5` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_id`),
  KEY `idx_bug_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `patch_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_canned_responses` (
  `bug_canned_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `title` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `order_id` int(11) NOT NULL DEFAULT 50,
  PRIMARY KEY (`bug_canned_id`),
  KEY `idx_bug_canned_response_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `patch_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_cc` (
  `bug_cc_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `added_by` int(11) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_cc_id`),
  KEY `bug_id_idx` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `patch_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_dependencies` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id_artifact` varchar(255) NOT NULL DEFAULT '0',
  KEY `idx_item_dependencies_bug_id` (`item_id`),
  KEY `idx_item_is_dependent_on_item_id` (`is_dependent_on_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `patch_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_field` (
  `bug_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(255) NOT NULL DEFAULT '',
  `display_type` varchar(255) NOT NULL DEFAULT '',
  `display_size` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `scope` char(1) NOT NULL DEFAULT '',
  `required` int(11) NOT NULL DEFAULT 0,
  `empty_ok` int(11) NOT NULL DEFAULT 0,
  `keep_history` int(11) NOT NULL DEFAULT 0,
  `special` int(11) NOT NULL DEFAULT 0,
  `custom` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_field_id`),
  KEY `idx_bug_field_name` (`field_name`)
) ENGINE=MyISAM AUTO_INCREMENT=606 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patch_field` WRITE;
/*!40000 ALTER TABLE `patch_field` DISABLE KEYS */;
INSERT INTO `patch_field` VALUES (90,'bug_id','TF','6/10','Item ID','Unique item identifier','S',1,0,0,1,0),(91,'group_id','TF','','Group ID','Unique group identifier','S',1,0,0,1,0),(92,'submitted_by','SB','','Submitted by','User who originally submitted the item','S',1,1,0,1,0),(93,'date','DF','10/15','Submitted on','Date and time of the initial submission','S',1,0,0,1,0),(94,'close_date','DF','10/15','Closed on','Date and time when the item status was changed to \'Closed\'','S',1,1,0,1,0),(101,'status_id','SB','','Open/Closed','Most basic status of the item: is the item considered as dealt with or not.','S',1,0,1,0,0),(102,'severity','SB','','Severity','Impact of the item on the system (Critical, Major,...)','S',0,1,1,0,0),(103,'category_id','SB','','Category','Generally high level modules or functionalities of the software (e.g. User interface, Configuration Manager, etc)','P',0,1,1,0,0),(104,'assigned_to','SB','','Assigned to','Who is in charge of handling the item','S',1,1,1,0,0),(105,'summary','TF','65/120','Summary','One line description of the item','S',1,0,1,1,0),(106,'details','TA','65/20','Original Submission','Full description of the item','S',1,1,1,1,0),(107,'bug_group_id','SB','','Item Group','Characterizes the nature of the item (e.g. Crash Error, Documentation Typo, Installation Problem, etc','P',0,1,1,0,0),(108,'resolution_id','SB','','Status','Current status of the item','P',0,1,1,0,0),(200,'category_version_id','SB','','Component Version','Version of the System Component (aka Item Category) impacted by the item','P',0,1,1,0,0),(201,'platform_version_id','SB','','Operating System','Operating System impacted by the issue','P',0,1,1,0,0),(202,'reproducibility_id','SB','','Reproducibility','How easy it is to reproduce the item','S',0,1,1,0,0),(203,'size_id','SB','','Size (loc)','Estimated size of the code to be developed or reworked to handle the item','S',0,1,1,0,0),(204,'fix_release_id','SB','','Fixed Release','Release in which the item was actually handled','P',0,1,1,0,0),(205,'comment_type_id','SB','','Comment Type','Nature of the comment','P',1,1,0,1,0),(206,'hours','TF','5/5','Effort','Number of hours of work needed to handle the item','S',0,1,1,0,0),(207,'plan_release_id','SB','','Planned Release','Release in which it is planned to have the item handled','P',0,1,1,0,0),(208,'component_version','TF','10/40','Component Version','Version of the system component (or work product) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(209,'fix_release','TF','10/40','Fixed Release','Release in which the item was actually fixed (<u>in free text</u>)','S',0,1,1,0,0),(210,'plan_release','TF','10/40','Planned Release','Release in which it is planned to have the item handled (<u>in free text</u>)','S',0,1,1,0,0),(211,'priority','SB','','Priority','How quickly the item should be handled (Immediate, Normal, Low, Later,...)','S',0,1,1,0,0),(212,'keywords','TF','60/120','Keywords','List of comma separated keywords associated with an item','S',0,1,1,0,0),(213,'release_id','SB','','Release','Release (global version number) impacted by the item','P',0,1,1,0,0),(214,'release','TF','10/40','Release','Release (global version number) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(215,'originator_name','TF','20/40','Originator Name','Name of the person who submitted the item (if different from the submitter field)','S',0,1,1,0,0),(216,'originator_email','TF','20/40','Originator Email','Email address of the person who submitted the item (if different from the submitter field, add address to CC list)','S',0,1,1,0,0),(217,'originator_phone','TF','10/40','Originator Phone','Phone number of the person who submitted the item','S',0,1,1,0,0),(220,'percent_complete','SB','','Percent Complete','','S',0,1,1,0,0),(112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0),(300,'custom_tf1','TF','10/15','Custom Text Field #1','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(301,'custom_tf2','TF','10/15','Custom Text Field #2','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(302,'custom_tf3','TF','10/15','Custom Text Field #3','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(303,'custom_tf4','TF','10/15','Custom Text Field #4','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(304,'custom_tf5','TF','10/15','Custom Text Field #5','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(305,'custom_tf6','TF','10/15','Custom Text Field #6','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(306,'custom_tf7','TF','10/15','Custom Text Field #7','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(307,'custom_tf8','TF','10/15','Custom Text Field #8','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(308,'custom_tf9','TF','10/15','Custom Text Field #9','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(309,'custom_tf10','TF','10/15','Custom Text Field #10','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(400,'custom_ta1','TA','60/3','Custom Text Area #1','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(401,'custom_ta2','TA','60/3','Custom Text Area #2','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(402,'custom_ta3','TA','60/3','Custom Text Area #3','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(403,'custom_ta4','TA','60/3','Custom Text Area #4','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(404,'custom_ta5','TA','60/3','Custom Text Area #5','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(405,'custom_ta6','TA','60/3','Custom Text Area #6','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(406,'custom_ta7','TA','60/3','Custom Text Area #7','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(407,'custom_ta8','TA','60/3','Custom Text Area #8','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(408,'custom_ta9','TA','60/3','Custom Text Area #9','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(409,'custom_ta10','TA','60/3','Custom Text Area #10','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(500,'custom_sb1','SB','','Custom Select Box #1','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(501,'custom_sb2','SB','','Custom Select Box #2','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(502,'custom_sb3','SB','','Custom Select Box #3','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(503,'custom_sb4','SB','','Custom Select Box #4','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(504,'custom_sb5','SB','','Custom Select Box #5','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(505,'custom_sb6','SB','','Custom Select Box #6','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(506,'custom_sb7','SB','','Custom Select Box #7','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(507,'custom_sb8','SB','','Custom Select Box #8','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(508,'custom_sb9','SB','','Custom Select Box #9','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(509,'custom_sb10','SB','','Custom Select Box #10','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(600,'custom_df1','DF','10/10','Custom Date Field #1','Customizable Date Field','P',0,1,1,0,1),(601,'custom_df2','DF','10/10','Custom Date Field #2','Customizable Date Field','P',0,1,1,0,1),(602,'custom_df3','DF','10/10','Custom Date Field #3','Customizable Date Field','P',0,1,1,0,1),(603,'custom_df4','DF','10/10','Custom Date Field #4','Customizable Date Field','P',0,1,1,0,1),(604,'custom_df5','DF','10/10','Custom Date Field #5','Customizable Date Field','P',0,1,1,0,1),(109,'privacy','SB','','Privacy','Whether the item can be seen by members of the group only or anybody','S',0,1,1,0,0),(110,'vote','TF','6/10','Votes','How many votes this item received','S',0,1,0,1,0),(605,'updated','DF','10/15','Updated','Last time the item was updated','S',0,0,0,1,0);
/*!40000 ALTER TABLE `patch_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `patch_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_field_usage` (
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `use_it` int(11) NOT NULL DEFAULT 0,
  `show_on_add` int(11) NOT NULL DEFAULT 0,
  `show_on_add_members` int(11) NOT NULL DEFAULT 0,
  `place` int(11) NOT NULL DEFAULT 0,
  `custom_label` varchar(255) DEFAULT NULL,
  `custom_description` varchar(255) DEFAULT NULL,
  `custom_display_size` varchar(255) DEFAULT NULL,
  `custom_empty_ok` int(11) DEFAULT NULL,
  `custom_keep_history` int(11) DEFAULT NULL,
  `transition_default_auth` char(1) NOT NULL DEFAULT 'A',
  KEY `idx_bug_fu_field_id` (`bug_field_id`),
  KEY `idx_bug_fu_group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patch_field_usage` WRITE;
/*!40000 ALTER TABLE `patch_field_usage` DISABLE KEYS */;
INSERT INTO `patch_field_usage` VALUES (90,100,1,0,0,10,NULL,NULL,NULL,NULL,NULL,'A'),(91,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL,'A'),(92,100,0,0,0,20,NULL,NULL,NULL,NULL,NULL,'A'),(93,100,1,0,0,40,NULL,NULL,NULL,NULL,NULL,'A'),(94,100,1,0,0,50,NULL,NULL,NULL,NULL,NULL,'A'),(101,100,1,0,0,600,NULL,NULL,NULL,NULL,NULL,'A'),(102,100,0,0,0,200,NULL,NULL,NULL,NULL,NULL,'A'),(103,100,1,1,1,100,NULL,NULL,NULL,NULL,NULL,'A'),(104,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,'A'),(105,100,1,1,1,700000,NULL,NULL,NULL,NULL,NULL,'A'),(106,100,1,1,1,700001,NULL,NULL,NULL,NULL,NULL,'A'),(107,100,0,0,0,300,NULL,NULL,NULL,NULL,NULL,'A'),(108,100,1,0,1,400,NULL,NULL,NULL,NULL,NULL,'A'),(200,100,0,0,0,1000,NULL,NULL,NULL,NULL,NULL,'A'),(201,100,0,0,0,1100,NULL,NULL,NULL,NULL,NULL,'A'),(202,100,0,0,0,1200,NULL,NULL,NULL,NULL,NULL,'A'),(203,100,0,0,0,1300,NULL,NULL,NULL,NULL,NULL,'A'),(204,100,0,0,0,1400,NULL,NULL,NULL,NULL,NULL,'A'),(205,100,1,0,0,1500,NULL,NULL,NULL,NULL,NULL,'A'),(206,100,0,0,0,1700,NULL,NULL,NULL,NULL,NULL,'A'),(207,100,0,0,0,1600,NULL,NULL,NULL,NULL,NULL,'A'),(208,100,0,0,0,1800,NULL,NULL,NULL,NULL,NULL,'A'),(209,100,0,0,0,1900,NULL,NULL,NULL,NULL,NULL,'A'),(210,100,0,0,0,2000,NULL,NULL,NULL,NULL,NULL,'A'),(211,100,1,1,1,150,NULL,NULL,NULL,NULL,NULL,'A'),(212,100,0,0,0,3000,NULL,NULL,NULL,NULL,NULL,'A'),(213,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(214,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(215,100,0,0,0,550,NULL,NULL,NULL,NULL,NULL,'A'),(216,100,1,2,0,560,NULL,NULL,NULL,NULL,NULL,'A'),(217,100,0,0,0,570,NULL,NULL,NULL,NULL,NULL,'A'),(220,100,0,0,0,500,NULL,NULL,NULL,NULL,NULL,'A'),(221,100,0,0,0,500,NULL,NULL,NULL,NULL,NULL,'A'),(300,100,0,0,0,30000,NULL,NULL,NULL,NULL,NULL,'A'),(301,100,0,0,0,30100,NULL,NULL,NULL,NULL,NULL,'A'),(302,100,0,0,0,30200,NULL,NULL,NULL,NULL,NULL,'A'),(303,100,0,0,0,30300,NULL,NULL,NULL,NULL,NULL,'A'),(304,100,0,0,0,30400,NULL,NULL,NULL,NULL,NULL,'A'),(305,100,0,0,0,30500,NULL,NULL,NULL,NULL,NULL,'A'),(306,100,0,0,0,30600,NULL,NULL,NULL,NULL,NULL,'A'),(307,100,0,0,0,30700,NULL,NULL,NULL,NULL,NULL,'A'),(308,100,0,0,0,30800,NULL,NULL,NULL,NULL,NULL,'A'),(309,100,0,0,0,30900,NULL,NULL,NULL,NULL,NULL,'A'),(400,100,0,0,0,40000,NULL,NULL,NULL,NULL,NULL,'A'),(401,100,0,0,0,40100,NULL,NULL,NULL,NULL,NULL,'A'),(402,100,0,0,0,40200,NULL,NULL,NULL,NULL,NULL,'A'),(403,100,0,0,0,40300,NULL,NULL,NULL,NULL,NULL,'A'),(404,100,0,0,0,40400,NULL,NULL,NULL,NULL,NULL,'A'),(405,100,0,0,0,40500,NULL,NULL,NULL,NULL,NULL,'A'),(406,100,0,0,0,40600,NULL,NULL,NULL,NULL,NULL,'A'),(407,100,0,0,0,40700,NULL,NULL,NULL,NULL,NULL,'A'),(408,100,0,0,0,40800,NULL,NULL,NULL,NULL,NULL,'A'),(409,100,0,0,0,40900,NULL,NULL,NULL,NULL,NULL,'A'),(500,100,0,0,0,50000,NULL,NULL,NULL,NULL,NULL,'A'),(501,100,0,0,0,50100,NULL,NULL,NULL,NULL,NULL,'A'),(502,100,0,0,0,50200,NULL,NULL,NULL,NULL,NULL,'A'),(503,100,0,0,0,50300,NULL,NULL,NULL,NULL,NULL,'A'),(504,100,0,0,0,50400,NULL,NULL,NULL,NULL,NULL,'A'),(505,100,0,0,0,50500,NULL,NULL,NULL,NULL,NULL,'A'),(506,100,0,0,0,50600,NULL,NULL,NULL,NULL,NULL,'A'),(507,100,0,0,0,50700,NULL,NULL,NULL,NULL,NULL,'A'),(508,100,0,0,0,50800,NULL,NULL,NULL,NULL,NULL,'A'),(509,100,0,0,0,50900,NULL,NULL,NULL,NULL,NULL,'A'),(600,100,0,0,0,60000,NULL,NULL,NULL,NULL,NULL,'A'),(601,100,0,0,0,60100,NULL,NULL,NULL,NULL,NULL,'A'),(602,100,0,0,0,60200,NULL,NULL,NULL,NULL,NULL,'A'),(603,100,0,0,0,60300,NULL,NULL,NULL,NULL,NULL,'A'),(604,100,0,0,0,60400,NULL,NULL,NULL,NULL,NULL,'A'),(109,100,1,1,1,402,NULL,NULL,NULL,NULL,NULL,'A'),(110,100,1,1,1,405,NULL,NULL,NULL,NULL,NULL,'A'),(112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(103,101,1,3,1,10,NULL,NULL,NULL,NULL,1,'A'),(605,100,1,0,0,800000,NULL,NULL,NULL,NULL,NULL,'A');
/*!40000 ALTER TABLE `patch_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `patch_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_field_value` (
  `bug_fv_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `value_id` int(11) NOT NULL DEFAULT 0,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL DEFAULT 0,
  `status` char(1) NOT NULL DEFAULT 'A',
  `email_ad` text DEFAULT NULL,
  `send_all_flag` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`bug_fv_id`),
  KEY `idx_bug_fv_field_id` (`bug_fv_id`),
  KEY `idx_bug_fv_group_id` (`group_id`),
  KEY `idx_bug_fv_value_id` (`value_id`),
  KEY `idx_bug_fv_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=1201 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patch_field_value` WRITE;
/*!40000 ALTER TABLE `patch_field_value` DISABLE KEYS */;
INSERT INTO `patch_field_value` VALUES (101,101,100,1,'Open','The bug has been submitted',20,'P','',0),(102,101,100,3,'Closed','The bug is no longer active. See the Resolution field for details on how it was resolved.',400,'P','',0),(131,102,100,1,'1 - Wish','Issue which is mainly a matter of taste',10,'A','',0),(132,102,100,2,'1,5','',20,'H','',0),(133,102,100,3,'2 - Minor','Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle',30,'A','',0),(134,102,100,4,'2,5','',40,'H','',0),(135,102,100,5,'3 - Normal','',50,'A','',0),(136,102,100,6,'3,5','',60,'H','',0),(137,102,100,7,'4 - Important','Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone',70,'A','',0),(138,102,100,8,'5 - Blocker','Issue which makes the object in question unusable or mostly so, or causes data loss',80,'A','',0),(139,102,100,9,'6 - Security','Issue which introduces a security breach',90,'A','',0),(150,103,100,100,'None','',10,'P','',0),(160,107,100,100,'None','',10,'P','',0),(174,108,100,4,'Postponed','The issue will be handled later',80,'A','',0),(173,108,100,8,'Need Info','More information is need to be able to handle this item',90,'A','',0),(178,108,100,3,'Wont Do','The item will not be carried out (see comments)',40,'A','',0),(172,108,100,2,'Invalid','This item is not valid for some reason (see comments)',130,'A','',0),(170,108,100,100,'None','',10,'P','',0),(171,108,100,1,'Done','The item was succesfuly done',30,'A','',0),(200,200,100,100,'None','',10,'P','',0),(210,201,100,100,'None','',10,'P','',0),(220,202,100,100,'None','',10,'P','',0),(221,202,100,110,'Every Time','',20,'P','',0),(222,202,100,120,'Intermittent','',30,'P','',0),(223,202,100,130,'Once','',40,'P','',0),(240,203,100,100,'None','',10,'P','',0),(241,203,100,110,'Low <30','',20,'A','',0),(242,203,100,120,'Medium 30 - 200','',30,'A','',0),(243,203,100,130,'High >200','',40,'A','',0),(250,204,100,100,'None','',10,'P','',0),(260,205,100,100,'None','',10,'P','',0),(270,207,100,100,'None','',10,'P','',0),(300,213,100,100,'None','',10,'P','',0),(400,500,100,100,'None','',10,'P','',0),(401,501,100,100,'None','',10,'P','',0),(402,502,100,100,'None','',10,'P','',0),(403,503,100,100,'None','',10,'P','',0),(404,504,100,100,'None','',10,'P','',0),(405,505,100,100,'None','',10,'P','',0),(406,506,100,100,'None','',10,'P','',0),(407,507,100,100,'None','',10,'P','',0),(408,508,100,100,'None','',10,'P','',0),(409,509,100,100,'None','',10,'P','',0),(700,211,100,1,'1 - Later','',10,'A','',0),(701,211,100,2,'2','',20,'A','',0),(702,211,100,3,'3 - Low','',30,'A','',0),(703,211,100,4,'4','',40,'A','',0),(704,211,100,5,'5 - Normal','',50,'A','',0),(705,211,100,6,'6','',60,'A','',0),(706,211,100,7,'7 - High','',70,'A','',0),(707,211,100,8,'8','',80,'A','',0),(708,211,100,9,'9 - Immediate','',90,'A','',0),(719,220,100,1,'0%','',1,'A','',0),(720,220,100,10,'10%','',10,'A','',0),(721,220,100,20,'20%','',20,'A','',0),(722,220,100,30,'30%','',30,'A','',0),(723,220,100,40,'40%','',40,'A','',0),(724,220,100,50,'50%','',50,'A','',0),(725,220,100,60,'60%','',60,'A','',0),(726,220,100,70,'70%','',70,'A','',0),(727,220,100,80,'80%','',80,'A','',0),(728,220,100,90,'90%','',90,'A','',0),(729,220,100,100,'100%','',100,'A','',0),(111,109,100,1,'Public','This item can be seen by everybody.',10,'A','',1),(112,109,100,2,'Private','This item can be seen only by group members.',20,'A','',1),(176,108,100,6,'Works For Me','No problem found for the group team',60,'A','',0),(177,108,100,7,'Duplicate','This item is already covered by another item',120,'A','',0),(179,108,100,9,'In Progress','This item is currently being worked on',70,'A','',0),(180,108,100,10,'Ready For Test','This item should be tested now',65,'A','',0),(181,108,100,11,'Confirmed','The issue is confirmed',82,'H','',0),(1199,112,100,0,'Unlocked','Comment can be added freely',20,'P','',1),(1200,112,100,1,'Locked','Discussion about this item is over',30,'P','',1);
/*!40000 ALTER TABLE `patch_field_value` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `patch_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_history` (
  `bug_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `field_name` text NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `mod_by` int(11) NOT NULL DEFAULT 0,
  `date` int(11) DEFAULT NULL,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`bug_history_id`),
  KEY `idx_bug_history_bug_id` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `patch_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_report` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 100,
  `user_id` int(11) NOT NULL DEFAULT 100,
  `name` varchar(80) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scope` char(3) NOT NULL DEFAULT 'I',
  PRIMARY KEY (`report_id`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `scope_idx` (`scope`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patch_report` WRITE;
/*!40000 ALTER TABLE `patch_report` DISABLE KEYS */;
INSERT INTO `patch_report` VALUES (100,100,100,'Basic','The system default bug report','S'),(101,100,100,'Advanced','The second, more complex, default bug report','S'),(102,100,100,'Votes','The default plus the votes','S'),(103,100,100,'By Date','Based on dates','S');
/*!40000 ALTER TABLE `patch_report` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `patch_report_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_report_field` (
  `report_id` int(11) NOT NULL DEFAULT 100,
  `field_name` varchar(255) DEFAULT NULL,
  `show_on_query` int(11) DEFAULT NULL,
  `show_on_result` int(11) DEFAULT NULL,
  `place_query` int(11) DEFAULT NULL,
  `place_result` int(11) DEFAULT NULL,
  `col_width` int(11) DEFAULT NULL,
  KEY `report_id_idx` (`report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patch_report_field` WRITE;
/*!40000 ALTER TABLE `patch_report_field` DISABLE KEYS */;
INSERT INTO `patch_report_field` VALUES (101,'planned_starting_date',0,1,NULL,900,NULL),(101,'summary',1,1,501,20,NULL),(101,'resolution_id',1,1,15,23,NULL),(101,'status_id',1,0,10,NULL,NULL),(101,'bug_group_id',1,0,55,NULL,NULL),(101,'category_id',1,1,50,21,NULL),(101,'bug_id',0,1,NULL,1,NULL),(100,'planned_close_date',0,1,NULL,50,NULL),(100,'summary',0,1,NULL,20,NULL),(100,'resolution_id',1,1,15,22,NULL),(100,'status_id',1,0,10,NULL,NULL),(100,'assigned_to',1,1,20,40,NULL),(100,'bug_group_id',1,0,55,NULL,NULL),(100,'category_id',1,0,50,NULL,NULL),(100,'bug_id',0,1,NULL,1,NULL),(101,'planned_close_date',0,1,NULL,880,NULL),(101,'close_date',0,1,NULL,879,NULL),(101,'submitted_by',1,1,30,41,NULL),(101,'assigned_to',1,1,31,40,NULL),(101,'priority',1,0,402,NULL,NULL),(102,'bug_id',0,1,NULL,1,NULL),(102,'vote',0,1,NULL,2,NULL),(102,'category_id',1,0,50,NULL,NULL),(102,'bug_group_id',1,0,55,NULL,NULL),(102,'assigned_to',1,1,20,40,NULL),(102,'status_id',1,0,10,NULL,NULL),(102,'resolution_id',1,1,15,22,NULL),(102,'summary',0,1,NULL,20,NULL),(102,'date',0,1,NULL,50,NULL),(103,'bug_id',0,1,NULL,1,NULL),(103,'updated',0,1,NULL,1000,NULL),(103,'category_id',1,0,50,NULL,NULL),(103,'bug_group_id',1,0,55,NULL,NULL),(103,'assigned_to',1,1,20,40,NULL),(103,'status_id',1,0,10,NULL,NULL),(103,'resolution_id',1,1,15,22,NULL),(103,'summary',0,1,NULL,20,NULL),(103,'planned_starting_date',1,1,1,900,NULL),(103,'planned_close_date',1,1,2,880,NULL),(101,'details',1,0,502,NULL,NULL);
/*!40000 ALTER TABLE `patch_report_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `people_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_job` (
  `job_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  `status_id` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`job_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `people_job_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_job_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `people_job_category` WRITE;
/*!40000 ALTER TABLE `people_job_category` DISABLE KEYS */;
INSERT INTO `people_job_category` VALUES (1,'Developer'),(2,'Tester'),(3,'Other');
/*!40000 ALTER TABLE `people_job_category` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `people_job_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_job_inventory` (
  `job_inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL DEFAULT 0,
  `skill_id` int(11) NOT NULL DEFAULT 0,
  `skill_level_id` int(11) NOT NULL DEFAULT 0,
  `skill_year_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`job_inventory_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `people_job_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_job_status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `people_job_status` WRITE;
/*!40000 ALTER TABLE `people_job_status` DISABLE KEYS */;
INSERT INTO `people_job_status` VALUES (1,'Open'),(2,'Filled'),(3,'Deleted');
/*!40000 ALTER TABLE `people_job_status` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `people_skill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill` (
  `skill_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`skill_id`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `people_skill` WRITE;
/*!40000 ALTER TABLE `people_skill` DISABLE KEYS */;
INSERT INTO `people_skill` VALUES (101,'GNU/Linux'),(102,'C'),(103,'GNU Emacs');
/*!40000 ALTER TABLE `people_skill` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `people_skill_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill_inventory` (
  `skill_inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `skill_id` int(11) NOT NULL DEFAULT 0,
  `skill_level_id` int(11) NOT NULL DEFAULT 0,
  `skill_year_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`skill_inventory_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `people_skill_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill_level` (
  `skill_level_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`skill_level_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `people_skill_level` WRITE;
/*!40000 ALTER TABLE `people_skill_level` DISABLE KEYS */;
INSERT INTO `people_skill_level` VALUES (1,'Base Knowledge'),(2,'Good Knowledge'),(3,'Master'),(4,'Master Apprentice'),(5,'Expert');
/*!40000 ALTER TABLE `people_skill_level` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `people_skill_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill_year` (
  `skill_year_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`skill_year_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `people_skill_year` WRITE;
/*!40000 ALTER TABLE `people_skill_year` DISABLE KEYS */;
INSERT INTO `people_skill_year` VALUES (1,'< 6 Months'),(2,'6 Mo - 2 yr'),(3,'2 yr - 5 yr'),(4,'5 yr - 10 yr'),(5,'> 10 years'),(6,'10 yr - 20 yr'),(7,'20 yr - 40 yr'),(8,'40 yr - 80 yr'),(9,'> 80 years');
/*!40000 ALTER TABLE `people_skill_year` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `session_hash` char(32) NOT NULL DEFAULT '',
  `stay_in_ssl` int(1) NOT NULL DEFAULT 1,
  `ip_addr` char(39) NOT NULL DEFAULT '',
  `time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`session_hash`),
  KEY `idx_session_user_id` (`user_id`),
  KEY `time_idx` (`time`),
  KEY `idx_session_time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `support`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support` (
  `bug_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `status_id` int(11) NOT NULL DEFAULT 100,
  `severity` int(11) NOT NULL DEFAULT 5,
  `privacy` int(2) NOT NULL DEFAULT 1,
  `discussion_lock` int(1) DEFAULT 0,
  `vote` int(11) NOT NULL DEFAULT 0,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `category_id` int(11) NOT NULL DEFAULT 100,
  `submitted_by` int(11) NOT NULL DEFAULT 100,
  `assigned_to` int(11) NOT NULL DEFAULT 100,
  `date` int(11) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `close_date` int(11) DEFAULT NULL,
  `bug_group_id` int(11) NOT NULL DEFAULT 100,
  `resolution_id` int(11) NOT NULL DEFAULT 100,
  `category_version_id` int(11) NOT NULL DEFAULT 100,
  `platform_version_id` int(11) NOT NULL DEFAULT 100,
  `reproducibility_id` int(11) NOT NULL DEFAULT 100,
  `size_id` int(11) NOT NULL DEFAULT 100,
  `fix_release_id` int(11) NOT NULL DEFAULT 100,
  `plan_release_id` int(11) NOT NULL DEFAULT 100,
  `hours` float(10,2) NOT NULL DEFAULT 0.00,
  `component_version` varchar(255) NOT NULL DEFAULT '',
  `fix_release` varchar(255) NOT NULL DEFAULT '',
  `plan_release` varchar(255) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT 5,
  `planned_starting_date` int(11) DEFAULT NULL,
  `planned_close_date` int(11) DEFAULT NULL,
  `percent_complete` int(11) DEFAULT 1,
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `release_id` int(11) NOT NULL DEFAULT 100,
  `release` varchar(255) NOT NULL DEFAULT '',
  `originator_name` varchar(255) NOT NULL DEFAULT '',
  `originator_email` varchar(255) NOT NULL DEFAULT '',
  `originator_phone` varchar(255) NOT NULL DEFAULT '',
  `custom_tf1` varchar(255) NOT NULL DEFAULT '',
  `custom_tf2` varchar(255) NOT NULL DEFAULT '',
  `custom_tf3` varchar(255) NOT NULL DEFAULT '',
  `custom_tf4` varchar(255) NOT NULL DEFAULT '',
  `custom_tf5` varchar(255) NOT NULL DEFAULT '',
  `custom_tf6` varchar(255) NOT NULL DEFAULT '',
  `custom_tf7` varchar(255) NOT NULL DEFAULT '',
  `custom_tf8` varchar(255) NOT NULL DEFAULT '',
  `custom_tf9` varchar(255) NOT NULL DEFAULT '',
  `custom_tf10` varchar(255) NOT NULL DEFAULT '',
  `custom_ta1` text DEFAULT NULL,
  `custom_ta2` text DEFAULT NULL,
  `custom_ta3` text DEFAULT NULL,
  `custom_ta4` text DEFAULT NULL,
  `custom_ta5` text DEFAULT NULL,
  `custom_ta6` text DEFAULT NULL,
  `custom_ta7` text DEFAULT NULL,
  `custom_ta8` text DEFAULT NULL,
  `custom_ta9` text DEFAULT NULL,
  `custom_ta10` text DEFAULT NULL,
  `custom_sb1` int(11) NOT NULL DEFAULT 100,
  `custom_sb2` int(11) NOT NULL DEFAULT 100,
  `custom_sb3` int(11) NOT NULL DEFAULT 100,
  `custom_sb4` int(11) NOT NULL DEFAULT 100,
  `custom_sb5` int(11) NOT NULL DEFAULT 100,
  `custom_sb6` int(11) NOT NULL DEFAULT 100,
  `custom_sb7` int(11) NOT NULL DEFAULT 100,
  `custom_sb8` int(11) NOT NULL DEFAULT 100,
  `custom_sb9` int(11) NOT NULL DEFAULT 100,
  `custom_sb10` int(11) NOT NULL DEFAULT 100,
  `custom_df1` int(11) NOT NULL DEFAULT 0,
  `custom_df2` int(11) NOT NULL DEFAULT 0,
  `custom_df3` int(11) NOT NULL DEFAULT 0,
  `custom_df4` int(11) NOT NULL DEFAULT 0,
  `custom_df5` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_id`),
  KEY `idx_bug_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `support_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_canned_responses` (
  `bug_canned_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `title` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `order_id` int(11) NOT NULL DEFAULT 50,
  PRIMARY KEY (`bug_canned_id`),
  KEY `idx_bug_canned_response_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `support_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_cc` (
  `bug_cc_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `added_by` int(11) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_cc_id`),
  KEY `bug_id_idx` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `support_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_dependencies` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id_artifact` varchar(255) NOT NULL DEFAULT '0',
  KEY `idx_item_dependencies_bug_id` (`item_id`),
  KEY `idx_item_is_dependent_on_item_id` (`is_dependent_on_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `support_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_field` (
  `bug_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(255) NOT NULL DEFAULT '',
  `display_type` varchar(255) NOT NULL DEFAULT '',
  `display_size` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `scope` char(1) NOT NULL DEFAULT '',
  `required` int(11) NOT NULL DEFAULT 0,
  `empty_ok` int(11) NOT NULL DEFAULT 0,
  `keep_history` int(11) NOT NULL DEFAULT 0,
  `special` int(11) NOT NULL DEFAULT 0,
  `custom` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_field_id`),
  KEY `idx_bug_field_name` (`field_name`)
) ENGINE=MyISAM AUTO_INCREMENT=606 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `support_field` WRITE;
/*!40000 ALTER TABLE `support_field` DISABLE KEYS */;
INSERT INTO `support_field` VALUES (90,'bug_id','TF','6/10','Item ID','Unique item identifier','S',1,0,0,1,0),(91,'group_id','TF','','Group ID','Unique group identifier','S',1,0,0,1,0),(92,'submitted_by','SB','','Submitted by','User who originally submitted the item','S',1,1,0,1,0),(93,'date','DF','10/15','Submitted on','Date and time of the initial submission','S',1,0,0,1,0),(94,'close_date','DF','10/15','Closed on','Date and time when the item status was changed to \'Closed\'','S',1,1,0,1,0),(101,'status_id','SB','','Open/Closed','Most basic status of the item: is the item considered as dealt with or not.','S',1,0,1,0,0),(102,'severity','SB','','Severity','Impact of the item on the system (Critical, Major,...)','S',0,1,1,0,0),(103,'category_id','SB','','Category','Generally high level modules or functionalities of the software (e.g. User interface, Configuration Manager, etc)','P',0,1,1,0,0),(104,'assigned_to','SB','','Assigned to','Who is in charge of handling the item','S',1,1,1,0,0),(105,'summary','TF','65/120','Summary','One line description of the item','S',1,0,1,1,0),(106,'details','TA','65/20','Original Submission','Full description of the item','S',1,1,1,1,0),(107,'bug_group_id','SB','','Item Group','Characterizes the nature of the item (e.g. Crash Error, Documentation Typo, Installation Problem, etc','P',0,1,1,0,0),(108,'resolution_id','SB','','Status','Current status of the item','P',0,1,1,0,0),(200,'category_version_id','SB','','Component Version','Version of the System Component impacted by the item','P',0,1,1,0,0),(201,'platform_version_id','SB','','Operating System','Operating System impacted by the issue','P',0,1,1,0,0),(202,'reproducibility_id','SB','','Reproducibility','How easy it is to reproduce the item','S',0,1,1,0,0),(203,'size_id','SB','','Size (loc)','Estimated size of the code to be developed or reworked to handle the item','S',0,1,1,0,0),(204,'fix_release_id','SB','','Fixed Release','Release in which the item was actually implemented','P',0,1,1,0,0),(205,'comment_type_id','SB','','Comment Type','Specify the nature of the follow up comment attached to this item)','P',1,1,0,1,0),(206,'hours','TF','5/5','Effort','Number of hours of work needed to handle the item','S',0,1,1,0,0),(207,'plan_release_id','SB','','Planned Release','Release in which it is planned to have the item implemented','P',0,1,1,0,0),(208,'component_version','TF','10/40','Component Version','Version of the system component (or work product) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(209,'fix_release','TF','10/40','Fixed Release','Release in which the item was actually implemented (<u>in free text</u>)','S',0,1,1,0,0),(210,'plan_release','TF','10/40','Planned Release','Release in which it is planned to have the item implemented (<u>in free text</u>)','S',0,1,1,0,0),(211,'priority','SB','','Priority','How quickly the item should be implemented (Immediate, Normal, Low, Later,...)','S',0,1,1,0,0),(212,'keywords','TF','60/120','Keywords','List of comma separated keywords associated with an item','S',0,1,1,0,0),(213,'release_id','SB','','Release','Release (global version number) impacted by the item','P',0,1,1,0,0),(214,'release','TF','10/40','Release','Release (global version number) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(215,'originator_name','TF','20/40','Originator Name','Name of the person who submitted the item (if different from the submitter field)','S',0,1,1,0,0),(216,'originator_email','TF','20/40','Originator Email','Email address of the person who submitted the item (if different from the submitter field, add address to CC list)','S',0,1,1,0,0),(217,'originator_phone','TF','10/40','Originator Phone','Phone number of the person who submitted the item','S',0,1,1,0,0),(220,'percent_complete','SB','','Percent Complete','','S',0,1,1,0,0),(300,'custom_tf1','TF','10/15','Custom Text Field #1','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(301,'custom_tf2','TF','10/15','Custom Text Field #2','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(302,'custom_tf3','TF','10/15','Custom Text Field #3','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(303,'custom_tf4','TF','10/15','Custom Text Field #4','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(304,'custom_tf5','TF','10/15','Custom Text Field #5','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(305,'custom_tf6','TF','10/15','Custom Text Field #6','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(306,'custom_tf7','TF','10/15','Custom Text Field #7','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(307,'custom_tf8','TF','10/15','Custom Text Field #8','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(308,'custom_tf9','TF','10/15','Custom Text Field #9','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(309,'custom_tf10','TF','10/15','Custom Text Field #10','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(400,'custom_ta1','TA','60/3','Custom Text Area #1','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(401,'custom_ta2','TA','60/3','Custom Text Area #2','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(402,'custom_ta3','TA','60/3','Custom Text Area #3','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(403,'custom_ta4','TA','60/3','Custom Text Area #4','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(404,'custom_ta5','TA','60/3','Custom Text Area #5','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(405,'custom_ta6','TA','60/3','Custom Text Area #6','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(406,'custom_ta7','TA','60/3','Custom Text Area #7','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(407,'custom_ta8','TA','60/3','Custom Text Area #8','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(408,'custom_ta9','TA','60/3','Custom Text Area #9','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(409,'custom_ta10','TA','60/3','Custom Text Area #10','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(500,'custom_sb1','SB','','Custom Select Box #1','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(501,'custom_sb2','SB','','Custom Select Box #2','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(502,'custom_sb3','SB','','Custom Select Box #3','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(503,'custom_sb4','SB','','Custom Select Box #4','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(504,'custom_sb5','SB','','Custom Select Box #5','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(505,'custom_sb6','SB','','Custom Select Box #6','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(506,'custom_sb7','SB','','Custom Select Box #7','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(507,'custom_sb8','SB','','Custom Select Box #8','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(508,'custom_sb9','SB','','Custom Select Box #9','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(509,'custom_sb10','SB','','Custom Select Box #10','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(600,'custom_df1','DF','10/10','Custom Date Field #1','Customizable Date Field','P',0,1,1,0,1),(601,'custom_df2','DF','10/10','Custom Date Field #2','Customizable Date Field','P',0,1,1,0,1),(602,'custom_df3','DF','10/10','Custom Date Field #3','Customizable Date Field','P',0,1,1,0,1),(603,'custom_df4','DF','10/10','Custom Date Field #4','Customizable Date Field','P',0,1,1,0,1),(604,'custom_df5','DF','10/10','Custom Date Field #5','Customizable Date Field','P',0,1,1,0,1),(109,'privacy','SB','','Privacy','Determines whether the item can be seen by members of the group only or anybody.','S',0,1,1,0,0),(110,'vote','TF','6/10','Votes','How many votes this item received','S',0,1,0,1,0),(112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0),(605,'updated','DF','10/15','Updated','Last time the item was updated','S',0,0,0,1,0);
/*!40000 ALTER TABLE `support_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `support_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_field_usage` (
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `use_it` int(11) NOT NULL DEFAULT 0,
  `show_on_add` int(11) NOT NULL DEFAULT 0,
  `show_on_add_members` int(11) NOT NULL DEFAULT 0,
  `place` int(11) NOT NULL DEFAULT 0,
  `custom_label` varchar(255) DEFAULT NULL,
  `custom_description` varchar(255) DEFAULT NULL,
  `custom_display_size` varchar(255) DEFAULT NULL,
  `custom_empty_ok` int(11) DEFAULT NULL,
  `custom_keep_history` int(11) DEFAULT NULL,
  `transition_default_auth` char(1) NOT NULL DEFAULT 'A',
  KEY `idx_bug_fu_field_id` (`bug_field_id`),
  KEY `idx_bug_fu_group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `support_field_usage` WRITE;
/*!40000 ALTER TABLE `support_field_usage` DISABLE KEYS */;
INSERT INTO `support_field_usage` VALUES (90,100,1,0,0,10,NULL,NULL,NULL,NULL,NULL,'A'),(91,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL,'A'),(92,100,0,0,0,20,NULL,NULL,NULL,NULL,NULL,'A'),(93,100,1,0,0,40,NULL,NULL,NULL,NULL,NULL,'A'),(94,100,1,0,0,50,NULL,NULL,NULL,NULL,NULL,'A'),(101,100,1,0,0,600,NULL,NULL,NULL,NULL,NULL,'A'),(102,100,1,1,1,200,NULL,NULL,NULL,NULL,NULL,'A'),(103,100,1,1,1,100,NULL,NULL,NULL,NULL,NULL,'A'),(104,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,'A'),(105,100,1,1,1,700000,NULL,NULL,NULL,NULL,NULL,'A'),(106,100,1,1,1,700001,NULL,NULL,NULL,NULL,NULL,'A'),(107,100,0,0,0,300,NULL,NULL,NULL,NULL,NULL,'A'),(108,100,1,0,1,400,NULL,NULL,NULL,NULL,NULL,'A'),(200,100,0,0,0,1000,NULL,NULL,NULL,NULL,NULL,'A'),(201,100,1,1,1,1100,NULL,NULL,NULL,NULL,NULL,'A'),(202,100,0,0,0,1200,NULL,NULL,NULL,NULL,NULL,'A'),(203,100,0,0,0,1300,NULL,NULL,NULL,NULL,NULL,'A'),(204,100,0,0,0,1400,NULL,NULL,NULL,NULL,NULL,'A'),(205,100,1,0,0,1500,NULL,NULL,NULL,NULL,NULL,'A'),(206,100,0,0,0,1700,NULL,NULL,NULL,NULL,NULL,'A'),(207,100,0,0,0,1600,NULL,NULL,NULL,NULL,NULL,'A'),(208,100,0,0,0,1800,NULL,NULL,NULL,NULL,NULL,'A'),(209,100,0,0,0,1900,NULL,NULL,NULL,NULL,NULL,'A'),(210,100,0,0,0,2000,NULL,NULL,NULL,NULL,NULL,'A'),(211,100,1,0,0,150,NULL,NULL,NULL,NULL,NULL,'A'),(212,100,0,0,0,3000,NULL,NULL,NULL,NULL,NULL,'A'),(213,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(214,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(215,100,0,0,0,550,NULL,NULL,NULL,NULL,NULL,'A'),(216,100,1,2,0,560,NULL,NULL,NULL,NULL,NULL,'A'),(217,100,0,0,0,570,NULL,NULL,NULL,NULL,NULL,'A'),(220,100,0,0,0,500,NULL,NULL,NULL,NULL,NULL,'A'),(300,100,0,0,0,30000,NULL,NULL,NULL,NULL,NULL,'A'),(301,100,0,0,0,30100,NULL,NULL,NULL,NULL,NULL,'A'),(302,100,0,0,0,30200,NULL,NULL,NULL,NULL,NULL,'A'),(303,100,0,0,0,30300,NULL,NULL,NULL,NULL,NULL,'A'),(304,100,0,0,0,30400,NULL,NULL,NULL,NULL,NULL,'A'),(305,100,0,0,0,30500,NULL,NULL,NULL,NULL,NULL,'A'),(306,100,0,0,0,30600,NULL,NULL,NULL,NULL,NULL,'A'),(307,100,0,0,0,30700,NULL,NULL,NULL,NULL,NULL,'A'),(308,100,0,0,0,30800,NULL,NULL,NULL,NULL,NULL,'A'),(309,100,0,0,0,30900,NULL,NULL,NULL,NULL,NULL,'A'),(400,100,0,0,0,40000,NULL,NULL,NULL,NULL,NULL,'A'),(401,100,0,0,0,40100,NULL,NULL,NULL,NULL,NULL,'A'),(402,100,0,0,0,40200,NULL,NULL,NULL,NULL,NULL,'A'),(403,100,0,0,0,40300,NULL,NULL,NULL,NULL,NULL,'A'),(404,100,0,0,0,40400,NULL,NULL,NULL,NULL,NULL,'A'),(405,100,0,0,0,40500,NULL,NULL,NULL,NULL,NULL,'A'),(406,100,0,0,0,40600,NULL,NULL,NULL,NULL,NULL,'A'),(407,100,0,0,0,40700,NULL,NULL,NULL,NULL,NULL,'A'),(408,100,0,0,0,40800,NULL,NULL,NULL,NULL,NULL,'A'),(409,100,0,0,0,40900,NULL,NULL,NULL,NULL,NULL,'A'),(500,100,0,0,0,50000,NULL,NULL,NULL,NULL,NULL,'A'),(501,100,0,0,0,50100,NULL,NULL,NULL,NULL,NULL,'A'),(502,100,0,0,0,50200,NULL,NULL,NULL,NULL,NULL,'A'),(503,100,0,0,0,50300,NULL,NULL,NULL,NULL,NULL,'A'),(504,100,0,0,0,50400,NULL,NULL,NULL,NULL,NULL,'A'),(505,100,0,0,0,50500,NULL,NULL,NULL,NULL,NULL,'A'),(506,100,0,0,0,50600,NULL,NULL,NULL,NULL,NULL,'A'),(507,100,0,0,0,50700,NULL,NULL,NULL,NULL,NULL,'A'),(508,100,0,0,0,50800,NULL,NULL,NULL,NULL,NULL,'A'),(509,100,0,0,0,50900,NULL,NULL,NULL,NULL,NULL,'A'),(600,100,0,0,0,60000,NULL,NULL,NULL,NULL,NULL,'A'),(601,100,0,0,0,60100,NULL,NULL,NULL,NULL,NULL,'A'),(602,100,0,0,0,60200,NULL,NULL,NULL,NULL,NULL,'A'),(603,100,0,0,0,60300,NULL,NULL,NULL,NULL,NULL,'A'),(604,100,0,0,0,60400,NULL,NULL,NULL,NULL,NULL,'A'),(109,100,1,1,1,402,NULL,NULL,NULL,NULL,NULL,'A'),(110,100,1,1,1,405,NULL,NULL,NULL,NULL,NULL,'A'),(112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(210,101,1,3,1,2000,NULL,NULL,'10/40',1,1,''),(605,100,1,0,0,800000,NULL,NULL,NULL,NULL,NULL,'A');
/*!40000 ALTER TABLE `support_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `support_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_field_value` (
  `bug_fv_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `value_id` int(11) NOT NULL DEFAULT 0,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL DEFAULT 0,
  `status` char(1) NOT NULL DEFAULT 'A',
  `email_ad` text NOT NULL,
  `send_all_flag` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_fv_id`),
  KEY `idx_bug_fv_field_id` (`bug_fv_id`),
  KEY `idx_bug_fv_group_id` (`group_id`),
  KEY `idx_bug_fv_value_id` (`value_id`),
  KEY `idx_bug_fv_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=1262 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `support_field_value` WRITE;
/*!40000 ALTER TABLE `support_field_value` DISABLE KEYS */;
INSERT INTO `support_field_value` VALUES (101,101,100,1,'Open','The bug has been submitted',20,'P','',0),(102,101,100,3,'Closed','The bug is no longer active. See the Resolution field for details on how it was resolved.',400,'P','',0),(131,102,100,1,'1 - Wish','Issue which is mainly a matter of taste',10,'A','',0),(132,102,100,2,'1,5','',20,'H','',0),(133,102,100,3,'2 - Minor','Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to handle',30,'A','',0),(134,102,100,4,'2,5','',40,'H','',0),(135,102,100,5,'3 - Normal','',50,'A','',0),(136,102,100,6,'3,5','',60,'H','',0),(137,102,100,7,'4 - Important','Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone',70,'A','',0),(138,102,100,8,'5 - Blocker','Issue which makes the object in question unusable or mostly so, or causes data loss',80,'A','',0),(139,102,100,9,'6 - Security','Issue which introduces a security breach',90,'A','',0),(150,103,100,100,'None','',10,'P','',0),(160,107,100,100,'None','',10,'P','',0),(176,108,100,6,'Works For Me','No problem found for the group team',60,'A','',0),(174,108,100,4,'Postponed','The issue will be handled later',80,'A','',0),(173,108,100,8,'Need Info','More information is need to be able to handle this item',90,'A','',0),(178,108,100,3,'Wont Do','The item will not be carried out (see comments)',40,'A','',0),(172,108,100,2,'Invalid','This item is not valid for some reason (see comments)',130,'A','',0),(200,200,100,100,'None','',10,'P','',0),(210,201,100,100,'None','',10,'P','',0),(220,202,100,100,'None','',10,'P','',0),(221,202,100,110,'Every Time','',20,'P','',0),(222,202,100,120,'Intermittent','',30,'P','',0),(223,202,100,130,'Once','',40,'P','',0),(240,203,100,100,'None','',10,'P','',0),(241,203,100,110,'Low <30','',20,'A','',0),(242,203,100,120,'Medium 30 - 200','',30,'A','',0),(243,203,100,130,'High >200','',40,'A','',0),(250,204,100,100,'None','',10,'P','',0),(260,205,100,100,'None','',10,'P','',0),(270,207,100,100,'None','',10,'P','',0),(300,213,100,100,'None','',10,'P','',0),(400,500,100,100,'None','',10,'P','',0),(401,501,100,100,'None','',10,'P','',0),(402,502,100,100,'None','',10,'P','',0),(403,503,100,100,'None','',10,'P','',0),(404,504,100,100,'None','',10,'P','',0),(405,505,100,100,'None','',10,'P','',0),(406,506,100,100,'None','',10,'P','',0),(407,507,100,100,'None','',10,'P','',0),(408,508,100,100,'None','',10,'P','',0),(409,509,100,100,'None','',10,'P','',0),(700,211,100,1,'1 - Later','',10,'A','',0),(701,211,100,2,'2','',20,'A','',0),(702,211,100,3,'3 - Low','',30,'A','',0),(703,211,100,4,'4','',40,'A','',0),(704,211,100,5,'5 - Normal','',50,'A','',0),(705,211,100,6,'6','',60,'A','',0),(706,211,100,7,'7 - High','',70,'A','',0),(707,211,100,8,'8','',80,'A','',0),(708,211,100,9,'9 - Immediate','',90,'A','',0),(719,220,100,1,'0%','',1,'A','',0),(720,220,100,10,'10%','',10,'A','',0),(721,220,100,20,'20%','',20,'A','',0),(722,220,100,30,'30%','',30,'A','',0),(723,220,100,40,'40%','',40,'A','',0),(724,220,100,50,'50%','',50,'A','',0),(725,220,100,60,'60%','',60,'A','',0),(726,220,100,70,'70%','',70,'A','',0),(727,220,100,80,'80%','',80,'A','',0),(728,220,100,90,'90%','',90,'A','',0),(729,220,100,100,'100%','',100,'A','',0),(111,109,100,1,'Public','This item can be seen by everybody.',10,'A','',1),(112,109,100,2,'Private','This item can be seen only by group members.',20,'A','',1),(211,201,100,110,'GNU/Linux','',20,'A','',0),(212,201,100,120,'*BSD','',40,'A','',0),(213,201,100,130,'Microsoft Windows','',50,'A','',0),(214,201,100,140,'Mac OS','',60,'A','',0),(170,108,100,100,'None','',10,'P','',0),(171,108,100,1,'Done','The item was succesfuly done',30,'A','',0),(177,108,100,7,'Duplicate','This item is already covered by another item',120,'A','',0),(179,108,100,9,'In Progress','This item is currently being worked on',70,'A','',0),(180,108,100,10,'Ready For Test','This item should be tested now',65,'A','',0),(181,108,100,11,'Confirmed','The issue is confirmed',82,'A','',0),(1260,112,100,0,'Unlocked','Comment can be added freely',20,'P','',1),(1261,112,100,1,'Locked','Discussion about this item is over',30,'P','',1);
/*!40000 ALTER TABLE `support_field_value` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `support_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_history` (
  `bug_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `field_name` text NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `mod_by` int(11) NOT NULL DEFAULT 0,
  `date` int(11) DEFAULT NULL,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`bug_history_id`),
  KEY `idx_bug_history_bug_id` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `support_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_report` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 100,
  `user_id` int(11) NOT NULL DEFAULT 100,
  `name` varchar(80) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scope` char(3) NOT NULL DEFAULT 'I',
  PRIMARY KEY (`report_id`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `scope_idx` (`scope`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `support_report` WRITE;
/*!40000 ALTER TABLE `support_report` DISABLE KEYS */;
INSERT INTO `support_report` VALUES (100,100,100,'Basic','The system default bug report','S'),(101,100,100,'Advanced','The second, more complex, default bug report','S'),(102,100,100,'Votes','The default plus the votes','S'),(103,100,100,'By Date','Based on dates','S');
/*!40000 ALTER TABLE `support_report` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `support_report_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_report_field` (
  `report_id` int(11) NOT NULL DEFAULT 100,
  `field_name` varchar(255) DEFAULT NULL,
  `show_on_query` int(11) DEFAULT NULL,
  `show_on_result` int(11) DEFAULT NULL,
  `place_query` int(11) DEFAULT NULL,
  `place_result` int(11) DEFAULT NULL,
  `col_width` int(11) DEFAULT NULL,
  KEY `report_id_idx` (`report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `support_report_field` WRITE;
/*!40000 ALTER TABLE `support_report_field` DISABLE KEYS */;
INSERT INTO `support_report_field` VALUES (101,'date',0,1,NULL,900,NULL),(101,'summary',1,1,501,20,NULL),(101,'resolution_id',1,1,15,23,NULL),(101,'status_id',1,0,10,NULL,NULL),(101,'bug_group_id',1,0,55,NULL,NULL),(101,'category_id',1,1,50,21,NULL),(101,'bug_id',0,1,NULL,1,NULL),(100,'date',0,1,NULL,50,NULL),(100,'summary',0,1,NULL,20,NULL),(100,'resolution_id',1,1,15,22,NULL),(100,'status_id',1,0,10,NULL,NULL),(100,'assigned_to',1,1,20,40,NULL),(100,'bug_group_id',1,0,55,NULL,NULL),(100,'category_id',1,0,50,NULL,NULL),(100,'bug_id',0,1,NULL,1,NULL),(101,'submitted_by',1,1,30,41,NULL),(101,'assigned_to',1,1,31,40,NULL),(101,'priority',1,0,402,NULL,NULL),(101,'severity',1,1,401,22,NULL),(102,'bug_id',0,1,NULL,1,NULL),(102,'vote',0,1,NULL,2,NULL),(102,'category_id',1,0,50,NULL,NULL),(102,'bug_group_id',1,0,55,NULL,NULL),(102,'assigned_to',1,1,20,40,NULL),(102,'status_id',1,0,10,NULL,NULL),(102,'resolution_id',1,1,15,22,NULL),(102,'summary',0,1,NULL,20,NULL),(102,'date',0,1,NULL,50,NULL),(103,'bug_id',0,1,NULL,1,NULL),(103,'updated',0,1,NULL,60,NULL),(103,'category_id',1,0,50,NULL,NULL),(103,'bug_group_id',1,0,55,NULL,NULL),(103,'assigned_to',1,1,20,40,NULL),(103,'status_id',1,0,10,NULL,NULL),(103,'resolution_id',1,1,15,22,NULL),(103,'summary',0,1,NULL,20,NULL),(103,'date',1,1,1,50,NULL),(101,'details',1,0,502,NULL,NULL);
/*!40000 ALTER TABLE `support_report_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task` (
  `bug_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `status_id` int(11) NOT NULL DEFAULT 100,
  `severity` int(11) NOT NULL DEFAULT 5,
  `privacy` int(2) NOT NULL DEFAULT 1,
  `discussion_lock` int(1) DEFAULT 0,
  `vote` int(11) NOT NULL DEFAULT 0,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `category_id` int(11) NOT NULL DEFAULT 100,
  `submitted_by` int(11) NOT NULL DEFAULT 100,
  `assigned_to` int(11) NOT NULL DEFAULT 100,
  `date` int(11) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `close_date` int(11) DEFAULT NULL,
  `bug_group_id` int(11) NOT NULL DEFAULT 100,
  `resolution_id` int(11) NOT NULL DEFAULT 100,
  `category_version_id` int(11) NOT NULL DEFAULT 100,
  `platform_version_id` int(11) NOT NULL DEFAULT 100,
  `reproducibility_id` int(11) NOT NULL DEFAULT 100,
  `size_id` int(11) NOT NULL DEFAULT 100,
  `fix_release_id` int(11) NOT NULL DEFAULT 100,
  `plan_release_id` int(11) NOT NULL DEFAULT 100,
  `hours` float(10,2) NOT NULL DEFAULT 0.00,
  `component_version` varchar(255) NOT NULL DEFAULT '',
  `fix_release` varchar(255) NOT NULL DEFAULT '',
  `plan_release` varchar(255) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT 5,
  `planned_starting_date` int(11) DEFAULT NULL,
  `planned_close_date` int(11) DEFAULT NULL,
  `percent_complete` int(11) DEFAULT 1,
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `release_id` int(11) NOT NULL DEFAULT 100,
  `release` varchar(255) NOT NULL DEFAULT '',
  `originator_name` varchar(255) NOT NULL DEFAULT '',
  `originator_email` varchar(255) NOT NULL DEFAULT '',
  `originator_phone` varchar(255) NOT NULL DEFAULT '',
  `custom_tf1` varchar(255) NOT NULL DEFAULT '',
  `custom_tf2` varchar(255) NOT NULL DEFAULT '',
  `custom_tf3` varchar(255) NOT NULL DEFAULT '',
  `custom_tf4` varchar(255) NOT NULL DEFAULT '',
  `custom_tf5` varchar(255) NOT NULL DEFAULT '',
  `custom_tf6` varchar(255) NOT NULL DEFAULT '',
  `custom_tf7` varchar(255) NOT NULL DEFAULT '',
  `custom_tf8` varchar(255) NOT NULL DEFAULT '',
  `custom_tf9` varchar(255) NOT NULL DEFAULT '',
  `custom_tf10` varchar(255) NOT NULL DEFAULT '',
  `custom_ta1` text DEFAULT NULL,
  `custom_ta2` text DEFAULT NULL,
  `custom_ta3` text DEFAULT NULL,
  `custom_ta4` text DEFAULT NULL,
  `custom_ta5` text DEFAULT NULL,
  `custom_ta6` text DEFAULT NULL,
  `custom_ta7` text DEFAULT NULL,
  `custom_ta8` text DEFAULT NULL,
  `custom_ta9` text DEFAULT NULL,
  `custom_ta10` text DEFAULT NULL,
  `custom_sb1` int(11) NOT NULL DEFAULT 100,
  `custom_sb2` int(11) NOT NULL DEFAULT 100,
  `custom_sb3` int(11) NOT NULL DEFAULT 100,
  `custom_sb4` int(11) NOT NULL DEFAULT 100,
  `custom_sb5` int(11) NOT NULL DEFAULT 100,
  `custom_sb6` int(11) NOT NULL DEFAULT 100,
  `custom_sb7` int(11) NOT NULL DEFAULT 100,
  `custom_sb8` int(11) NOT NULL DEFAULT 100,
  `custom_sb9` int(11) NOT NULL DEFAULT 100,
  `custom_sb10` int(11) NOT NULL DEFAULT 100,
  `custom_df1` int(11) NOT NULL DEFAULT 0,
  `custom_df2` int(11) NOT NULL DEFAULT 0,
  `custom_df3` int(11) NOT NULL DEFAULT 0,
  `custom_df4` int(11) NOT NULL DEFAULT 0,
  `custom_df5` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_id`),
  KEY `idx_bug_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `task_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_canned_responses` (
  `bug_canned_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `title` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `order_id` int(11) NOT NULL DEFAULT 50,
  PRIMARY KEY (`bug_canned_id`),
  KEY `idx_bug_canned_response_group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `task_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_cc` (
  `bug_cc_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL DEFAULT '',
  `added_by` int(11) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_cc_id`),
  KEY `bug_id_idx` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `task_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_dependencies` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id` int(11) NOT NULL DEFAULT 0,
  `is_dependent_on_item_id_artifact` varchar(255) NOT NULL DEFAULT '0',
  KEY `idx_item_dependencies_bug_id` (`item_id`),
  KEY `idx_item_is_dependent_on_item_id` (`is_dependent_on_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `task_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_field` (
  `bug_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(255) NOT NULL DEFAULT '',
  `display_type` varchar(255) NOT NULL DEFAULT '',
  `display_size` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `scope` char(1) NOT NULL DEFAULT '',
  `required` int(11) NOT NULL DEFAULT 0,
  `empty_ok` int(11) NOT NULL DEFAULT 0,
  `keep_history` int(11) NOT NULL DEFAULT 0,
  `special` int(11) NOT NULL DEFAULT 0,
  `custom` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_field_id`),
  KEY `idx_bug_field_name` (`field_name`)
) ENGINE=MyISAM AUTO_INCREMENT=606 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_field` WRITE;
/*!40000 ALTER TABLE `task_field` DISABLE KEYS */;
INSERT INTO `task_field` VALUES (90,'bug_id','TF','6/10','Item ID','Unique item identifier','S',1,0,0,1,0),(91,'group_id','TF','','Group ID','Unique group identifier','S',1,0,0,1,0),(92,'submitted_by','SB','','Submitted by','User who originally submitted the item','S',1,1,0,1,0),(93,'date','DF','10/15','Submitted on','Date and time of the initial item submission','S',1,0,0,1,0),(94,'close_date','DF','10/15','Closed on','Date and time when the item status was changed to \'Closed\'','S',1,1,0,1,0),(101,'status_id','SB','','Open/Closed','Most basic status of the item: is the item considered as dealt with or not.','S',1,0,1,0,0),(102,'severity','SB','','Severity','Impact of the item on the system (Critical, Major,...)','S',0,1,1,0,0),(103,'category_id','SB','','Category','Generally high level modules or functionalities of the software (e.g. User interface, Configuration Manager, etc)','P',0,1,1,0,0),(104,'assigned_to','SB','','Assigned to','Who is in charge of handling the item','S',1,1,1,0,0),(105,'summary','TF','65/120','Summary','One line description of the item','S',1,0,1,1,0),(106,'details','TA','65/20','Original Submission','Full description of the item','S',1,1,1,1,0),(107,'bug_group_id','SB','','Item Group','Characterizes the nature of the item (e.g. Crash Error, Documentation Typo, Installation Problem, etc','P',0,1,1,0,0),(108,'resolution_id','SB','','Status','Current status of the item','P',1,1,1,0,0),(200,'category_version_id','SB','','Component Version','Version of the System Component (aka Item Category) impacted by the item','P',0,1,1,0,0),(201,'platform_version_id','SB','','Operating System','Operating System impacted by the issue','P',0,1,1,0,0),(202,'reproducibility_id','SB','','Reproducibility','How easy it is to reproduce the item','S',0,1,1,0,0),(203,'size_id','SB','','Size (loc)','Estimated size of the code to be developed or reworked to handle the item','S',0,1,1,0,0),(204,'fix_release_id','SB','','Fixed Release','Release in which the item was actually handled','P',0,1,1,0,0),(205,'comment_type_id','SB','','Comment Type','Nature of the comment','P',1,1,0,1,0),(206,'hours','TF','5/5','Effort','Number of hours of work need the handle the item','S',0,1,1,0,0),(207,'plan_release_id','SB','','Planned Release','Release in which it is planned to have the item implemented','P',0,1,1,0,0),(208,'component_version','TF','10/40','Component Version','Version of the system component (or work product) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(209,'fix_release','TF','10/40','Fixed Release','Release in which the item was actually implemented (<u>in free text</u>)','S',0,1,1,0,0),(210,'plan_release','TF','10/40','Planned Release','Release in which you initially planned the item to be handled (<u>in free text</u>)','S',0,1,1,0,0),(211,'priority','SB','','Priority','How quickly the item must be completed (Immediate, Normal, Low, Later,...)','S',1,0,1,0,0),(212,'keywords','TF','60/120','Keywords','List of comma separated keywords associated with an item','S',0,1,1,0,0),(213,'release_id','SB','','Release','Release (global version number) impacted by the item','P',0,1,1,0,0),(214,'release','TF','10/40','Release','Release (global version number) impacted by the item (<u>in free text</u>)','S',0,1,1,0,0),(215,'originator_name','TF','20/40','Originator Name','Name of the person who submitted the item (if different from the submitter field)','S',0,1,1,0,0),(216,'originator_email','TF','20/40','Originator Email','Email address of the person who submitted the item (if different from the submitter field, add address to CC list)','S',0,1,1,0,0),(217,'originator_phone','TF','10/40','Originator Phone','Phone number of the person who submitted the item','S',0,1,1,0,0),(218,'planned_starting_date','DF','10/15','Should Start On','Date and time when someone should start working on the item','S',1,1,1,0,0),(219,'planned_close_date','DF','10/15','Should be Finished on','Date and time when the item should be completed','S',1,1,1,0,0),(220,'percent_complete','SB','','Percent Complete','','S',0,1,1,0,0),(300,'custom_tf1','TF','10/15','Custom Text Field #1','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(301,'custom_tf2','TF','10/15','Custom Text Field #2','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(302,'custom_tf3','TF','10/15','Custom Text Field #3','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(303,'custom_tf4','TF','10/15','Custom Text Field #4','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(304,'custom_tf5','TF','10/15','Custom Text Field #5','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(305,'custom_tf6','TF','10/15','Custom Text Field #6','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(306,'custom_tf7','TF','10/15','Custom Text Field #7','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(307,'custom_tf8','TF','10/15','Custom Text Field #8','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(308,'custom_tf9','TF','10/15','Custom Text Field #9','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(309,'custom_tf10','TF','10/15','Custom Text Field #10','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1),(400,'custom_ta1','TA','60/3','Custom Text Area #1','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(401,'custom_ta2','TA','60/3','Custom Text Area #2','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(402,'custom_ta3','TA','60/3','Custom Text Area #3','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(403,'custom_ta4','TA','60/3','Custom Text Area #4','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(404,'custom_ta5','TA','60/3','Custom Text Area #5','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(405,'custom_ta6','TA','60/3','Custom Text Area #6','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(406,'custom_ta7','TA','60/3','Custom Text Area #7','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(407,'custom_ta8','TA','60/3','Custom Text Area #8','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(408,'custom_ta9','TA','60/3','Custom Text Area #9','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(409,'custom_ta10','TA','60/3','Custom Text Area #10','Customizable Text Area (multi-line text)','P',0,1,1,0,1),(500,'custom_sb1','SB','','Custom Select Box #1','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(501,'custom_sb2','SB','','Custom Select Box #2','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(502,'custom_sb3','SB','','Custom Select Box #3','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(503,'custom_sb4','SB','','Custom Select Box #4','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(504,'custom_sb5','SB','','Custom Select Box #5','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(505,'custom_sb6','SB','','Custom Select Box #6','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(506,'custom_sb7','SB','','Custom Select Box #7','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(507,'custom_sb8','SB','','Custom Select Box #8','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(508,'custom_sb9','SB','','Custom Select Box #9','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(509,'custom_sb10','SB','','Custom Select Box #10','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1),(600,'custom_df1','DF','10/10','Custom Date Field #1','Customizable Date Field','P',0,1,1,0,1),(601,'custom_df2','DF','10/10','Custom Date Field #2','Customizable Date Field','P',0,1,1,0,1),(602,'custom_df3','DF','10/10','Custom Date Field #3','Customizable Date Field','P',0,1,1,0,1),(603,'custom_df4','DF','10/10','Custom Date Field #4','Customizable Date Field','P',0,1,1,0,1),(604,'custom_df5','DF','10/10','Custom Date Field #5','Customizable Date Field','P',0,1,1,0,1),(109,'privacy','SB','','Privacy','Determines whether the item can be seen by members of the group only or anybody.','S',0,1,1,0,0),(110,'vote','TF','6/10','Votes','How many votes this item received','S',0,1,0,1,0),(112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0),(605,'updated','DF','10/15','Updated','Last time the item was updated','S',0,0,0,1,0);
/*!40000 ALTER TABLE `task_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `task_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_field_usage` (
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `use_it` int(11) NOT NULL DEFAULT 0,
  `show_on_add` int(11) NOT NULL DEFAULT 0,
  `show_on_add_members` int(11) NOT NULL DEFAULT 0,
  `place` int(11) NOT NULL DEFAULT 0,
  `custom_label` varchar(255) DEFAULT NULL,
  `custom_description` varchar(255) DEFAULT NULL,
  `custom_display_size` varchar(255) DEFAULT NULL,
  `custom_empty_ok` int(11) DEFAULT NULL,
  `custom_keep_history` int(11) DEFAULT NULL,
  `transition_default_auth` char(1) NOT NULL DEFAULT 'A',
  KEY `idx_bug_fu_field_id` (`bug_field_id`),
  KEY `idx_bug_fu_group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_field_usage` WRITE;
/*!40000 ALTER TABLE `task_field_usage` DISABLE KEYS */;
INSERT INTO `task_field_usage` VALUES (90,100,1,0,0,10,NULL,NULL,NULL,NULL,NULL,'A'),(91,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL,'A'),(92,100,1,0,0,20,NULL,NULL,NULL,NULL,NULL,'A'),(93,100,1,0,0,40,NULL,NULL,NULL,NULL,NULL,'A'),(94,100,1,0,0,50,NULL,NULL,NULL,NULL,NULL,'A'),(101,100,1,0,0,600,NULL,NULL,NULL,NULL,NULL,'A'),(102,100,0,0,0,200,NULL,NULL,NULL,NULL,NULL,'A'),(103,100,1,1,1,100,NULL,NULL,NULL,NULL,NULL,'A'),(104,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,'A'),(105,100,1,1,1,700000,NULL,NULL,NULL,NULL,NULL,'A'),(106,100,1,1,1,700001,NULL,NULL,NULL,NULL,NULL,'A'),(107,100,0,1,1,300,NULL,NULL,NULL,NULL,NULL,'A'),(108,100,1,0,1,400,NULL,NULL,NULL,NULL,NULL,'A'),(200,100,0,0,0,1000,NULL,NULL,NULL,NULL,NULL,'A'),(201,100,0,0,0,1100,NULL,NULL,NULL,NULL,NULL,'A'),(202,100,0,0,0,1200,NULL,NULL,NULL,NULL,NULL,'A'),(203,100,0,0,0,1300,NULL,NULL,NULL,NULL,NULL,'A'),(204,100,0,0,0,1400,NULL,NULL,NULL,NULL,NULL,'A'),(205,100,1,0,0,1500,NULL,NULL,NULL,NULL,NULL,'A'),(206,100,1,1,1,1700,NULL,NULL,NULL,NULL,NULL,'A'),(207,100,0,0,0,1600,NULL,NULL,NULL,NULL,NULL,'A'),(208,100,0,0,0,1800,NULL,NULL,NULL,NULL,NULL,'A'),(209,100,0,0,0,1900,NULL,NULL,NULL,NULL,NULL,'A'),(210,100,0,0,0,2000,NULL,NULL,NULL,NULL,NULL,'A'),(211,100,1,1,1,200,NULL,NULL,NULL,NULL,NULL,'A'),(212,100,0,0,0,3000,NULL,NULL,NULL,NULL,NULL,'A'),(213,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(214,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(215,100,0,0,0,550,NULL,NULL,NULL,NULL,NULL,'A'),(216,100,0,0,0,560,NULL,NULL,NULL,NULL,NULL,'A'),(217,100,0,0,0,570,NULL,NULL,NULL,NULL,NULL,'A'),(218,100,1,1,1,55,NULL,NULL,NULL,NULL,NULL,'A'),(219,100,1,1,1,56,NULL,NULL,NULL,NULL,NULL,'A'),(220,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,'A'),(300,100,0,0,0,30000,NULL,NULL,NULL,NULL,NULL,'A'),(301,100,0,0,0,30100,NULL,NULL,NULL,NULL,NULL,'A'),(302,100,0,0,0,30200,NULL,NULL,NULL,NULL,NULL,'A'),(303,100,0,0,0,30300,NULL,NULL,NULL,NULL,NULL,'A'),(304,100,0,0,0,30400,NULL,NULL,NULL,NULL,NULL,'A'),(305,100,0,0,0,30500,NULL,NULL,NULL,NULL,NULL,'A'),(306,100,0,0,0,30600,NULL,NULL,NULL,NULL,NULL,'A'),(307,100,0,0,0,30700,NULL,NULL,NULL,NULL,NULL,'A'),(308,100,0,0,0,30800,NULL,NULL,NULL,NULL,NULL,'A'),(309,100,0,0,0,30900,NULL,NULL,NULL,NULL,NULL,'A'),(400,100,0,0,0,40000,NULL,NULL,NULL,NULL,NULL,'A'),(401,100,0,0,0,40100,NULL,NULL,NULL,NULL,NULL,'A'),(402,100,0,0,0,40200,NULL,NULL,NULL,NULL,NULL,'A'),(403,100,0,0,0,40300,NULL,NULL,NULL,NULL,NULL,'A'),(404,100,0,0,0,40400,NULL,NULL,NULL,NULL,NULL,'A'),(405,100,0,0,0,40500,NULL,NULL,NULL,NULL,NULL,'A'),(406,100,0,0,0,40600,NULL,NULL,NULL,NULL,NULL,'A'),(407,100,0,0,0,40700,NULL,NULL,NULL,NULL,NULL,'A'),(408,100,0,0,0,40800,NULL,NULL,NULL,NULL,NULL,'A'),(409,100,0,0,0,40900,NULL,NULL,NULL,NULL,NULL,'A'),(500,100,0,0,0,50000,NULL,NULL,NULL,NULL,NULL,'A'),(501,100,0,0,0,50100,NULL,NULL,NULL,NULL,NULL,'A'),(502,100,0,0,0,50200,NULL,NULL,NULL,NULL,NULL,'A'),(503,100,0,0,0,50300,NULL,NULL,NULL,NULL,NULL,'A'),(504,100,0,0,0,50400,NULL,NULL,NULL,NULL,NULL,'A'),(505,100,0,0,0,50500,NULL,NULL,NULL,NULL,NULL,'A'),(506,100,0,0,0,50600,NULL,NULL,NULL,NULL,NULL,'A'),(507,100,0,0,0,50700,NULL,NULL,NULL,NULL,NULL,'A'),(508,100,0,0,0,50800,NULL,NULL,NULL,NULL,NULL,'A'),(509,100,0,0,0,50900,NULL,NULL,NULL,NULL,NULL,'A'),(600,100,0,0,0,60000,NULL,NULL,NULL,NULL,NULL,'A'),(601,100,0,0,0,60100,NULL,NULL,NULL,NULL,NULL,'A'),(602,100,0,0,0,60200,NULL,NULL,NULL,NULL,NULL,'A'),(603,100,0,0,0,60300,NULL,NULL,NULL,NULL,NULL,'A'),(604,100,0,0,0,60400,NULL,NULL,NULL,NULL,NULL,'A'),(109,100,1,1,1,402,NULL,NULL,NULL,NULL,NULL,'A'),(110,100,1,1,1,405,NULL,NULL,NULL,NULL,NULL,'A'),(112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A'),(605,100,1,0,0,800000,NULL,NULL,NULL,NULL,NULL,'A');
/*!40000 ALTER TABLE `task_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `task_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_field_value` (
  `bug_fv_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_field_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `value_id` int(11) NOT NULL DEFAULT 0,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL DEFAULT 0,
  `status` char(1) NOT NULL DEFAULT 'A',
  `email_ad` text DEFAULT NULL,
  `send_all_flag` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bug_fv_id`),
  KEY `idx_bug_fv_field_id` (`bug_fv_id`),
  KEY `idx_bug_fv_group_id` (`group_id`),
  KEY `idx_bug_fv_value_id` (`value_id`),
  KEY `idx_bug_fv_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=2957 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_field_value` WRITE;
/*!40000 ALTER TABLE `task_field_value` DISABLE KEYS */;
INSERT INTO `task_field_value` VALUES
  (101, 101, 100, 1, 'Open', 'The bug has been submitted', 20, 'P', NULL, 0),
  (102, 101, 100, 3, 'Closed',
    'The bug is no longer active. See the Resolution field '
      'for details on how it was resolved.',
    400, 'P', NULL, 0),
  (131, 102, 100, 1, '1 - Wish',
    'Issue which is mainly a matter of taste', 10, 'A', NULL, 0),
  (132, 102, 100, 2, '1, 5', '', 20, 'H', NULL,0),
  (133, 102, 100, 3, '2 - Minor',
    "Issue which doesn't affect the object's usefulness, "
      'and is presumably trivial to handle',
    30, 'A', NULL, 0),
  (134, 102, 100, 4, '2, 5', '', 40, 'H', NULL,0),
  (135, 102, 100, 5, '3 - Normal', '', 50, 'A', NULL, 0),
  (136, 102, 100, 6, '3, 5', '', 60, 'H', NULL,0),
  (137, 102, 100, 7, '4 - Important',
    'Issue which has a major effect on the usability of the object, '
      'without rendering it completely unusable to everyone',
    70, 'A', NULL, 0),
  (138, 102, 100, 8, '5 - Blocker',
    'Issue which makes the object in question unusable or mostly so, '
      'or causes data loss',
    80, 'A', NULL, 0),
  (139, 102, 100, 9, '6 - Security',
     'Issue which introduces a security breach', 90, 'A', NULL, 0),
  (150, 103, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (160, 107, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (177, 108, 100, 7, 'Duplicate',
    'This item is already covered by another item', 120, 'H', NULL, 0),
  (174, 108, 100, 4, 'Postponed', 'The issue will be handled later', 80, 'A',
     NULL, 0),
  (176, 108, 100, 6, 'Works For Me', 'No problem found for the group team',
    60, 'H', NULL, 0),
  (173, 108, 100, 8, 'Need Info',
    'More information is need to be able to handle this item', 90, 'A', NULL,
    0),
  (178, 108, 100, 3, 'Cancelled',
    'The item will not be carried out (see comments)', 40, 'A', NULL, 0),
  (172, 108, 100, 2, 'Invalid',
    'This item is not valid for some reason (see comments)', 130, 'H', NULL,
    0),
  (200, 200, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (210, 201, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (220, 202, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (221, 202, 100, 110, 'Every Time', '', 20, 'P', NULL, 0),
  (222, 202, 100, 120, 'Intermittent', '', 30, 'P', NULL, 0),
  (223, 202, 100, 130, 'Once', '', 40, 'P', NULL, 0),
  (240, 203, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (241, 203, 100, 110, 'Low <30', '', 20, 'A', NULL, 0),
  (242, 203, 100, 120, 'Medium 30 - 200', '', 30, 'A', NULL, 0),
  (243, 203, 100, 130, 'High >200', '', 40, 'A', NULL, 0),
  (250, 204, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (260, 205, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (270, 207, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (281, 211, 100, 1, '1 - Later', '', 10, 'A', NULL, 0),
  (282, 211, 100, 2, '2', '', 20, 'A', NULL, 0),
  (283, 211, 100, 3, '3 - Low', '', 30, 'A', NULL, 0),
  (284, 211, 100, 4, '4', '', 40, 'A', NULL, 0),
  (285, 211, 100, 5, '5 - Normal', '', 50, 'A', NULL, 0),
  (286, 211, 100, 6, '6', '', 60, 'A', NULL, 0),
  (287, 211, 100, 7, '7 - High', '', 70, 'A', NULL, 0),
  (298, 211, 100, 8, '8', '', 80, 'A', NULL, 0),
  (299, 211, 100, 9, '9 - Immediate', '', 90, 'A', NULL, 0),
  (300, 213, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (400, 500, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (401, 501, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (402, 502, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (403, 503, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (404, 504, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (405, 505, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (406, 506, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (407, 507, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (408, 508, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (409, 509, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (719, 220, 100, 1,  '0%', '', 1, 'A', NULL, 0),
  (720, 220, 100, 10, '10%', '', 10, 'A', NULL, 0),
  (721, 220, 100, 20, '20%', '', 20, 'A', NULL, 0),
  (722, 220, 100, 30, '30%', '', 30, 'A', NULL, 0),
  (723, 220, 100, 40, '40%', '', 40, 'A', NULL, 0),
  (724, 220, 100, 50, '50%', '', 50, 'A', NULL, 0),
  (725, 220, 100, 60, '60%', '', 60, 'A', NULL, 0),
  (726, 220, 100, 70, '70%', '', 70, 'A', NULL, 0),
  (727, 220, 100, 80, '80%', '', 80, 'A', NULL, 0),
  (728, 220, 100, 90, '90%', '', 90, 'A', NULL, 0),
  (729, 220, 100, 100, '100%', '', 100, 'A', NULL, 0),
  (111, 109, 100, 1, 'Public', 'This item can be seen by everybody.', 10, 'A',
    NULL, 1),
  (112, 109, 100, 2, 'Private', 'This item can be seen only by group members.',
    20, 'A', NULL, 1),
  (211, 201, 100, 110, 'GNU/Linux', '', 20, 'A', NULL, 0),
  (212, 201, 100, 120, 'Microsoft Windows', '', 30, 'A', NULL, 0),
  (213, 201, 100, 130, '*BSD', '', 40, 'A', NULL, 0),
  (214, 201, 100, 140, 'Mac OS', '', 50, 'A', NULL, 0),
  (170, 108, 100, 100, 'None', '', 10, 'P', NULL, 0),
  (171, 108, 100, 1, 'Done', 'The item was succesfuly done', 30, 'A', NULL, 0),
  (179, 108, 100, 9, 'In Progress', 'This item is currently being worked on',
    70, 'A', NULL, 0),
  (180, 108, 100, 10, 'Ready For Test', 'This item should be tested now', 65,
    'A', NULL, 0),
  (181, 108, 100, 11, 'Confirmed', 'The issue is confirmed', 82, 'H', NULL, 0),
  (2955, 112, 100, 0, 'Unlocked', 'Comment can be added freely', 20, 'P',
     NULL, 1),
  (2956, 112, 100, 1, 'Locked', 'Discussion about this item is over', 30, 'P',
    NULL, 1),
   (2957, 103, 101, 101, 'Group approval', 'Pending group registration', 10,
     'P', NULL, 0);
/*!40000 ALTER TABLE `task_field_value` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `task_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_history` (
  `bug_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `field_name` text NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `mod_by` int(11) NOT NULL DEFAULT 0,
  `date` int(11) DEFAULT NULL,
  `spamscore` int(2) DEFAULT 0,
  `ip` varchar(15) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`bug_history_id`),
  KEY `idx_bug_history_bug_id` (`bug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `task_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_report` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 100,
  `user_id` int(11) NOT NULL DEFAULT 100,
  `name` varchar(80) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scope` char(3) NOT NULL DEFAULT 'I',
  PRIMARY KEY (`report_id`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `scope_idx` (`scope`)
) ENGINE=MyISAM AUTO_INCREMENT=104 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_report` WRITE;
/*!40000 ALTER TABLE `task_report` DISABLE KEYS */;
INSERT INTO `task_report` VALUES (100,100,100,'Basic','The system default bug report','S'),(101,100,100,'Advanced','The second, more complex, default bug report','S'),(102,100,100,'Votes','The default plus the votes','S'),(103,100,100,'By Date','Based on dates','S');
/*!40000 ALTER TABLE `task_report` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `task_report_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_report_field` (
  `report_id` int(11) NOT NULL DEFAULT 100,
  `field_name` varchar(255) DEFAULT NULL,
  `show_on_query` int(11) DEFAULT NULL,
  `show_on_result` int(11) DEFAULT NULL,
  `place_query` int(11) DEFAULT NULL,
  `place_result` int(11) DEFAULT NULL,
  `col_width` int(11) DEFAULT NULL,
  KEY `report_id_idx` (`report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_report_field` WRITE;
/*!40000 ALTER TABLE `task_report_field` DISABLE KEYS */;
INSERT INTO `task_report_field` VALUES (101,'planned_starting_date',0,1,NULL,900,NULL),(101,'summary',1,1,501,20,NULL),(101,'resolution_id',1,1,15,23,NULL),(101,'status_id',1,0,10,NULL,NULL),(101,'bug_group_id',1,0,55,NULL,NULL),(101,'category_id',1,1,50,21,NULL),(101,'bug_id',0,1,NULL,1,NULL),(100,'planned_close_date',0,1,NULL,50,NULL),(100,'summary',0,1,NULL,20,NULL),(100,'resolution_id',1,1,15,22,NULL),(100,'status_id',1,0,10,NULL,NULL),(100,'assigned_to',1,1,20,40,NULL),(100,'bug_group_id',1,0,55,NULL,NULL),(100,'category_id',1,0,50,NULL,NULL),(100,'bug_id',0,1,NULL,1,NULL),(101,'planned_close_date',0,1,NULL,880,NULL),(101,'close_date',0,1,NULL,879,NULL),(101,'submitted_by',1,1,30,41,NULL),(101,'assigned_to',1,1,31,40,NULL),(101,'priority',1,0,402,NULL,NULL),(102,'bug_id',0,1,NULL,1,NULL),(102,'vote',0,1,NULL,2,NULL),(102,'category_id',1,0,50,NULL,NULL),(102,'bug_group_id',1,0,55,NULL,NULL),(102,'assigned_to',1,1,20,40,NULL),(102,'status_id',1,0,10,NULL,NULL),(102,'resolution_id',1,1,15,22,NULL),(102,'summary',0,1,NULL,20,NULL),(102,'date',0,1,NULL,50,NULL),(103,'bug_id',0,1,NULL,1,NULL),(103,'updated',0,1,NULL,1000,NULL),(103,'category_id',1,0,50,NULL,NULL),(103,'bug_group_id',1,0,55,NULL,NULL),(103,'assigned_to',1,1,20,40,NULL),(103,'status_id',1,0,10,NULL,NULL),(103,'resolution_id',1,1,15,22,NULL),(103,'summary',0,1,NULL,20,NULL),(103,'planned_starting_date',1,1,1,900,NULL),(103,'planned_close_date',1,1,2,880,NULL),(101,'details',1,0,502,NULL,NULL);
/*!40000 ALTER TABLE `task_report_field` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `trackers_export`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_export` (
  `export_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL DEFAULT 0,
  `artifact` varchar(16) NOT NULL DEFAULT '',
  `unix_group_name` varchar(255) NOT NULL DEFAULT '',
  `user_name` varchar(255) NOT NULL DEFAULT '',
  `sql` text NOT NULL,
  `status` char(1) NOT NULL DEFAULT '',
  `date` int(11) NOT NULL DEFAULT 0,
  `frequency_day` int(2) DEFAULT NULL,
  `frequency_hour` int(2) DEFAULT NULL,
  PRIMARY KEY (`export_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_field_transition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_field_transition` (
  `transition_id` int(11) NOT NULL AUTO_INCREMENT,
  `artifact` varchar(16) NOT NULL DEFAULT '',
  `group_id` int(11) NOT NULL DEFAULT 0,
  `field_id` int(11) NOT NULL DEFAULT 0,
  `from_value_id` int(11) NOT NULL DEFAULT 0,
  `to_value_id` int(11) NOT NULL DEFAULT 0,
  `is_allowed` char(1) DEFAULT 'Y',
  `notification_list` text DEFAULT NULL,
  PRIMARY KEY (`transition_id`)
) ENGINE=MyISAM AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_field_transition_other_field_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_field_transition_other_field_update` (
  `other_field_update_id` int(11) NOT NULL AUTO_INCREMENT,
  `transition_id` int(11) NOT NULL DEFAULT 0,
  `update_field_name` varchar(255) NOT NULL DEFAULT '',
  `update_value_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`other_field_update_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_file` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL DEFAULT 0,
  `artifact` varchar(16) NOT NULL DEFAULT '',
  `submitted_by` int(11) NOT NULL DEFAULT 0,
  `date` int(11) NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `filename` text NOT NULL,
  `filesize` int(11) NOT NULL DEFAULT 0,
  `filetype` text NOT NULL,
  PRIMARY KEY (`file_id`),
  KEY `item_id_idx` (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_msgid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_msgid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `msg_id` varchar(255) NOT NULL DEFAULT '',
  `artifact` varchar(16) NOT NULL DEFAULT '',
  `item_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_notification` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `role_id` int(11) NOT NULL DEFAULT 0,
  `event_id` int(11) NOT NULL DEFAULT 0,
  `notify` int(11) NOT NULL DEFAULT 1,
  KEY `user_id_idx` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_notification_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_notification_event` (
  `event_id` int(11) NOT NULL DEFAULT 0,
  `event_label` varchar(255) DEFAULT NULL,
  `short_description` varchar(40) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `rank` int(11) NOT NULL DEFAULT 0,
  KEY `event_id_idx` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trackers_notification_event` WRITE;
/*!40000 ALTER TABLE `trackers_notification_event` DISABLE KEYS */;
INSERT INTO `trackers_notification_event` VALUES (1,'ROLE_CHANGE','Role has changed','I\'m added to or removed from this role',10),(2,'NEW_COMMENT','New comment','A new followup comment is added',20),(3,'NEW_FILE','New attachment','A new file attachment is added',30),(4,'CC_CHANGE','CC Change','A new CC address is added/removed',40),(5,'CLOSED','Item closed','The item is closed',50),(6,'PSS_CHANGE','PSS change','Priority,Status,Severity changes',60),(7,'ANY_OTHER_CHANGE','Any other Changes','Any changes not mentioned above',70),(8,'I_MADE_IT','I did it','I am the author of the change',80),(9,'NEW_ITEM','New Item','A new item has been submitted',90);
/*!40000 ALTER TABLE `trackers_notification_event` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `trackers_notification_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_notification_role` (
  `role_id` int(11) NOT NULL DEFAULT 0,
  `role_label` varchar(255) DEFAULT NULL,
  `short_description` varchar(40) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `rank` int(11) NOT NULL DEFAULT 0,
  KEY `role_id_idx` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trackers_notification_role` WRITE;
/*!40000 ALTER TABLE `trackers_notification_role` DISABLE KEYS */;
INSERT INTO `trackers_notification_role` VALUES (1,'SUBMITTER','Submitter','The person who submitted the item',10),(2,'ASSIGNEE','Assignee','The person to whom the item was assigned',20),(3,'CC','CC','The person who is in the CC list',30),(4,'COMMENTER','Commenter','A person who once posted a follow-up comment',40);
/*!40000 ALTER TABLE `trackers_notification_role` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `trackers_spamban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_spamban` (
  `ip` char(15) NOT NULL DEFAULT '',
  `date` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_spamcheck_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_spamcheck_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `artifact` varchar(16) NOT NULL DEFAULT '',
  `item_id` int(11) NOT NULL DEFAULT 0,
  `comment_id` int(11) NOT NULL DEFAULT 0,
  `priority` int(1) NOT NULL DEFAULT 1,
  `date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`queue_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_spamcheck_queue_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_spamcheck_queue_notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `artifact` varchar(16) NOT NULL DEFAULT '',
  `item_id` int(11) NOT NULL DEFAULT 0,
  `comment_id` int(11) NOT NULL DEFAULT 0,
  `to_header` text DEFAULT NULL,
  `subject_header` text DEFAULT NULL,
  `other_headers` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  PRIMARY KEY (`notification_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_spamscore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_spamscore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `score` int(2) NOT NULL DEFAULT 1,
  `affected_user_id` int(11) NOT NULL DEFAULT 0,
  `reporter_user_id` int(11) NOT NULL DEFAULT 0,
  `artifact` varchar(16) NOT NULL DEFAULT '',
  `item_id` int(11) NOT NULL DEFAULT 0,
  `comment_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trackers_watcher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trackers_watcher` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `watchee_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  KEY `user_id_idx` (`user_id`),
  KEY `watchee_id_idx` (`watchee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(33) NOT NULL,
  `email` text NOT NULL,
  `user_pw` varchar(128) DEFAULT NULL,
  `realname` varchar(32) NOT NULL DEFAULT '',
  `status` varchar(16) NOT NULL DEFAULT 'A',
  `uidNumber` int(11) DEFAULT NULL,
  `spamscore` int(2) DEFAULT 0,
  `add_date` int(11) NOT NULL DEFAULT 0,
  `confirm_hash` varchar(32) DEFAULT NULL,
  `authorized_keys` text DEFAULT NULL,
  `authorized_keys_count` int(11) DEFAULT NULL,
  `email_new` text DEFAULT NULL,
  `people_view_skills` int(11) NOT NULL DEFAULT 0,
  `people_resume` text NOT NULL,
  `timezone` varchar(64) DEFAULT 'GMT',
  `theme` varchar(15) DEFAULT '',
  `email_hide` char(3) DEFAULT '0',
  `gpg_key` mediumtext DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_user_user` (`status`),
  KEY `user_name` (`user_name`),
  KEY `idx_uidNumber` (`uidNumber`)
) ENGINE=MyISAM AUTO_INCREMENT=102 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
-- 'Zero user': user_id = 100 is a magic number that may be used in code.
INSERT INTO `user` SET
  user_id = 100, user_name = 'None', realname = 'None',
  email = '_@localhost', status = 'S';
-- First user: default admin.  Born active, with the password
-- equal to user_name.
INSERT INTO `user` SET
  user_id = 101, user_name = '-', realname = 'Admin Savane',
  email = '_@localhost', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `user_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_bookmarks` (
  `bookmark_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `bookmark_url` text DEFAULT NULL,
  `bookmark_title` text DEFAULT NULL,
  PRIMARY KEY (`bookmark_id`),
  KEY `idx_user_bookmark_user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_group` (
  `user_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `admin_flags` char(16) DEFAULT NULL,
  `cache_uidNumber` int(11) DEFAULT NULL,
  `cache_gidNumber` int(11) DEFAULT NULL,
  `cache_user_name` varchar(33) DEFAULT NULL,
  `onduty` tinyint(4) NOT NULL DEFAULT 1,
  `privacy_flags` int(1) DEFAULT 1,
  `forum_flags` int(11) DEFAULT NULL,
  `bugs_flags` int(11) DEFAULT NULL,
  `task_flags` int(11) DEFAULT NULL,
  `patch_flags` int(11) DEFAULT NULL,
  `support_flags` int(11) DEFAULT NULL,
  `cookbook_flags` int(11) DEFAULT NULL,
  `news_flags` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_group_id`),
  KEY `bugs_flags_idx` (`bugs_flags`),
  KEY `task_flags_idx` (`task_flags`),
  KEY `patch_flags_idx` (`patch_flags`),
  KEY `support_flags_idx` (`support_flags`),
  KEY `forum_flags_idx` (`forum_flags`),
  KEY `admin_flags_idx` (`admin_flags`),
  KEY `pk_idx` (`user_id`,`group_id`),
  KEY `idx_cache_uidNumber` (`cache_uidNumber`),
  KEY `idx_cache_gidNumber` (`cache_gidNumber`)
) ENGINE=MyISAM AUTO_INCREMENT=102 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_group` WRITE;
/*!40000 ALTER TABLE `user_group` DISABLE KEYS */;
INSERT INTO `user_group` VALUES (101,101,101,'A',0,0,'',1,1,NULL,NULL,0,NULL,0,0,0);
/*!40000 ALTER TABLE `user_group` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `user_lostpw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_lostpw` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `count` int(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_preferences` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `preference_name` varchar(255) NOT NULL DEFAULT '',
  `preference_value` mediumtext DEFAULT NULL,
  PRIMARY KEY (`user_id`,`preference_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `user_squad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_squad` (
  `user_squad_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `squad_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_squad_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `user_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `tracker` varchar(16) NOT NULL DEFAULT '',
  `item_id` int(11) NOT NULL DEFAULT 0,
  `howmuch` int(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`vote_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
