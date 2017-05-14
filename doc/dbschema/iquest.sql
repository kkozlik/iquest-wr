
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

# Dumping database structure for iquest
CREATE DATABASE IF NOT EXISTS `iquest` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_czech_ci */;
USE `iquest`;


# Dumping structure for table iquest.clue
CREATE TABLE IF NOT EXISTS `clue` (
  `clue_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `ref_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `cgrp_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `content_type` varchar(45) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('regular','coin','hidden','special') COLLATE utf8_czech_ci NOT NULL DEFAULT 'regular',
  `comment` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `ordering` int(11) NOT NULL,
  PRIMARY KEY (`clue_id`),
  UNIQUE KEY `ref_id_UNIQUE` (`ref_id`),
  KEY `fk_task_part_task` (`cgrp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.clue_grp
CREATE TABLE IF NOT EXISTS `clue_grp` (
  `cgrp_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `ref_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `ordering` int(11) NOT NULL,
  PRIMARY KEY (`cgrp_id`),
  UNIQUE KEY `ref_id_UNIQUE` (`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.clue_point_to_solution
CREATE TABLE IF NOT EXISTS `clue_point_to_solution` (
  `clue_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `solution_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`clue_id`,`solution_id`),
  KEY `fk_clue_has_task_solution_task_solution` (`solution_id`),
  KEY `fk_clue_has_task_solution_clue` (`clue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.event
CREATE TABLE IF NOT EXISTS `event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `type` enum('team_logged','key_entered','logout','giveitup','coin_gain','coin_spend') COLLATE utf8_czech_ci NOT NULL,
  `success` int(11) NOT NULL,
  `data` text COLLATE utf8_czech_ci,
  PRIMARY KEY (`event_id`),
  KEY `fk_event_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.hint
CREATE TABLE IF NOT EXISTS `hint` (
  `hint_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `ref_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `clue_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `content_type` varchar(45) COLLATE utf8_czech_ci NOT NULL,
  `timeout` time DEFAULT NULL,
  `price` decimal(5,2) DEFAULT NULL,
  `ordering` int(11) NOT NULL,
  `comment` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`hint_id`),
  UNIQUE KEY `ref_id_UNIQUE` (`ref_id`),
  KEY `fk_hint_clue1` (`clue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.hint_team
CREATE TABLE IF NOT EXISTS `hint_team` (
  `team_id` int(11) NOT NULL,
  `hint_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `show_at` datetime NOT NULL,
  `for_sale` tinyint(1) NOT NULL,
  PRIMARY KEY (`team_id`,`hint_id`),
  KEY `fk_hint_has_team_team` (`team_id`),
  KEY `fk_hint_has_team_hint` (`hint_id`),
  CONSTRAINT `fk_hint_team_team` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.message
CREATE TABLE IF NOT EXISTS `message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `for_team_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `fk_message_team` (`for_team_id`),
  CONSTRAINT `fk_message_team` FOREIGN KEY (`for_team_id`) REFERENCES `team` (`team_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.message_accepted
CREATE TABLE IF NOT EXISTS `message_accepted` (
  `team_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  PRIMARY KEY (`team_id`,`message_id`),
  KEY `fk_message_has_team_team` (`team_id`),
  KEY `fk_message_has_team_message` (`message_id`),
  CONSTRAINT `fk_message_accepted_team` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_message_accepted_message` FOREIGN KEY (`message_id`) REFERENCES `message` (`message_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.open_cgrp_team
CREATE TABLE IF NOT EXISTS `open_cgrp_team` (
  `team_id` int(11) NOT NULL,
  `cgrp_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `gained_at` datetime NOT NULL COMMENT 'timestamp specifing when the task has been opened\n',
  PRIMARY KEY (`team_id`,`cgrp_id`),
  KEY `fk_team_has_task_task` (`cgrp_id`),
  KEY `fk_team_has_task_team` (`team_id`),
  CONSTRAINT `fk_open_cgrp_team_team` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='list of open tasks for each team';

# Data exporting was unselected.


# Dumping structure for table iquest.options
CREATE TABLE IF NOT EXISTS `options` (
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='various options:\n* start_time\n* initial_task_id\n';

# Data exporting was unselected.


# Dumping structure for table iquest.task_solution
CREATE TABLE IF NOT EXISTS `task_solution` (
  `solution_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `ref_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `cgrp_id` varchar(64) COLLATE utf8_czech_ci NOT NULL COMMENT 'ID of clue grp that is opened by solving the task',
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `solution_key` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `content_type` varchar(45) COLLATE utf8_czech_ci NOT NULL,
  `comment` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `timeout` time NOT NULL,
  `countdown_start` enum('all','single') COLLATE utf8_czech_ci NOT NULL DEFAULT 'all' COMMENT 'Specify when to start the timeout countdown. The default value ''all'' means it should start counting down once all clues pointed to this solution are gained. If the value is set to ''single'' it''s sufficient to gain just one clue pointing to this solution.',
  `coin_value` decimal(5,2) NOT NULL DEFAULT '0.00',
  `stub` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Indicate dead end waypoint. Could be used e.g. to gain coins only.',
  PRIMARY KEY (`solution_id`),
  UNIQUE KEY `ref_id_UNIQUE` (`ref_id`),
  UNIQUE KEY `solution_key_UNIQUE` (`solution_key`),
  KEY `fk_task_solution_clue_grp` (`cgrp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.

# Dumping structure for table iquest.task_solution_nextcgrp
CREATE TABLE `task_solution_nextcgrp` (
	`solution_id` VARCHAR(64) NOT NULL COLLATE 'utf8_czech_ci',
	`cgrp_id` VARCHAR(64) NOT NULL COLLATE 'utf8_czech_ci',
	`cond` TEXT NULL COLLATE 'utf8_czech_ci',
	PRIMARY KEY (`solution_id`, `cgrp_id`),
	INDEX `solution_id` (`solution_id`),
	INDEX `cgrp_id` (`cgrp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.task_solution_team
CREATE TABLE IF NOT EXISTS `task_solution_team` (
  `team_id` int(11) NOT NULL,
  `solution_id` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `show_at` datetime NOT NULL,
  `solved_at` datetime NOT NULL,
  PRIMARY KEY (`team_id`,`solution_id`),
  KEY `fk_task_solution_has_team_team` (`team_id`),
  KEY `fk_task_solution_has_team_task_solution` (`solution_id`),
  CONSTRAINT `fk_task_solution_team_team` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.team
CREATE TABLE IF NOT EXISTS `team` (
  `team_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `username` varchar(45) COLLATE utf8_czech_ci NOT NULL,
  `passwd` varchar(45) COLLATE utf8_czech_ci NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `wallet` decimal(5,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`team_id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.


# Dumping structure for table iquest.team_rank
CREATE TABLE IF NOT EXISTS `team_rank` (
  `timestamp` datetime NOT NULL,
  `distance` mediumtext COLLATE utf8_czech_ci NOT NULL,
  `rank` mediumtext COLLATE utf8_czech_ci NOT NULL,
  `team_id` int(10) DEFAULT NULL COMMENT 'ID of team originating the change',
  PRIMARY KEY (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

# Data exporting was unselected.
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
