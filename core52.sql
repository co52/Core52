CREATE TABLE `sessions` (
	`key` varchar(100) NOT NULL ,
	`user` int(11) NULL DEFAULT NULL ,
	`method` set('cookie','uri') NOT NULL ,
	`signature` varchar(100) NOT NULL ,
	`hits` int(10) NOT NULL ,
	`_data` text NOT NULL ,
	`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
	`closed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 ,
	PRIMARY KEY (`key`),
	INDEX `ndx_sessions_signature` USING BTREE (`signature`),
	INDEX `ndx_sessions_users` USING BTREE (`user`),
	INDEX `ndx_sessions_timestamp` USING BTREE (`timestamp`),
	INDEX `ndx_sessions_closed` USING BTREE (`closed`)
);

CREATE TABLE `hits` (
	`id`  int(10) NOT NULL AUTO_INCREMENT ,
	`user`  int(11) NULL DEFAULT NULL ,
	`session`  varchar(100) NULL DEFAULT NULL ,
	`ip`  varchar(45) NULL DEFAULT NULL ,
	`_data`  text NULL DEFAULT NULL ,
	`timestamp`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
	PRIMARY KEY (`id`),
	INDEX `fk_hits_sessions` USING BTREE (`session`),
	INDEX `fk_hits_users` USING BTREE (`user`),
	INDEX `ndx_hits_timestamp` USING BTREE (`timestamp`)
);

CREATE TABLE `sitesettings` (
	`name` varchar(45) NOT NULL,
	`value` varchar(45) default NULL,
	PRIMARY KEY  (`name`)
);


-- OPTIONAL: Mailqueue table (used in Mailer.php)

CREATE TABLE `mailqueue` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
	`to` text NOT NULL ,
	`subject` varchar(255) NULL DEFAULT NULL ,
	`user` int(11) UNSIGNED NULL DEFAULT NULL ,
	`page_url` varchar(255) NULL DEFAULT NULL ,
	`email_body` longtext NULL DEFAULT NULL ,
	`time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
	`send_after` timestamp NULL DEFAULT NULL ,
	`status` enum('pending','processing','sent','failed') NOT NULL DEFAULT 'pending' ,
	`attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 ,
	`error_msg` varchar(255) NULL DEFAULT NULL ,
	`process_id` int(11) NULL DEFAULT NULL ,
	`process_started` datetime NULL DEFAULT NULL ,
	PRIMARY KEY (`id`),
	INDEX `ndx_mailqueue_status` (`status`) USING BTREE,
	INDEX `ndx_mailqueue_sendafter` (`send_after`) USING BTREE,
	INDEX `ndx_mailqueue_attempts` (`attempts`) USING BTREE
) ENGINE=InnoDB;


-- OPTIONAL: Subscription tables (used in Subscription.php)

CREATE TABLE `subscriptions` (
	`id`  int UNSIGNED NULL AUTO_INCREMENT ,
	`description`  varchar(50) NULL ,
	`amount`  decimal(10,2) NULL ,
	`recur`  enum('weekly','monthly','quarterly','annually') NOT NULL DEFAULT 'monthly' ,
	`date`  date NOT NULL ,
	`renew`  date NULL DEFAULT NULL,
	`fname`  varchar(255) NULL ,
	`lname`  varchar(255) NULL ,
	`address`  varchar(255) NULL ,
	`city`  varchar(255) NULL ,
	`state`  varchar(2) NULL ,
	`zip`  varchar(10) NULL ,
	`email`  varchar(255) NULL ,
	`cc_type`  varchar(10) ,
	`cc_num`  varchar(50) ,
	`cc_exp`  varchar(4) ,
	`guid`  varchar(255) NULL ,
	`count`  int UNSIGNED NOT NULL DEFAULT 1 ,
	`timestamp`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`active`  tinyint UNSIGNED NOT NULL DEFAULT 1 ,
	PRIMARY KEY (`id`)
);

CREATE TABLE `subscription_renewals` (
	`id`  int UNSIGNED NOT NULL AUTO_INCREMENT ,
	`subscription_id`  int UNSIGNED NOT NULL ,
	`timestamp`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
	`amount`  decimal(10,2) NOT NULL ,
	`approved`  tinyint(1) NOT NULL DEFAULT 0 ,
	`message`  varchar(128) NULL ,
	`reference`  varchar(32) NULL ,
	PRIMARY KEY (`id`)
);


-- OPTIONAL: Twitter api cache (used in Twitter.php)

CREATE  TABLE IF NOT EXISTS `api_cache` (
  `hash` CHAR(32) NOT NULL ,
  `contents` LONGTEXT NOT NULL ,
  `time_stamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  PRIMARY KEY (`hash`)
) ENGINE = InnoDB;
