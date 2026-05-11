-- MySQL dump 10.13  Distrib 8.2.0, for Win64 (x86_64)
--
-- Host: localhost    Database: ocrs_db
-- ------------------------------------------------------
-- Server version	8.2.0

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super','department') COLLATE utf8mb4_unicode_ci DEFAULT 'super',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (7,'Harjinder Singh','h@gmail.com','$2y$10$b/ZrPjpc.CHKFh4oMHTABuTuLcgBrvofk0tVgIfOBpRN5V3v.VBfO','super','2025-08-25 04:11:29'),(12,'New Admin','n@gmail.com','$2y$10$S0ahQuT45whZwpYN/wbvK.Fae5Fgk5gahs5iHTf4sDpPt1h5fBQnS','super','2025-09-27 04:01:19'),(13,'xyz','x@gmail.com','$2y$10$vCQXfSn6bmrKbR2/77n88O2jz45MnNw1iWeErzu11qOq4CFIFwm1m','super','2025-10-17 06:28:45'),(14,'Ravi Kumar Gupta','r@gmail.com','$2y$10$pxu/l.ovkxXhA3H3icilrOd/Ob.FOpHZNO05c4mfPjleH9VXCgbGO','super','2025-10-23 12:07:31');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_records`
--

DROP TABLE IF EXISTS `course_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credits` int NOT NULL,
  `seat_limit` int NOT NULL DEFAULT '30',
  `minimum_percentage` decimal(5,2) DEFAULT '60.00',
  `required_qualification` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Higher Secondary',
  `required_subjects` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_records`
--

LOCK TABLES `course_records` WRITE;
/*!40000 ALTER TABLE `course_records` DISABLE KEYS */;
INSERT INTO `course_records` VALUES (1,'Data Structure ','1.4','BCA','1st',5,30,60.00,'Higher Secondary','Maths'),(2,'Computer Graphics','5.1','BCA','5th',10,30,60.00,'Higher Secondary',NULL),(3,'Mechnical Physics','6.1','Bsc','3rd',10,30,60.00,'Higher Secondary',NULL),(4,'Scientific Engineering ','2.1','Bsc','3rd ',6,30,60.00,'Higher Secondary',NULL),(21,'Mechanical Engineering ','7.4','B. Tech','1st',10,10,60.00,'Higher Secondary','Physics, Maths'),(8,'Accounts','5.2','Bcom','4th',10,30,60.00,'Higher Secondary',NULL),(10,'Statistics','5.5','Bsc','5th',10,30,60.00,'Higher Secondary',NULL),(27,'BBA','8.3','Bcom','3rd ',10,10,60.00,'Higher Secondary',NULL),(14,'Buisness Administration','6.2','MBA','1st',10,30,60.00,'Higher Secondary',NULL),(26,'Geography','5.8','BA','4th',10,30,60.00,'Higher Secondary','HS Geography');
/*!40000 ALTER TABLE `course_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_code` (`department_code`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (3,'BCA','001'),(4,'Bsc','002'),(5,'BA','003'),(6,'Bcom','004'),(7,'MCA','005'),(8,'MBA','006'),(10,'MSW','007'),(11,'B. Tech','008');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollment_history`
--

DROP TABLE IF EXISTS `enrollment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrollment_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `course_id` int NOT NULL,
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_year` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completion_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollment_history`
--

LOCK TABLES `enrollment_history` WRITE;
/*!40000 ALTER TABLE `enrollment_history` DISABLE KEYS */;
INSERT INTO `enrollment_history` VALUES (1,17,21,NULL,'2027','2025-09-28 07:48:57'),(2,38,24,NULL,'2026','2025-10-17 06:33:24'),(3,17,1,NULL,'2026','2025-10-17 06:33:24'),(4,39,1,NULL,'2026','2025-10-17 06:33:24');
/*!40000 ALTER TABLE `enrollment_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `course_id` int NOT NULL,
  `enroll_date` date NOT NULL,
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (1,40,1,'2025-10-23',NULL,'Approved'),(9,17,4,'2025-11-16',NULL,'Pending'),(7,43,3,'2025-11-13',NULL,'Approved');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('success','error','info') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id_index` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,42,'Congratulations! Your enrollment for \'Data Structure \' has been approved.','success',1,'2025-11-13 11:16:15'),(2,42,'Your enrollment application for \'Scientific Engineering \' was not approved. Please contact an administrator for details.','error',1,'2025-11-13 13:04:52'),(3,43,'Congratulations! Your enrollment for \'Mechnical Physics\' has been approved.','success',1,'2025-11-13 13:15:35'),(4,42,'Congratulations! Your enrollment for \'Data Structure \' has been approved.','success',1,'2025-11-16 05:53:51');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `semesters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `semester_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semesters`
--

LOCK TABLES `semesters` WRITE;
/*!40000 ALTER TABLE `semesters` DISABLE KEYS */;
/*!40000 ALTER TABLE `semesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reg_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_completed` tinyint(1) DEFAULT '0',
  `previous_qualification` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_board` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_percentage` decimal(5,2) DEFAULT NULL,
  `previous_institution` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_subjects` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (17,'Gyandeep Dehingia','REG2025-0001','','g@gmail.com','$2y$10$Wl6JM00RzLIDU4LPsBMQLuc0VhfFnmtSQpTLfi77/j.JApKUoNp1.','2025-09-25 10:15:41',NULL,NULL,'8453992833','Bokuloni Chariali, Duliajan, Dibrugarh, Assam, 786191',20,'uploads/profile_pics/1759723167_course3.png',1,'Higher Secondary','AHSEC',89.00,'OIHSS','None'),(42,'Jhon Wick','REG2025-0005','Data Structure ','jhon@gmail.com','$2y$10$9v.8IogtPFZiApJ894kk6eOFN3Y2DK0hMzLnzMJ5APWyc5bZFGLuW','2025-11-13 09:44:28','BCA','1st','8453992833','Dibrugarh, Assam',23,'uploads/profile_pics/1763029723_aalu.png',1,'Higher Secondary','AHSEC',85.00,'OIHSS','maths'),(37,'Uday','REG2025-0003','','u@gmail.com','$2y$10$DEqfVixjQlQbMK1v46F72uyDPMAkmNkmwvWJw7uLrDvn6xrwoqSPu','2025-10-08 15:42:57',NULL,NULL,'54654546','ferferfer',21,'uploads/profile_pics/1759938231_icedcombo.jpg',1,NULL,NULL,NULL,NULL,NULL),(38,'piyakhi Borah',NULL,'','p@gmail.com','$2y$10$Skofx1p6bkO8fbEKCLQi7.uxbs3PXtVSaZGHVQdZfj5np1U87HA6W','2025-10-15 14:30:02',NULL,NULL,'9395169883','Rajgarh',20,'uploads/profile_pics/1760538704_anime.webp',1,'12th Science Stream (PCMC)','AHSEC',79.00,'OIHSS',NULL),(39,'Ravi Kumar Gupta','REG2025-0004','','h@gmail.com','$2y$10$K.KLlucK8emGtjT2W0xC0.t0TIgDzTuTr2pEf/6cLd.s8L6wytVD2','2025-10-17 06:30:28',NULL,NULL,'46464','vvhgchf',21,'uploads/profile_pics/1760682674_anime.webp',1,'12th Science Stream (PCMC)','AHSEC',71.00,'OIHSS',NULL),(40,'Raj Kr','REG2025-0005','Data Structure ','rk@gmail.com','$2y$10$jp/7.HXG3HQI4ODAa3tDNOPf.ElmuvGOeAEZyrv2dtBYNlBn3ofPC','2025-10-23 12:13:44','BCA','1st','08453992833','bbzdfbb',21,'uploads/profile_pics/1761221761_hero.avif',1,'12th Science Stream (PCMC)','AHSEC',87.00,'OIHSS',NULL),(41,'Ravi Kumar Gupta',NULL,'','rk2@gmail.com','$2y$10$mnwgbxdsWtHcTtCUdq5KyeQ4nFXlCVEver3liLm23Zc8nIkAi2qsW','2025-10-30 05:10:20',NULL,NULL,'8453992833','Dibruga',21,NULL,1,NULL,NULL,NULL,NULL,NULL),(43,'Rajdeep Gogoi','REG2025-0006','Mechnical Physics','raj@gmail.com','$2y$10$NkcqUAwx0VPdtV.ZBeRLtOuigSUE6Q0aZC.5IQ1bn3nPGuVCksKzu','2025-11-13 13:13:02','Bsc','3rd','46546166854','Bahorsuk',20,'uploads/profile_pics/1763039618_aalu.png',1,'Higher Secondary','AHSEC',88.00,'OIHSS','None'),(44,'Shyam',NULL,'','s@gmail.com','$2y$10$h1El3gX2EL54rmJTUQjimuXRWEBUSh5EBKaDmwTghVw1aTODc3m1e','2025-11-16 05:48:41',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'open_registrations','1'),(2,'maintenance_mode','0'),(3,'email_alerts','1'),(4,'institution_name','XYZ'),(5,'system_name','Course Connect'),(6,'institution_address','Dibrugarh, Assam, 786001'),(7,'institution_contact','courseconnect@org.com');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-11 21:03:34
