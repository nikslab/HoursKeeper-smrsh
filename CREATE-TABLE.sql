CREATE TABLE `timesheet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `client` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `worked` int(11) NOT NULL,
  `rate` double(7,2) DEFAULT NULL,
  `amount` double(7,2) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timesheet_inserted` (`inserted`),
  KEY `timesheet_client` (`client`),
  KEY `timesheet_date` (`date`),
  KEY `timesheet_note` (`note`),
  KEY `timesheet_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
