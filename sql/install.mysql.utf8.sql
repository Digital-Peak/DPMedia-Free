CREATE TABLE IF NOT EXISTS `#__dppermissions` (
  `path` varchar(255) NOT NULL,
  `group_id` int unsigned NOT NULL,
  KEY `idx_path` (`path`),
  KEY `idx_group` (`group_id`),
  KEY `idx_path_group` (`path`, `group_id`)
);
