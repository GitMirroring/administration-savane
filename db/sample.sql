-- Sample database to add on top of initial.sql.
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
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

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

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
-- A set of accounts.   The password is '-', the same for all accounts.
INSERT INTO `user` SET
  user_id = 102, user_name = 'alice', realname = 'Alice',
  email = 'alice@test.net', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 103, user_name = 'bob', realname = 'ﺑﻮﺐ',
  email = 'bob@test.net', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 104, user_name = 'carol', realname = 'करोळ्',
  email = 'carol@test.net', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 105, user_name = 'dave', realname = 'Дейв',
  email = 'dave@test.net', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 106, user_name = 'eve', realname = 'Εύα',
  email = 'eve@test.net', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

