-- Task Management Platform: MySQL 8 dump (schema + seed data)
-- Import: mysql -u root < database/database.sql   (creates database `task_management`)
-- Demo accounts: admin@example.com / password, user@example.com / password


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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_28_000001_create_tasks_table',1),(5,'2026_09_28_000002_create_task_attachments_table',1),(6,'2026_09_28_000003_create_task_comments_table',1);
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
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint unsigned NOT NULL,
  `mime_type` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_attachments_task_id_foreign` (`task_id`),
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
INSERT INTO `task_comments` VALUES (1,19,1,'Pariatur vel reiciendis cum vel quae enim.','2026-09-28 02:35:41'),(2,8,2,'Harum animi et cum dolorum tempora ratione.','2026-09-28 02:35:41'),(3,15,5,'Ab est adipisci quia animi distinctio tenetur.','2026-09-28 02:35:41'),(4,12,4,'Dolorem minus expedita autem.','2026-09-28 02:35:41'),(5,16,2,'Ullam quia est libero dicta.','2026-09-28 02:35:41'),(6,4,4,'Porro qui nesciunt et debitis optio sint aut.','2026-09-28 02:35:41'),(7,13,1,'Distinctio aut itaque cum voluptatum eos dolor sint.','2026-09-28 02:35:41'),(8,18,6,'Maxime architecto molestias et dolores quibusdam repudiandae reprehenderit quod.','2026-09-28 02:35:41'),(9,15,2,'Neque vel optio voluptatem sit tempora.','2026-09-28 02:35:41'),(10,1,4,'Esse exercitationem perferendis autem fugiat eaque expedita.','2026-09-28 02:35:41'),(11,10,5,'Ratione nisi ipsam nobis expedita ea architecto voluptas.','2026-09-28 02:35:41'),(12,13,6,'Aut asperiores quis ducimus incidunt et quibusdam suscipit.','2026-09-28 02:35:41'),(13,6,1,'Nostrum fuga consequatur exercitationem nihil sed ad minima.','2026-09-28 02:35:41'),(14,9,5,'Reiciendis dolorem omnis aut consequatur.','2026-09-28 02:35:41'),(15,5,4,'Beatae perferendis quas veritatis nihil.','2026-09-28 02:35:41'),(16,20,2,'Minus voluptas architecto aperiam fugit.','2026-09-28 02:35:41'),(17,4,4,'Magnam totam error beatae rerum aliquid est.','2026-09-28 02:35:41'),(18,18,4,'Similique consequatur magni eos animi quia eveniet magnam.','2026-09-28 02:35:41'),(19,5,6,'Voluptatem ipsum reiciendis ut omnis.','2026-09-28 02:35:41'),(20,14,6,'Facere modi placeat nemo quibusdam ut.','2026-09-28 02:35:41'),(21,16,6,'Numquam dicta minus molestiae iusto.','2026-09-28 02:35:41'),(22,10,3,'Consectetur minus quia blanditiis voluptas excepturi ratione.','2026-09-28 02:35:41'),(23,5,5,'Officiis sapiente praesentium cupiditate harum modi aliquid.','2026-09-28 02:35:41'),(24,6,6,'Atque tempora omnis ut quas quod enim.','2026-09-28 02:35:41'),(25,4,1,'Perferendis deserunt eum optio eos.','2026-09-28 02:35:41'),(26,2,5,'Omnis id nihil est sapiente consequuntur tenetur omnis.','2026-09-28 02:35:41'),(27,15,4,'Quia praesentium sit aliquid aliquam qui dolor.','2026-09-28 02:35:41'),(28,5,6,'Voluptas hic repellendus qui quo repudiandae excepturi.','2026-09-28 02:35:41'),(29,12,1,'Non perferendis libero explicabo.','2026-09-28 02:35:41'),(30,6,6,'Omnis recusandae corporis cum deleniti aliquam.','2026-09-28 02:35:41');
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'Aliquam sed voluptatem consectetur',NULL,'in_progress','low',2,3,NULL,'2026-09-28 02:35:41','2026-09-28 03:17:38'),(2,'Quo voluptatem ducimus vel','Aliquam nobis consequatur delectus iusto. Numquam neque omnis ea. Magni accusamus enim voluptas voluptatem nobis veritatis asperiores.','done','medium',3,3,NULL,'2026-09-28 02:35:41','2026-09-28 02:35:41'),(3,'Et omnis nostrum libero','Ut repellat perferendis hic eius error. Maiores non sit qui doloribus amet voluptatem sit. Sunt optio aut minus nam doloribus. Tempore aut facere deleniti necessitatibus.','in_progress','low',NULL,3,'2026-10-12','2026-09-28 02:35:41','2026-09-28 02:35:41'),(4,'Necessitatibus accusamus aut',NULL,'done','medium',6,2,'2026-10-12','2026-09-28 02:35:41','2026-09-28 02:35:41'),(5,'Consequatur suscipit eveniet sed',NULL,'in_progress','medium',6,2,'2026-10-28','2026-09-28 02:35:41','2026-09-28 02:35:41'),(6,'Itaque et earum','Numquam minus porro consectetur omnis. Ea et est error debitis.','in_progress','low',3,5,NULL,'2026-09-28 02:35:41','2026-09-28 02:35:41'),(7,'Minima iure eius nihil',NULL,'done','high',NULL,5,'2026-09-23','2026-09-28 02:35:41','2026-09-28 02:35:41'),(8,'Esse commodi non','Nam sed consequatur aut quidem. Iste dolor neque delectus. Quos esse deleniti dolor quidem eos. Facilis aut mollitia veniam.','todo','low',NULL,4,NULL,'2026-09-28 02:35:41','2026-09-28 02:35:41'),(9,'Quibusdam et et ut','Expedita et omnis enim quae. Error iusto ut delectus delectus sapiente possimus est. Reiciendis laborum asperiores quia illum nesciunt cupiditate.','done','high',4,5,NULL,'2026-09-28 02:35:41','2026-09-28 02:35:41'),(10,'Nostrum accusantium facere voluptas vel','Dolores sequi quis ratione asperiores occaecati qui quia accusamus. Ut id facere voluptatem veniam maxime soluta omnis. Porro vel et explicabo et quo. Et sit veniam similique rerum quis perspiciatis. Recusandae alias voluptatibus quam ratione.','in_progress','medium',NULL,3,'2026-10-27','2026-09-28 02:35:41','2026-09-28 02:35:41'),(11,'Qui odio fugiat assumenda ea','Enim est expedita et harum accusantium ipsam expedita. Nostrum impedit quis omnis nam veniam autem. Minima iusto qui repellendus dicta ipsum delectus.','done','high',2,2,'2026-10-26','2026-09-28 02:35:41','2026-09-28 02:35:41'),(12,'Suscipit aut quaerat maxime','Dignissimos et enim maxime dolorem quos. Perspiciatis saepe rem architecto est perspiciatis sit. Voluptatibus fugit velit aut in tempora.','todo','high',3,5,'2026-10-09','2026-09-28 02:35:41','2026-09-28 02:35:41'),(13,'Rerum veritatis accusamus enim','Placeat tenetur qui expedita pariatur tenetur corporis. Temporibus accusamus voluptatem et illo nihil eligendi quas. Odit sit omnis deleniti voluptates possimus atque. Voluptatem laudantium non aut qui molestiae necessitatibus aut.','done','high',6,5,'2026-10-17','2026-09-28 02:35:41','2026-09-28 02:35:41'),(14,'Repudiandae sint sapiente','Odio id iusto sit sunt ipsum. Suscipit dolore aut quia ratione laboriosam optio. Expedita animi quia vero minus voluptate et qui. Quia quo vel vitae odio qui animi.','done','low',5,6,'2026-10-09','2026-09-28 02:35:41','2026-09-28 02:35:41'),(15,'Molestiae nesciunt voluptatem aut a','Facilis culpa adipisci consequatur. Assumenda sed sit est quas. Aut est exercitationem architecto inventore.','done','medium',1,6,'2026-10-05','2026-09-28 02:35:41','2026-09-28 02:35:41'),(16,'Repellat quo temporibus sed nobis aut',NULL,'done','medium',NULL,5,NULL,'2026-09-28 02:35:41','2026-09-28 02:35:41'),(17,'Modi expedita natus rerum doloribus','Quam quis cum sequi ea. Ut eligendi mollitia molestiae magni culpa. Amet sit qui nam quisquam ratione sit aspernatur. Ab consequatur ut praesentium occaecati quaerat.','in_progress','low',NULL,5,NULL,'2026-09-28 02:35:41','2026-09-28 02:35:41'),(18,'Aspernatur sed animi magni non corrupti',NULL,'in_progress','medium',1,3,NULL,'2026-09-28 02:35:41','2026-09-28 02:35:41'),(19,'Doloribus ut qui quis odio','Voluptatem similique laboriosam est temporibus. Officiis qui hic at ducimus. Dolores laboriosam aut aliquid. Dolor illum cum quae facere omnis sit magnam.','in_progress','low',4,5,'2026-10-11','2026-09-28 02:35:41','2026-09-28 02:35:41'),(20,'Rerum et id','Dicta maiores quo aut quos aliquid nihil ipsam. Quas non culpa illo dolore. Quo rem omnis tempora eos.','done','high',NULL,2,'2026-10-10','2026-09-28 02:35:41','2026-09-28 03:14:19');
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
INSERT INTO `users` VALUES (1,'Admin Demo','admin@example.com','2026-09-28 02:35:40','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','admin','aOsreUyuM7','2026-09-28 02:35:41','2026-09-28 02:35:41'),(2,'User Demo','user@example.com','2026-09-28 02:35:41','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','member','mzrApnGJMa','2026-09-28 02:35:41','2026-09-28 02:35:41'),(3,'Davion Berge','gkihn@example.org','2026-09-28 02:35:41','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','member','qG6NPqMjVY','2026-09-28 02:35:41','2026-09-28 02:35:41'),(4,'Prof. Jovany Robel','anabel.zemlak@example.net','2026-09-28 02:35:41','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','member','yXOsF2vs7e','2026-09-28 02:35:41','2026-09-28 02:35:41'),(5,'Trudie Weber','emmerich.marjorie@example.org','2026-09-28 02:35:41','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','member','lTZDoFwbaT','2026-09-28 02:35:41','2026-09-28 02:35:41'),(6,'Mr. Daryl Sanford I','maximilian.farrell@example.net','2026-09-28 02:35:41','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','member','MmvKNNmMdm','2026-09-28 02:35:41','2026-09-28 02:35:41'),(7,'Wisnu','wisnu@transcosmos.com','2026-09-29 00:00:00','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','member',NULL,'2026-09-29 00:00:00','2026-09-29 00:00:00'),(8,'Wisnu 2','wisnu2@transcosmos.com','2026-09-29 00:00:00','$2y$12$qlPdNDdceTZjpplPBzrMwO5qx0wn9jMXSZqdHvuUvlQi5Bl0QK0Y2','member',NULL,'2026-09-29 00:00:00','2026-09-29 00:00:00');
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

