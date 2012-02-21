DROP TABLE IF EXISTS `image`;

CREATE TABLE `image` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`path` varchar(255) NOT NULL,
	`extension` varchar(255) NOT NULL,
	`filename` varchar(255) NOT NULL,
	`byteSize` int(10) unsigned NOT NULL,
	`mimeType` varchar(255) NOT NULL,
	`created` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
);