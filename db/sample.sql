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

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
-- A set of accounts.   The password is '-', the same for all accounts.
-- The GnuPG keys are passwordless test keys from frontend/php/testing/gpg.
INSERT INTO `user` SET
  -- Since the start of Git history, register.php applies strtolower ()
  -- before writing user_name to the database; however, as of 2024-06,
  -- a few accounts with capitals in user_name exist that were registered
  -- before 2005, see Savannah sr #111079.
  user_name = 'Alice',
  user_id = 102, realname = 'Alice',
  email = 'alice@savane.test', user_pw = '00WKk4u6PmmLA', status = 'A',
  gpg_key =
    'Test RSA public key.  This file contains no copyrightable data.\n'
    '-----BEGIN PGP PUBLIC KEY BLOCK-----\n'
    '\n'
    'mQENBGZ9PYMBCACVuaUN9npHw5Lg5x5JFEmrBT0GzfgI7zIsASDP8mTln/SCP7+3\n'
    'lIKcpwSsZ6sA4/elLyXqVBNlxjQOYoAsa1E4oSiJ/y+qH7QL/vKC7eGvxRY9jYnT\n'
    'OAbQCpLtHQs1+FUuX+TrgGQpQRabE2sFPC/n4iranMDkAAR4lEhAjPUDVam9l3uU\n'
    'jhinlPwUN1uFsAQDqz6YOYYm7ixRtFrdJgIGI3bral3OrMyW1hmHo8vxLV34GPWs\n'
    'KYaB7WtIwuZr7MRFI4e1Eps6m4pZXobO/4mtI0w2sGiAHqLK0h4bn4zIu5EIoVzb\n'
    'DujxuswPJRabxN3n4ut6IEiSGe8SyGiP5LaFABEBAAG0JEFsaWNlICh0ZXN0IGtl\n'
    'eSkgPGFsaWNlQHNhdmFuZS50ZXN0PokBUAQTAQgAOhYhBGZBcU8l0Rhnl9eoPX39\n'
    'IP0E2nbuBQJmfT2DAhsDBQsJCAcCAiICBhUKCQgLAgMWAgECHgcCF4AACgkQff0g\n'
    '/QTadu6UdAf+OwrWA0xskYeLTufHOy/XuedfIqTz8Ftp/WZYne0GbkaSQDS7kOdd\n'
    'Ge8pxfF3Kq0nYMum1GIAemVVhUEt5cSSC7WUSgbCyEQPx6atSxi9T8WPG9Mh2nIw\n'
    'WlDWXOIRekztiTKaFpeU8pyW8924xs7mjxCJWL0YYMWYlvkkI6zezfShKHe45csI\n'
    'm9O3jE7Ii1bwAW/XOhSlOCe4Q1kyoqRvUvfvcCuqlwv8YWzN7m7rv2AL6sVrWx7U\n'
    'v7fuzJAmp11BzA7Ax2LZ1J3AnKTRjHu0nzsjNEkEfJOMjciSTJ3KsHJIVbVEk62p\n'
    'tSGN03jfQi2x2Y1jNsdNJPWM0lLUXPCYtLkBDQRmfT2WAQgA7AKIyX5Ra8nFHsza\n'
    'vcruRhL/EucHHsjV5E18ktRltvyJlQYfzw1kaBw5/Vj3ChD/G9ROhw/f9/YdeuX0\n'
    'vo7u0Nhx+XQPdHhjpueMoYlgTru9yWqRZLIzWObxT0HZvffY5u7ny7QGj++ZTmJe\n'
    'OWgNAA7j0DOHWdybVsLOJQ7ACqRmyuwr1JEQYz6TQKGgZZRfO1+lpTLajL9Ba6Ul\n'
    'nx00Wrld9LZWv6ZwJ/3sEs+tQZnhZq+i81wexsJFYA8uq7PDKXO7hbuf/qK4K6Ks\n'
    'VhEcIOaiKM2qygZEoqhKTpk9b7NyBpYl+0tmEzfxjb+c6cmqVyK+t6pZ2cdMAFv9\n'
    'yZ+YvwARAQABiQE2BBgBCAAgFiEEZkFxTyXRGGeX16g9ff0g/QTadu4FAmZ9PZYC\n'
    'GwwACgkQff0g/QTadu4hBgf+K8fQ7bKHMY0HwgL6kOfLQA2WwTqxuX+W6e5yUUk5\n'
    'kziN6TIgMPblskzeOqp0G0s0yS2Ypv/ZT7U6WY0H90VQD3urcoYdpQVaLT9P9F80\n'
    'RhicNsrcV9JYU/fKJ7KxEB5cJGMyrlBfOYnlMzuCxldVrF3hVjXntrDmrPBRYjKR\n'
    'V6BeuIHl8a0Yz8Qq94dA4LVNayh8d0rdSyPOAN+7NCgggzpZN5w0IMKGvxDOwd70\n'
    'EbLn041N4xUyOa52kSrnY9fSCQ5rPpb8v9jSsjK0Yoetbk6VtaySOQw8ZkTu0H8b\n'
    'Y7QZgqGaXRyUQxphA6ABG4PVRUa7rQuWoFWq9dMVhClmiA==\n'
    '=OcPV\n'
    '-----END PGP PUBLIC KEY BLOCK-----\n',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 103, user_name = 'bob', realname = 'ﺑﻮﺐ',
  email = 'bob@savane.test', user_pw = '00WKk4u6PmmLA', status = 'A',
  gpg_key =
    'Test ECC public key.  This file contains no copyrightable data.\n'
    '-----BEGIN PGP PUBLIC KEY BLOCK-----\n'
    '\n'
    'mDMEZn097hYJKwYBBAHaRw8BAQdAwfyP0PMT7I8p0gYVoIcDtxltXi7OeC+C+vgu\n'
    'XRP06Eu0Ju+6ke+7ru+6kCAodGVzdCBrZXkpIDxib2JAc2F2YW5lLnRlc3Q+iJIE\n'
    'ExYKADoWIQQQXWtqoNRQAwZOtn1mPwJRYifeRgUCZn097gIbAwULCQgHAgIiAgYV\n'
    'CgkICwIDFgIBAh4HAheAAAoJEGY/AlFiJ95GF2MBAKNL4ncz7HUkj1cCuUufKqcN\n'
    'zezqziHEWhiEURYKS2ayAQC3Geie6TQnBdkZ5xWrOFJniwTH65mku2zgXilR5tzq\n'
    'CLg4BGZ9PnQSCisGAQQBl1UBBQEBB0DwQkRa2FjlER5UNYBk/rRF69TX2Z1g4JF8\n'
    '/q9mrI0rSwMBCAeIeAQYFgoAIBYhBBBda2qg1FADBk62fWY/AlFiJ95GBQJmfT50\n'
    'AhsMAAoJEGY/AlFiJ95GbBIBAOrkpM/SOnNQJdOYjGCLm0NTe2OYIKlG+Tw8skMY\n'
    'WfM8AQDAyliQCO2MVKYJCJO6pqGWMZxGamvqoSKI9K+Pk1x9DA==\n'
    '=7KX6\n'
    '-----END PGP PUBLIC KEY BLOCK-----\n',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 104, user_name = 'carol', realname = 'करोळ्',
  email = 'carol@savane.test', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 105, user_name = 'dave', realname = 'Дейв',
  email = 'dave@savane.test', user_pw = '00WKk4u6PmmLA', status = 'A',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 106, user_name = 'eve', realname = 'Εύα',
  email = 'eve@savane.test', user_pw = '00WKk4u6PmmLA', status = 'A',
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

