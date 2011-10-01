DROP TABLE IF EXISTS wcf1_shoutbox_entry;
CREATE TABLE wcf1_shoutbox_entry (
	`entryID` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`userID` INT(10) NOT NULL DEFAULT 0,
	`username` VARCHAR(255) NOT NULL DEFAULT '',
	`time` INT(20) NOT NULL DEFAULT 0,
	`message` MEDIUMTEXT,
	`me` INT(1) NOT NULL DEFAULT 0,
	`toUserID` INT(10) NOT NULL DEFAULT 0,
	`toUserName` VARCHAR(255) NOT NULL DEFAULT '',
	`ipAddress` VARCHAR(15) NOT NULL DEFAULT '',
	KEY (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wcf1_shoutbox_blacklist;
CREATE TABLE wcf1_shoutbox_blacklist (
	`userID` INT(10) NOT NULL,
	`blackUserID` INT(10) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wcf1_shoutbox_banlist;
CREATE TABLE wcf1_shoutbox_banlist (
	`userID` int(10) NOT NULL,
  	`until` int(10) NOT NULL,
  UNIQUE KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
