-- MySQL dump 10.13  Distrib 8.0.28, for Linux (x86_64)
--
-- Host: localhost    Database: smalltown
-- ------------------------------------------------------
-- Server version	8.0.28-0ubuntu0.20.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `feedUpdate`
--

DROP TABLE IF EXISTS `feedUpdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedUpdate` (
  `pId` int NOT NULL DEFAULT '0',
  `ppId` int DEFAULT NULL,
  `uId` int NOT NULL,
  `ruId` int DEFAULT NULL,
  `rpId` int DEFAULT NULL,
  `pTime` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `pTxt` varchar(255) DEFAULT NULL,
  `emotion` varchar(255) NOT NULL DEFAULT '',
  `action` enum('add','mod','del') DEFAULT 'del',
  KEY `pId` (`pId`),
  KEY `pTime` (`pTime`),
  KEY `rpId` (`rpId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedUpdate`
--

LOCK TABLES `feedUpdate` WRITE;
/*!40000 ALTER TABLE `feedUpdate` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedUpdate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friend`
--

DROP TABLE IF EXISTS `friend`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `friend` (
  `uId1` int DEFAULT NULL,
  `uId2` int DEFAULT NULL,
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
/*!40000 ALTER TABLE `friend` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post` (
  `pId` int NOT NULL AUTO_INCREMENT,
  `ppId` int DEFAULT NULL,
  `uId` int NOT NULL,
  `ruId` int DEFAULT NULL,
  `rpId` int DEFAULT NULL,
  `pTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pTxt` varchar(255) DEFAULT NULL,
  `emotion` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`pId`),
  KEY `uId` (`uId`),
  KEY `rpId` (`rpId`),
  KEY `ruId` (`ruId`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post`
--

LOCK TABLES `post` WRITE;
/*!40000 ALTER TABLE `post` DISABLE KEYS */;
/*!40000 ALTER TABLE `post` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session` (
  `uId` int NOT NULL,
  `sHash` varchar(128) DEFAULT NULL,
  `sTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uId`),
  KEY `sHash` (`sHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
INSERT INTO `session` VALUES (16,'f39f62e6dbf4e4087c3db9b6090729244f7884fa','2022-02-27 20:46:09');
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `uId` int NOT NULL AUTO_INCREMENT,
  `uName` varchar(32) NOT NULL,
  `uEmail` varchar(128) NOT NULL,
  `uLastName` varchar(32) DEFAULT NULL,
  `uFirstName` varchar(32) DEFAULT NULL,
  `uPassword` char(128) DEFAULT NULL,
  `uYear` int DEFAULT NULL,
  `uCourse` char(32) DEFAULT NULL,
  `uImageId` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`uId`),
  UNIQUE KEY `uEmail` (`uEmail`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (15,'nada','leonard.ilanius@gmail.com','ilanius','leonard','$2y$10$u6yIrz3TOMAygZEqZhalXuX/gLJ.EqBK8BN6eOeqXd75i7WryASK2',1234,NULL,'profileDefaultImage.png'),(16,'nada','frank@gmail.com','Sinatra','Frank','$2y$10$29d///1iVlb5dlz0RndFneU5mmm4sAxSh9qr1FoWZ07K0ec6XSiYy',1234,NULL,'profileDefaultImage.png');
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

-- Dump completed on 2022-02-27 21:46:46
