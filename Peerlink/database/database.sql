/*M!999999\- enable the sandbox mode */
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: PeerLink
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

-- FIX: Create and select the database before creating tables
CREATE DATABASE IF NOT EXISTS `PeerLink`;
USE `PeerLink`;

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
-- Table structure for table `Divisions`
--

DROP TABLE IF EXISTS `Divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Divisions` (
  `division_id` varchar(10) NOT NULL,
  `division_name` text NOT NULL,
  PRIMARY KEY (`division_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Divisions`
--

LOCK TABLES `Divisions` WRITE;
/*!40000 ALTER TABLE `Divisions` DISABLE KEYS */;
INSERT INTO `Divisions` VALUES ('DH','Division of Humanities'),('DM','Division of Management'),('DNSM','Division of Natural Sciences and Mathematics'),('DSS','Division of Social Sciences');
/*!40000 ALTER TABLE `Divisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Programs`
--

DROP TABLE IF EXISTS `Programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Programs` (
  `program_code` varchar(15) NOT NULL,
  `division_id` varchar(10) DEFAULT NULL,
  `program_name` text NOT NULL,
  PRIMARY KEY (`program_code`),
  KEY `division_id` (`division_id`),
  CONSTRAINT `Programs_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `Divisions` (`division_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Programs`
--

LOCK TABLES `Programs` WRITE;
/*!40000 ALTER TABLE `Programs` DISABLE KEYS */;
INSERT INTO `Programs` VALUES ('BALit','DH','Bachelor of Arts in Literature'),('BAMA','DH','Bachelor of Arts in Media Arts'),('BAPolSci','DSS','Bachelor of Arts in Political Science'),('BAPsych','DSS','Bachelor of Arts in Psychology'),('BSA','DM','Bachelor of Science in Accountancy'),('BSAM','DNSM','Bachelor of Science in Applied Mathematics'),('BSBio','DNSM','Bachelor of Science in Biology'),('BSCS','DNSM','Bachelor of Science in Computer Science'),('BSEcon','DSS','Bachelor of Science in Economics'),('BSM','DM','Bachelor of Science in Management'),('MM','DM','Master of Management'),('MSES','DNSM','Master of Science in Environmental Science');
/*!40000 ALTER TABLE `Programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Users`
-- FIX: DEFAULT uuid() -> DEFAULT (uuid())
--

DROP TABLE IF EXISTS `Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Users` (
  `user_id` char(36) NOT NULL DEFAULT (uuid()),
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `current_year_level` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `program_code` varchar(15) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_program` (`program_code`),
  CONSTRAINT `fk_users_program` FOREIGN KEY (`program_code`) REFERENCES `Programs` (`program_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Users`
--

LOCK TABLES `Users` WRITE;
/*!40000 ALTER TABLE `Users` DISABLE KEYS */;
/*!40000 ALTER TABLE `Users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Courses`
--

DROP TABLE IF EXISTS `Courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `division_id` varchar(10) DEFAULT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` text NOT NULL,
  PRIMARY KEY (`course_id`),
  KEY `division_id` (`division_id`),
  CONSTRAINT `Courses_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `Divisions` (`division_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Courses`
--

LOCK TABLES `Courses` WRITE;
/*!40000 ALTER TABLE `Courses` DISABLE KEYS */;
INSERT INTO `Courses` VALUES (1,'DH','ARTS1','Critical Perspectives in the Arts'),(2,'DNSM','BIO162','Tropical Coasts: Biodiversity, Ecology and Conversation'),(3,'DNSM','CMSC10','Introduction to Computer Science'),(4,'DNSM','CMSC11','Fundamentals of Programming 1'),(5,'DNSM','CMSC12','Fundamentals of Programming 2'),(6,'DNSM','CMSC121','Internet Technologies'),(7,'DNSM','CMSC122','Data Structures and Algorithms 1'),(8,'DNSM','CMSC123','Data Structures and Algorithms 2'),(9,'DNSM','CMSC124','Automata and Language Theory'),(10,'DNSM','CMSC125','Operating Systems'),(11,'DNSM','CMSC128','Introduction to Software Engineering'),(12,'DNSM','CMSC13','Survey of Programming Paradigms'),(13,'DNSM','CMSC133','Computer Organization and Architecture with Assembly Programming'),(14,'DNSM','CMSC141','Design and Implementation of Programming Languages'),(15,'DNSM','CMSC154','Fundamentals of Geographic Information Systems'),(16,'DNSM','CMSC155','Fundamentals of Remote Sensing'),(17,'DNSM','CMSC170','Introduction to Artificial Intelligence'),(18,'DNSM','CMSC189','Technical Writing for Computer Science'),(19,'DH','COMM10','Critical Perspective in Communication'),(20,'DSS','ETHICS1','Ethics and Moral Reasoning in Everyday Life'),(21,'DSS','KAS1','Kasaysayan ng Pilipinas'),(22,'DNSM','MATH18','Precalculus Mathematics'),(23,'DNSM','MATH55','Calculus 3'),(24,'DH','PE1','Foundations of Physical Fitness'),(25,'DH','PE2','Ballroom'),(26,'DH','PE2','Ballet'),(27,'DH','PE2','Football'),(28,'DH','PE2','Volleyball'),(29,'DH','PHILARTS1','Philippine Arts and Culture'),(30,'DNSM','PHYSICS71','Elementary Physics I'),(31,'DNSM','SCIENCE10','Probing the Physical World'),(32,'DNSM','SCIENCE11','Living Systems: Concepts and Dynamics'),(33,'DNSM','STAT105','Introduction to Statistical Analysis'),(34,'DNSM','STS1','Science, Technology and Society'),(35,'DH','WIKA1','Wika, Kultura at Lipunan');
/*!40000 ALTER TABLE `Courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Course_Topics`
--

DROP TABLE IF EXISTS `Course_Topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Course_Topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `topic_name` text NOT NULL,
  PRIMARY KEY (`topic_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `Course_Topics_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `Courses` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Course_Topics`
--

LOCK TABLES `Course_Topics` WRITE;
/*!40000 ALTER TABLE `Course_Topics` DISABLE KEYS */;
INSERT INTO `Course_Topics` VALUES (1,6,'Introduction to the World Wide Web (WWW)'),(2,6,'Hypertext Markup Language (HTML) Fundamentals'),(3,6,'Cascading Style Sheets (CSS) Fundamentals'),(4,6,'JavaScript and Document Object Model'),(5,6,'Client-side Web Applications'),(6,6,'Server-side Programming'),(7,6,'Services Oriented Architecture (SOA)'),(8,6,'Web Frameworks (Laravel)'),(9,6,'Web Security Fundamentals');
/*!40000 ALTER TABLE `Course_Topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Rooms`
--

DROP TABLE IF EXISTS `Rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_code` varchar(20) NOT NULL,
  `room_name` text NOT NULL,
  `room_type` enum('Physical','Virtual') NOT NULL,
  `capacity` int(11) NOT NULL,
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_code` (`room_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Rooms`
--

LOCK TABLES `Rooms` WRITE;
/*!40000 ALTER TABLE `Rooms` DISABLE KEYS */;
INSERT INTO `Rooms` VALUES (1,'CSLAB2','Computer Science Laboratory Room 2','Physical',24),(2,'CSLAB1','Computer Science Laboratory 1','Physical',24),(3,'GMeet','Google Meet','Virtual',100),(4,'Zoom','Zoom','Virtual',100);
/*!40000 ALTER TABLE `Rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tutor_Profiles`
--

DROP TABLE IF EXISTS `Tutor_Profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tutor_Profiles` (
  `user_id` char(36) NOT NULL,
  `bio` text DEFAULT NULL,
  `rating_avg` decimal(3,2) DEFAULT 0.00,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `Tutor_Profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tutor_Profiles`
--

LOCK TABLES `Tutor_Profiles` WRITE;
/*!40000 ALTER TABLE `Tutor_Profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `Tutor_Profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tutor_Expertise`
--

DROP TABLE IF EXISTS `Tutor_Expertise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tutor_Expertise` (
  `user_id` char(36) NOT NULL,
  `topic_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`),
  KEY `topic_id` (`topic_id`),
  CONSTRAINT `Tutor_Expertise_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `Tutor_Profiles` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `Tutor_Expertise_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `Course_Topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tutor_Expertise`
--

LOCK TABLES `Tutor_Expertise` WRITE;
/*!40000 ALTER TABLE `Tutor_Expertise` DISABLE KEYS */;
/*!40000 ALTER TABLE `Tutor_Expertise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `User_Photos`
--

DROP TABLE IF EXISTS `User_Photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `User_Photos` (
  `photo_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` char(36) NOT NULL,
  `image_data` longblob NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`photo_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `User_Photos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `User_Photos`
--

LOCK TABLES `User_Photos` WRITE;
/*!40000 ALTER TABLE `User_Photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `User_Photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Requests`
-- FIX: DEFAULT uuid() -> DEFAULT (uuid())
--

DROP TABLE IF EXISTS `Requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Requests` (
  `request_id` char(36) NOT NULL DEFAULT (uuid()),
  `student_id` char(36) NOT NULL,
  `tutor_id` char(36) DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined','Expired') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `student_id` (`student_id`),
  KEY `tutor_id` (`tutor_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `Requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `Users` (`user_id`),
  CONSTRAINT `Requests_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `Users` (`user_id`),
  CONSTRAINT `Requests_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `Courses` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Requests`
--

LOCK TABLES `Requests` WRITE;
/*!40000 ALTER TABLE `Requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `Requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Sessions`
-- FIX: DEFAULT uuid() -> DEFAULT (uuid())
--

DROP TABLE IF EXISTS `Sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Sessions` (
  `session_id` char(36) NOT NULL DEFAULT (uuid()),
  `request_id` char(36) NOT NULL,
  `modality` enum('In-Person','Online') NOT NULL,
  `room_id` int(11) NOT NULL,
  `meeting_link` text DEFAULT NULL,
  `scheduled_time` datetime NOT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `Sessions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `Requests` (`request_id`) ON DELETE CASCADE,
  CONSTRAINT `Sessions_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `Rooms` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Sessions`
--

LOCK TABLES `Sessions` WRITE;
/*!40000 ALTER TABLE `Sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `Sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Session_Media`
-- FIX: DEFAULT uuid() -> DEFAULT (uuid())
--

DROP TABLE IF EXISTS `Session_Media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Session_Media` (
  `media_id` char(36) NOT NULL DEFAULT (uuid()),
  `session_id` char(36) NOT NULL,
  `uploader_id` char(36) NOT NULL,
  `image_data` longblob NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`media_id`),
  KEY `session_id` (`session_id`),
  KEY `uploader_id` (`uploader_id`),
  CONSTRAINT `Session_Media_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `Sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `Session_Media_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `Users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Session_Media`
--

LOCK TABLES `Session_Media` WRITE;
/*!40000 ALTER TABLE `Session_Media` DISABLE KEYS */;
/*!40000 ALTER TABLE `Session_Media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Session_Participants`
-- FIX: DEFAULT uuid() -> DEFAULT (uuid())
--

DROP TABLE IF EXISTS `Session_Participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Session_Participants` (
  `participation_id` char(36) NOT NULL DEFAULT (uuid()),
  `session_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `role` enum('Tutor','Tutee') NOT NULL,
  `has_attended` tinyint(1) DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`participation_id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `Session_Participants_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `Sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `Session_Participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Session_Participants`
--

LOCK TABLES `Session_Participants` WRITE;
/*!40000 ALTER TABLE `Session_Participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `Session_Participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Session_Reviews`
-- FIX: DEFAULT uuid() -> DEFAULT (uuid())
--

DROP TABLE IF EXISTS `Session_Reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Session_Reviews` (
  `review_id` char(36) NOT NULL DEFAULT (uuid()),
  `session_id` char(36) NOT NULL,
  `reviewer_id` char(36) NOT NULL,
  `reviewee_id` char(36) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `session_id` (`session_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `reviewee_id` (`reviewee_id`),
  CONSTRAINT `Session_Reviews_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `Sessions` (`session_id`),
  CONSTRAINT `Session_Reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `Users` (`user_id`),
  CONSTRAINT `Session_Reviews_ibfk_3` FOREIGN KEY (`reviewee_id`) REFERENCES `Users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Session_Reviews`
--

LOCK TABLES `Session_Reviews` WRITE;
/*!40000 ALTER TABLE `Session_Reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `Session_Reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Session_Topics`
--

DROP TABLE IF EXISTS `Session_Topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Session_Topics` (
  `session_id` char(36) NOT NULL,
  `topic_id` int(11) NOT NULL,
  PRIMARY KEY (`session_id`,`topic_id`),
  KEY `topic_id` (`topic_id`),
  CONSTRAINT `Session_Topics_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `Sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `Session_Topics_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `Course_Topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Session_Topics`
--

LOCK TABLES `Session_Topics` WRITE;
/*!40000 ALTER TABLE `Session_Topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `Session_Topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_sessions`
--

DROP TABLE IF EXISTS `auth_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_sessions_user_id_index` (`user_id`),
  KEY `auth_sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_sessions`
--

LOCK TABLES `auth_sessions` WRITE;
/*!40000 ALTER TABLE `auth_sessions` DISABLE KEYS */;
INSERT INTO `auth_sessions` VALUES ('mPReCuDDb0MZlYyFyk8Yg4LPIsxxdcr32fSSW6ar',NULL,'127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0','eyJfdG9rZW4iOiJFOEVFUnByOGdnOGtTMDVMT2x5eXdKVktHZjJhWkNZUlpxbENrWXhKIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDFcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9fQ==',1777808831);
/*!40000 ALTER TABLE `auth_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-jipetilla@up.edu.ph|127.0.0.1','i:1;',1777808827),('laravel-cache-jipetilla@up.edu.ph|127.0.0.1:timer','i:1777808827;',1777808827);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_05_03_113306_create_personal_access_tokens_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-05 13:28:47
