-- MySQL dump 10.19  Distrib 10.3.32-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 192.168.0.103    Database: smalltown
-- ------------------------------------------------------
-- Server version	10.6.5-MariaDB

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

--
-- Table structure for table `friend`
--

DROP TABLE IF EXISTS `friend`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `friend` (
  `uId1` int(11) DEFAULT NULL,
  `uId2` int(11) DEFAULT NULL,
  `relation` set('block','follow','friend','request') DEFAULT NULL,
  UNIQUE KEY `uId1_2` (`uId1`,`uId2`),
  KEY `uId1` (`uId1`),
  KEY `uId2` (`uId2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friend`
--

LOCK TABLES `friend` WRITE;
/*!40000 ALTER TABLE `friend` DISABLE KEYS */;
INSERT INTO `friend` VALUES (7,2,'friend'),(2,7,'friend');
/*!40000 ALTER TABLE `friend` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post` (
  `pId` int(11) NOT NULL AUTO_INCREMENT,
  `ppId` int(11) DEFAULT NULL,
  `uId` int(11) NOT NULL,
  `ruId` int(11) DEFAULT NULL,
  `rpId` int(11) DEFAULT NULL,
  `pTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `pTxt` varchar(255) DEFAULT NULL,
  `emotion` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`pId`),
  KEY `uId` (`uId`),
  KEY `rpId` (`rpId`),
  KEY `ruId` (`ruId`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post`
--

LOCK TABLES `post` WRITE;
/*!40000 ALTER TABLE `post` DISABLE KEYS */;
INSERT INTO `post` VALUES (3,NULL,7,7,3,'2021-12-29 22:25:58','Hello!',''),(4,3,7,NULL,3,'2021-12-29 22:28:32','I am fine!',''),(5,3,7,NULL,3,'2021-12-29 22:30:55','Get it?',''),(6,3,7,NULL,3,'2021-12-29 22:33:55','Got it!',''),(7,6,7,NULL,3,'2021-12-29 22:35:20','Good!',''),(8,NULL,7,2,8,'2022-01-01 23:45:44','add to user 2 from user 7','');
/*!40000 ALTER TABLE `post` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `uId` int(11) NOT NULL,
  `sHash` varchar(128) DEFAULT NULL,
  `sTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`uId`),
  KEY `sHash` (`sHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
INSERT INTO `session` VALUES (5,'a8bee519b4f7aa901c1ad838b780c38e0fd99e61','2021-12-21 21:49:17'),(7,'072df4b72da0f7285d2fb714c8ef216e55463c10','2022-01-02 01:05:26');
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `uId` int(11) NOT NULL AUTO_INCREMENT,
  `uName` varchar(32) NOT NULL,
  `uEmail` varchar(128) NOT NULL,
  `uLastName` varchar(32) DEFAULT NULL,
  `uFirstName` varchar(32) DEFAULT NULL,
  `uPassword` char(128) DEFAULT NULL,
  `uYear` int(11) DEFAULT NULL,
  `uCourse` char(32) DEFAULT NULL,
  `uImageId` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`uId`),
  UNIQUE KEY `uEmail` (`uEmail`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin','info@smalltown.com','admin','admin',NULL,0,'ALL',NULL),(2,'leo','leonard.ilanius@onnestadsfolkhogskola.se','Ilanius','Leonard',NULL,2022,'ITTEKNIK',NULL),(7,'nada','leonard.ilanius@gmail.com',NULL,NULL,'$2y$10$cLv1SYnm2qZ29q6xkprAYeF/ezrsHs3sZ9Fd.asXx3Pq7ruHY22D2',1234,NULL,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-01-02  2:06:47
