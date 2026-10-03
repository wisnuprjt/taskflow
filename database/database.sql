-- Task Management Platform: MySQL 8 dump (schema + seed data)
-- Import: mysql -u root < database/database.sql   (creates database `task_management`)
-- Demo accounts (password: password): admin@example.com, wisnu@transcosmos.com (admin); user@example.com, wisnu2@transcosmos.com (member)


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
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `task_management` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `task_management`;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_28_000001_create_tasks_table',1),(5,'2026_09_28_000002_create_task_attachments_table',1),(6,'2026_09_28_000003_create_task_comments_table',1),(7,'2026_10_03_000001_add_thumbnail_path_to_task_attachments_table',1),(8,'2026_10_03_000002_add_scan_status_to_task_attachments_table',1),(9,'2026_10_03_000003_add_version_to_task_attachments_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `task_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` smallint unsigned NOT NULL DEFAULT '1',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL,
  `mime_type` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scan_status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_attachments_task_id_file_name_version_unique` (`task_id`,`file_name`,`version`),
  CONSTRAINT `task_attachments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_attachments` WRITE;
/*!40000 ALTER TABLE `task_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_attachments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `task_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_comments_user_id_foreign` (`user_id`),
  KEY `task_comments_task_id_created_at_index` (`task_id`,`created_at`),
  CONSTRAINT `task_comments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task_comments` WRITE;
/*!40000 ALTER TABLE `task_comments` DISABLE KEYS */;
INSERT INTO `task_comments` VALUES (1,8,2,'Qui eveniet sed ullam et.','2026-09-29 04:54:45'),(2,7,5,'Autem qui rerum maxime cupiditate sint qui quod.','2026-09-29 04:54:45'),(3,7,4,'Unde libero aut earum vero ut error est ratione.','2026-09-29 04:54:45'),(4,14,2,'Voluptatum at id cumque sit nihil.','2026-09-29 04:54:45'),(5,2,8,'Nostrum magnam fugiat dicta quasi rerum.','2026-09-29 04:54:45'),(6,11,2,'Temporibus dolores similique aut cumque eos aut.','2026-09-29 04:54:45'),(7,18,8,'Ipsum accusamus laborum temporibus dolor.','2026-09-29 04:54:45'),(8,10,3,'Non temporibus voluptates praesentium explicabo natus reiciendis eius recusandae.','2026-09-29 04:54:45'),(9,2,1,'Omnis a quo nihil veritatis et unde qui.','2026-09-29 04:54:45'),(10,20,6,'Repellat perspiciatis amet vero ea.','2026-09-29 04:54:45'),(11,16,1,'Temporibus quidem non illo deleniti numquam.','2026-09-29 04:54:45'),(12,17,2,'Dolor non culpa eveniet beatae molestiae.','2026-09-29 04:54:45'),(13,4,6,'Alias sequi assumenda velit rerum harum autem.','2026-09-29 04:54:45'),(14,8,7,'Atque alias suscipit possimus voluptates exercitationem quibusdam.','2026-09-29 04:54:45'),(15,12,6,'Odio est omnis rerum blanditiis placeat ducimus.','2026-09-29 04:54:45'),(16,9,3,'Magni cum aut ut temporibus voluptatem tempora.','2026-09-29 04:54:45'),(17,2,3,'Quo quod voluptas minima provident at fugit sequi.','2026-09-29 04:54:45'),(18,19,6,'Cupiditate quis qui placeat velit itaque est eum.','2026-09-29 04:54:45'),(19,6,8,'Sint consequuntur dicta quia eligendi rerum quis.','2026-09-29 04:54:45'),(20,14,2,'Sequi adipisci beatae voluptatibus qui molestiae illo vel.','2026-09-29 04:54:45'),(21,9,4,'Autem numquam eum temporibus et voluptatem laborum est.','2026-09-29 04:54:45'),(22,15,3,'Nisi architecto corporis amet voluptatem non.','2026-09-29 04:54:45'),(23,20,2,'Ut omnis praesentium quis consequatur commodi.','2026-09-29 04:54:45'),(24,13,1,'Cum veritatis voluptatem illum quis ad atque assumenda.','2026-09-29 04:54:45'),(25,4,7,'Culpa nulla dolorum id dolores nobis aliquid.','2026-09-29 04:54:45'),(26,2,5,'Nulla modi aspernatur non quidem.','2026-09-29 04:54:45'),(27,6,5,'Quos voluptatem non exercitationem magni ipsa quia.','2026-09-29 04:54:45'),(28,10,5,'Sunt ut omnis est quia.','2026-09-29 04:54:45'),(29,6,8,'Ut quaerat debitis voluptas dolore quisquam dolor.','2026-09-29 04:54:45'),(30,20,5,'Quisquam consequatur officia et voluptatem quia qui ut.','2026-09-29 04:54:45');
/*!40000 ALTER TABLE `task_comments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('todo','in_progress','done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todo',
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `assigned_user_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_assigned_user_id_foreign` (`assigned_user_id`),
  KEY `tasks_created_by_foreign` (`created_by`),
  KEY `tasks_created_at_index` (`created_at`),
  KEY `tasks_status_index` (`status`),
  KEY `tasks_priority_index` (`priority`),
  KEY `tasks_due_date_index` (`due_date`),
  CONSTRAINT `tasks_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'Eligendi harum et velit id voluptatem','Commodi consequatur repellendus vitae. Cumque et in aliquam maxime quod et non. Voluptatibus aliquam fugiat placeat necessitatibus eum voluptatem. Qui sunt eos voluptas labore occaecati repellat blanditiis sed.','todo','low',6,7,'2026-10-17','2026-09-29 04:54:45','2026-09-29 04:54:45'),(2,'Sit deleniti vero ratione maiores','In aperiam vel tenetur omnis quibusdam nam. Rerum unde reiciendis facilis. Esse odit officiis suscipit. Tempore officiis esse quia quia.','todo','low',NULL,7,'2026-09-23','2026-09-29 04:54:45','2026-09-29 04:54:45'),(3,'Et quis magnam quis odit natus',NULL,'done','high',3,4,'2026-10-23','2026-09-29 04:54:45','2026-09-29 04:54:45'),(4,'Ut provident et minima','Voluptatem hic porro enim quam asperiores quos. Ut eligendi quam laboriosam. Voluptates molestias ullam et assumenda. Eos doloremque omnis officia blanditiis voluptate nobis sapiente.','in_progress','high',3,6,'2026-10-15','2026-09-29 04:54:45','2026-09-29 04:54:45'),(5,'Eos modi dignissimos ut unde',NULL,'done','high',2,1,'2026-10-26','2026-09-29 04:54:45','2026-09-29 04:54:45'),(6,'Voluptas sunt ratione omnis','Aut illo vero sit nulla. Nisi minus enim sed dolor. Praesentium voluptatem quaerat praesentium dolor. Enim beatae animi quasi numquam quia. Ut et a itaque.','in_progress','high',5,7,NULL,'2026-09-29 04:54:45','2026-09-29 04:54:45'),(7,'Ut qui ea','Quidem pariatur aut qui vel minima. Possimus reiciendis quo amet excepturi. Voluptas vitae quia totam. Reiciendis dignissimos sunt ullam excepturi qui est.','todo','high',3,8,'2026-10-25','2026-09-29 04:54:45','2026-09-29 04:54:45'),(8,'Consequatur ipsum voluptatem',NULL,'in_progress','high',4,3,'2026-10-01','2026-09-29 04:54:45','2026-09-29 04:54:45'),(9,'Dolorum sed autem magni','Nulla aut numquam qui recusandae. Saepe porro voluptas expedita in eaque.','in_progress','medium',5,8,NULL,'2026-09-29 04:54:45','2026-09-29 04:54:45'),(10,'Qui est eaque dolor cumque pariatur','Eligendi molestiae corrupti odit ut. Eum porro dolore aut. Odit commodi numquam vel amet. Provident minus id quibusdam.','in_progress','high',1,2,'2026-10-16','2026-09-29 04:54:45','2026-09-29 04:54:45'),(11,'Blanditiis commodi soluta ut','Veritatis et aspernatur perspiciatis eum placeat optio et quis. Non vero et ullam tempora expedita.','done','medium',1,6,'2026-10-27','2026-09-29 04:54:45','2026-09-29 04:54:45'),(12,'Magnam ratione ex qui debitis sunt','Architecto eos voluptatem ipsa. Ducimus voluptates totam cum quia quo eos. Provident a a pariatur rerum doloribus libero debitis.','todo','high',1,2,'2026-10-02','2026-09-29 04:54:45','2026-09-29 04:54:45'),(13,'Voluptatem sunt dignissimos dolores','Consequatur ipsa voluptas assumenda similique est voluptatum. Eaque ea necessitatibus voluptatem dolore veritatis. Dolor maiores commodi qui molestias aut quaerat.','in_progress','high',1,2,NULL,'2026-09-29 04:54:45','2026-09-29 04:54:45'),(14,'Consequatur id culpa occaecati','Sit sapiente molestiae nam saepe veniam dolorem ut. Alias illo consequatur voluptate vero velit illo molestias. Explicabo nulla repellendus rerum reprehenderit vel voluptatem autem. Eius qui et officia quo mollitia necessitatibus.','in_progress','high',6,4,'2026-10-11','2026-09-29 04:54:45','2026-09-29 04:54:45'),(15,'Placeat explicabo repudiandae distinctio est eos','In libero debitis fuga et consequatur a. Sit aperiam est sed veritatis adipisci aliquid. Delectus asperiores quo inventore sequi tempore veniam itaque. Quod suscipit delectus nisi dolores autem quibusdam inventore.','todo','high',7,5,'2026-10-16','2026-09-29 04:54:45','2026-09-29 04:54:45'),(16,'Possimus est ut repellendus facere','Nesciunt voluptatem non quia voluptates id qui. Ratione incidunt quas odit ut aut dolorem. Itaque non iusto voluptatem.','done','low',1,5,NULL,'2026-09-29 04:54:45','2026-09-29 04:54:45'),(17,'Voluptates ea impedit','Ut sit ducimus non maxime ullam. Vel et vero vel fuga quis. Id officiis est voluptatum numquam.','todo','high',NULL,8,'2026-10-14','2026-09-29 04:54:45','2026-09-29 04:54:45'),(18,'Et repellendus tempore nisi aut dolores','Laudantium reiciendis nihil ipsum dicta eum. Asperiores voluptatem voluptates quasi saepe facilis voluptatum cumque. Numquam quisquam eveniet et consequatur minus neque inventore. Est eum nesciunt libero et molestiae.','in_progress','medium',3,2,'2026-10-02','2026-09-29 04:54:45','2026-09-29 04:54:45'),(19,'Qui dolorem non sint','Nisi quis dignissimos dolorum ipsum quo. Aut qui tenetur occaecati nemo adipisci fugiat fuga aliquam. Non sapiente consequatur eligendi optio quia eos unde.','todo','low',2,7,'2026-10-16','2026-09-29 04:54:45','2026-09-29 04:54:45'),(20,'Ratione non omnis aut','Veniam iste est nulla. Et sapiente ea iste et dicta dicta temporibus dicta. Dolor aut voluptatem ut in ipsa. Rem cumque ut et impedit hic earum.','todo','high',5,4,NULL,'2026-09-29 04:54:45','2026-09-29 04:54:45'),(33,'Testing Image Thumbnail','Upload JPG/PNG/WebP, termasuk satu foto kamera resolusi besar','in_progress','high',4,3,'2026-10-31','2026-10-03 06:34:00','2026-10-03 06:37:10'),(34,'Testing Docs (PDF, Word, Excel, TXT)','Upload .pdf, .docx, .xlsx, .txt','in_progress','high',4,3,'2026-10-31','2026-10-03 06:35:39','2026-10-03 06:35:39'),(35,'Testing Invalid File Type','Upload .exe, .zip, atau .php','in_progress','high',4,3,'2026-10-31','2026-10-03 06:37:33','2026-10-03 06:37:33'),(36,'Testing Virus Scanner','Upload virus.txt berisi SIMULATED-VIRUS-SIGNATURE','in_progress','high',4,3,'2026-10-31','2026-10-03 06:40:02','2026-10-03 06:40:02'),(37,'Testing Double Extension','Upload catatan.exe.txt (isi bebas), otomatis Quarantined','todo','high',4,3,'2026-10-31','2026-10-03 06:41:29','2026-10-03 06:42:34'),(38,'Testing Task Attachment Video <20MB','Upload MP4/WebM','in_progress','high',4,3,'2026-10-31','2026-10-03 06:43:12','2026-10-03 06:45:41'),(39,'Testing Task Attachment Video >50MB','Upload video 50–500 MB, Progress bar jalan sampai 100% (chunked), lolos scan, folder storage/app/private/chunks/ kosong lagi','in_progress','high',4,3,'2026-10-31','2026-10-03 06:45:31','2026-10-03 06:45:31'),(40,'Testing File >500MB','Upload file di atas 500 MB, Ditolak: \"File exceeds the 500 MB limit\"','in_progress','high',4,3,'2026-10-31','2026-10-03 06:46:20','2026-10-03 06:46:20'),(41,'Testing Task Attachment Version','setiap adanya revisi, pada file dengan judul dan format yang sama, yang tampil akan tetap 1, namun di database tercatat 3 (terbaru-terlama)','todo','high',4,3,'2026-10-31','2026-10-03 06:48:08','2026-10-03 06:48:08');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Demo','admin@example.com','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','admin',NULL,'2026-09-29 04:54:44','2026-09-29 04:54:44'),(2,'User Demo','user@example.com','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','member',NULL,'2026-09-29 04:54:44','2026-09-29 04:54:44'),(3,'Wisnu','wisnu@transcosmos.com','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','admin',NULL,'2026-09-29 04:54:44','2026-10-03 03:44:54'),(4,'Wisnu 2','wisnu2@transcosmos.com','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','member',NULL,'2026-09-29 04:54:44','2026-10-03 03:44:28'),(5,'Breana Harber','lindsey09@example.net','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','member',NULL,'2026-09-29 04:54:44','2026-09-29 04:54:44'),(6,'Eldridge Herzog','egreenholt@example.org','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','member',NULL,'2026-09-29 04:54:44','2026-09-29 04:54:44'),(7,'Dr. Osbaldo Lueilwitz','wharvey@example.org','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','member',NULL,'2026-09-29 04:54:44','2026-09-29 04:54:44'),(8,'Karson Miller DDS','serenity55@example.net','2026-09-29 04:54:44','$2y$12$2sGjhAORPtcKBJexT5etLe54hsZTrO5WzRd/OajSZ4IJw5EO7SyAa','member',NULL,'2026-09-29 04:54:44','2026-09-29 04:54:44');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

