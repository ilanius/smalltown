
DROP TABLE IF EXISTS `feedUpdate`;

CREATE TABLE `feedUpdate` (
  `pId` int(11) NOT NULL DEFAULT 0,
  `ppId` int(11) DEFAULT NULL,
  `uId` int(11) NOT NULL,
  `ruId` int(11) DEFAULT NULL,
  `rpId` int(11) DEFAULT NULL,
  `pTime` timestamp(3) NOT NULL DEFAULT current_timestamp(),
  `pTxt` varchar(255) DEFAULT NULL,
  `emotion` varchar(255) NOT NULL DEFAULT '',
  `action` enum('add','mod','del') DEFAULT 'del',
  KEY `pId` (`pId`),
  KEY `pTime` (`pTime`),
  KEY `rpId` (`rpId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
