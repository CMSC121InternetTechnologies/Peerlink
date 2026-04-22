/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: PeerLink
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

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
-- Table structure for table `Course_Topics`
--

DROP TABLE IF EXISTS `Course_Topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Course_Topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `topic_name` tinytext NOT NULL,
  PRIMARY KEY (`topic_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `Course_Topics_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `Courses` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Course_Topics`
--

LOCK TABLES `Course_Topics` WRITE;
/*!40000 ALTER TABLE `Course_Topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `Course_Topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Courses`
--

DROP TABLE IF EXISTS `Courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `division_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` tinytext NOT NULL,
  PRIMARY KEY (`course_id`),
  KEY `division_id` (`division_id`),
  CONSTRAINT `Courses_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `Divisions` (`division_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Courses`
--

LOCK TABLES `Courses` WRITE;
/*!40000 ALTER TABLE `Courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `Courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Divisions`
--

DROP TABLE IF EXISTS `Divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Divisions` (
  `division_id` int(11) NOT NULL AUTO_INCREMENT,
  `division_name` tinytext NOT NULL,
  PRIMARY KEY (`division_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Divisions`
--

LOCK TABLES `Divisions` WRITE;
/*!40000 ALTER TABLE `Divisions` DISABLE KEYS */;
INSERT INTO `Divisions` VALUES
(1,'Division of Humanities'),
(2,'Division of Management'),
(3,'Division of Natural Sciences and Mathematics'),
(4,'Division of Social Sciences');
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
  `division_id` int(11) NOT NULL,
  `program_name` tinytext NOT NULL,
  PRIMARY KEY (`program_code`),
  KEY `division_id` (`division_id`),
  CONSTRAINT `Programs_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `Divisions` (`division_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Programs`
--

LOCK TABLES `Programs` WRITE;
/*!40000 ALTER TABLE `Programs` DISABLE KEYS */;
INSERT INTO `Programs` VALUES
('BALit',1,'Bachelor of Arts in Literature'),
('BAMA',1,'Bachelor of Arts in Media Arts'),
('BAPolSci',4,'Bachelor of Arts in Political Science'),
('BAPsych',4,'Bachelor of Arts in Psychology'),
('BSA',2,'Bachelor of Science in Accountancy'),
('BSAM',3,'Bachelor of Science in Applied Mathematics'),
('BSBio',3,'Bachelor of Science in Biology'),
('BSCS',3,'Bachelor of Science in Computer Science'),
('BSEcon',4,'Bachelor of Science in Economics'),
('BSM',2,'Bachelor of Science in Management'),
('MM',2,'Master of Management'),
('MSES',3,'Master of Science in Environmental Science');
/*!40000 ALTER TABLE `Programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Requests`
--

DROP TABLE IF EXISTS `Requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Requests` (
  `request_id` varchar(36) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `tutor_id` varchar(36) DEFAULT NULL,
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
-- Table structure for table `Rooms`
--

DROP TABLE IF EXISTS `Rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_code` varchar(20) NOT NULL,
  `room_name` tinytext NOT NULL,
  `room_type` enum('Physical','Virtual') NOT NULL,
  `capacity` int(11) NOT NULL,
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_code` (`room_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Rooms`
--

LOCK TABLES `Rooms` WRITE;
/*!40000 ALTER TABLE `Rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `Rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Session_Media`
--

DROP TABLE IF EXISTS `Session_Media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Session_Media` (
  `media_id` varchar(36) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `uploader_id` varchar(36) NOT NULL,
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
--

DROP TABLE IF EXISTS `Session_Participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Session_Participants` (
  `participation_id` varchar(36) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
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
--

DROP TABLE IF EXISTS `Session_Reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Session_Reviews` (
  `review_id` varchar(36) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `reviewer_id` varchar(36) NOT NULL,
  `reviewee_id` varchar(36) NOT NULL,
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
  `session_id` varchar(36) NOT NULL,
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
-- Table structure for table `Sessions`
--

DROP TABLE IF EXISTS `Sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Sessions` (
  `session_id` varchar(36) NOT NULL,
  `request_id` varchar(36) NOT NULL,
  `modality` enum('In-Person','Online') NOT NULL,
  `room_id` int(11) NOT NULL,
  `meeting_link` tinytext DEFAULT NULL,
  `scheduled_time` datetime NOT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `request_id_2` (`request_id`),
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
-- Table structure for table `Tutor_Expertise`
--

DROP TABLE IF EXISTS `Tutor_Expertise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tutor_Expertise` (
  `user_id` varchar(36) NOT NULL,
  `topic_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`),
  KEY `idx_expertise_topic` (`topic_id`),
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
-- Table structure for table `Tutor_Profiles`
--

DROP TABLE IF EXISTS `Tutor_Profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tutor_Profiles` (
  `user_id` varchar(36) NOT NULL,
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
-- Table structure for table `User_Photos`
--

DROP TABLE IF EXISTS `User_Photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `User_Photos` (
  `photo_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) NOT NULL,
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
-- Table structure for table `Users`
--

DROP TABLE IF EXISTS `Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Users` (
  `user_id` varchar(36) NOT NULL,
  `email` tinytext NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` char(100) NOT NULL,
  `middle_name` char(100) DEFAULT NULL,
  `last_name` char(100) NOT NULL,
  `contact_number` char(15) DEFAULT NULL,
  `current_year_level` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Users`
--

LOCK TABLES `Users` WRITE;
/*!40000 ALTER TABLE `Users` DISABLE KEYS */;
/*!40000 ALTER TABLE `Users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-23  5:38:26
