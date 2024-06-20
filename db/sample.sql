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
  email = 'alice@test.net', user_pw = '00WKk4u6PmmLA', status = 'A',
  gpg_key =
    'Test RSA public key.  This file contains no copyrightable data.\n'
    '-----BEGIN PGP PUBLIC KEY BLOCK-----\n'
    'Version: GnuPG v1\n'
    '\n'
    'mQENBGZNgdIBCAChVAcg7Oq5JcHPjBIa2iesBJfLu5wxEJaTATn52NjELy4zP13R\n'
    'NWfbCOhnEpDnQeAlrKUOig0C07RkW/0AL7Jnvu96JQ0mGh8VOt1WMa8Y6gXv2GU4\n'
    'jX9itwCmZVQ/UK9Seiucy9oj5H1/2qbEX4K5iUtjYpvgqZHv1HIKuBSz1ztBcDRK\n'
    'mPbHAQ02ogbaa5IdiHOV6RdQGbtBQ9pnjHy3ABHVou61HV/pM+gKY5cJ4Vc4Bf5l\n'
    '8oaWQh56utlsDhzZP1o1G9vEqX2prBvWcPdLw5w/H+3Y7w8J5DcCVb8W152Mef/0\n'
    'P4ng4yX7R2jqDpfhLmiySJwDWGePAuf1VTcpABEBAAG0I1NhdmFuZSAodGVzdCBr\n'
    'ZXkpIDxzYXZhbmVAdGVzdC5vcmc+iQE4BBMBAgAiBQJmTYHSAhsDBgsJCAcDAgYV\n'
    'CAIJCgsEFgIDAQIeAQIXgAAKCRAqzm7d7wC9jJNiB/9rcuLdxaYkb2QqX0+XXK0L\n'
    'otVLtNI29a9PEnBkTp9LuvjlEiLt7VtpDIZtOKjF7wBP9LUVWj6qXrnuGxKL+0JB\n'
    '/uhKN2PvuORhb46lLMZ2AMN2pC4z/6z0eLxAUSfnMykmFvQR4w9W3IMoz+1qWUmC\n'
    'WUmTA8SJr6BTlXfkiQoU7hWP3EFulxX0U/MF1bACGb14Iu2RtAyLwRbJFAlji3nf\n'
    'plX24OvRiRGSS7SWUGYkDo+z6uSf/fWUbfuB3C+sDvUSKdqNXNRhmn6fqTMg6Q2K\n'
    'r0esj6UqvmzHa9URiohJQg3HnNhnpNetdm0s0omnwdYhbIkb2OUEIhr+GSgySl0c\n'
    'uQENBGZNgdIBCADt6razMxgKtZHLsQrwxfFLJLEn14KAe31ckvHDPcbM2tdugHZ/\n'
    'OYTaAfqsak4Uuh3vUL93uOu0kjeJfbIx2xIkTd51wC61ZXDqrmqGbtUNbRBdGUul\n'
    'bDA+SYIs7jhh7hglWET3Nk36yRPAsrvpzUaPn6/HfTdp2LAavScwaEfXHr3IYLov\n'
    '+tkYEU788K8LAiblatxWNb8O5WpWLxBAjzUTkqDDL8BNBHIwDsPd5a7Y5TqVUdNu\n'
    'MC1YeU9mUqTshqGM74JdLTXRn+v24DxDuktz2hepF45rO5k5M1WFE7gmdGx3dAZL\n'
    'jqG3AGXfqbDvnuJMzTfN0jVmT3UqdNgh0jafABEBAAGJAR8EGAECAAkFAmZNgdIC\n'
    'GwwACgkQKs5u3e8AvYwpqAf/aqDKu8TanIfuld/atfkkngw/FPwYPjmcL3hKOWjz\n'
    'Ue9ClxEs8rnX33XM6iaNlqyr5l81HrnP/VId8lddaIHpphZ6Oii9dqBa57UpHCw2\n'
    'fiSo79rlgumIN+ilO6Z/8eFR/anpRb0YVC0DrL2lB0wNRhVHbV8WCBbP4NTNITPN\n'
    'wVodE0Ly8F+vcdyx5tVIBBAZ/FS9/nzvtso9mbh5yzZou1OzAOiR9pgQH43bkEcJ\n'
    'PkoL71BkqCt1qVk/wIiEUepAbFSNS2mNJVk4J7/Y/12SIt4a8qYUcMx/KNNSN5Tm\n'
    'KOdnfhZJIhDKz+RQvG+Q27gtJ5+AGAdSHTC/6vmO8Ba+Gg==\n'
    '=Qkbc\n'
    '-----END PGP PUBLIC KEY BLOCK-----\n',
  add_date = UNIX_TIMESTAMP ();
INSERT INTO `user` SET
  user_id = 103, user_name = 'bob', realname = 'ﺑﻮﺐ',
  email = 'bob@test.net', user_pw = '00WKk4u6PmmLA', status = 'A',
  gpg_key =
    'Test ECC public key.  This file contains no copyrightable data.\n'
    '-----BEGIN PGP PUBLIC KEY BLOCK-----\n'
    '\n'
    'mDMEZk4L7hYJKwYBBAHaRw8BAQdACklv6+JTOl/K8DnOmxTvIH8thncGGEzngWhV\n'
    'xA5LFsy0I1NhdmFuZSAodGVzdCBrZXkpIDxzYXZhbmVAdGVzdC5vcmc+iJIEExYK\n'
    'ADoWIQQiXbDJE6aJryo9l/4NAEoWSYKS7wUCZk4L7gIbAwULCQgHAgIiAgYVCgkI\n'
    'CwIDFgIBAh4HAheAAAoJEA0AShZJgpLvx5MBAM+zdtHso8QblU1Tg5rhFIgfQ/I+\n'
    'q+/fzN770cshv8J+AP0Q/TNrbbHxvI0i7TSHbUtHi5ixiklfjkWnTgamAMRABrg4\n'
    'BGZOC+4SCisGAQQBl1UBBQEBB0DykUVgWV4tiSZM0ijP5IDLbN1NFLH1KNetZiLW\n'
    'gNmyFAMBCAeIeAQYFgoAIBYhBCJdsMkTpomvKj2X/g0AShZJgpLvBQJmTgvuAhsM\n'
    'AAoJEA0AShZJgpLv9OIA/3mX9SKU/KwGUH+0e7t3cnU2reUnaqAlvqJKTUCJ7/vD\n'
    'AP9TDVGBKNlP1tK80XXhzcDJLXHuii7jF+K80vQz85jNCw==\n'
    '=RZ16\n'
    '-----END PGP PUBLIC KEY BLOCK-----\n',
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

