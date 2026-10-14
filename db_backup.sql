-- MySQL dump 10.13  Distrib 8.0.39, for Linux (x86_64)
--
-- Host: localhost    Database: agrosys
-- ------------------------------------------------------
-- Server version	8.0.39-0ubuntu0.22.04.1

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
-- Table structure for table `abono_cuentas`
--

DROP TABLE IF EXISTS `abono_cuentas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abono_cuentas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cantidad_abonada` double(8,2) NOT NULL,
  `cuenta_pagada` tinyint(1) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `id_usuario` bigint unsigned NOT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `abono_cuentas_id_usuario_foreign` (`id_usuario`),
  KEY `abono_cuentas_id_cliente_foreign` (`id_cliente`),
  KEY `abono_cuentas_id_sucursal_foreign` (`id_sucursal`),
  CONSTRAINT `abono_cuentas_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `abono_cuentas_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `abono_cuentas_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abono_cuentas`
--

LOCK TABLES `abono_cuentas` WRITE;
/*!40000 ALTER TABLE `abono_cuentas` DISABLE KEYS */;
INSERT INTO `abono_cuentas` VALUES (1,1205.00,1,0,2,1,'2024-08-30 20:29:49','2024-08-30 20:29:49',4),(2,1500.00,0,0,3,2,'2024-08-30 20:38:03','2024-08-30 20:41:03',4),(3,1000.00,1,0,3,2,'2024-08-30 20:39:35','2024-08-30 20:41:03',4),(4,900.00,0,0,3,2,'2024-08-30 20:40:22','2024-08-30 20:41:03',4),(5,1420.00,1,0,3,2,'2024-08-30 20:41:03','2024-08-30 20:41:03',4);
/*!40000 ALTER TABLE `abono_cuentas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alta_inventarios`
--

DROP TABLE IF EXISTS `alta_inventarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alta_inventarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cantidad_actual` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad_nueva` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_producto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alta_inventarios_id_sucursal_foreign` (`id_sucursal`),
  CONSTRAINT `alta_inventarios_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alta_inventarios`
--

LOCK TABLES `alta_inventarios` WRITE;
/*!40000 ALTER TABLE `alta_inventarios` DISABLE KEYS */;
INSERT INTO `alta_inventarios` VALUES (1,'0','0','2','1',4,'2024-08-30 19:36:51','2024-08-30 19:36:51'),(2,'0','12','2','1',4,'2024-08-30 19:59:13','2024-08-30 19:59:13'),(3,'0','0','2','2',4,'2024-08-30 20:05:18','2024-08-30 20:05:18'),(4,'12','11','2','1',4,'2024-08-30 20:29:49','2024-08-30 20:29:49'),(5,'11','8','3','1',4,'2024-08-30 20:38:03','2024-08-30 20:38:03'),(6,'8','7','3','1',4,'2024-08-30 20:40:22','2024-08-30 20:40:22'),(7,'0','12','2','2',4,'2024-08-30 20:43:27','2024-08-30 20:43:27'),(8,'7','17','2','1',4,'2024-08-30 20:43:27','2024-08-30 20:43:27');
/*!40000 ALTER TABLE `alta_inventarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_clasificacions`
--

DROP TABLE IF EXISTS `cat_clasificacions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_clasificacions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criterio` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_clasificacions`
--

LOCK TABLES `cat_clasificacions` WRITE;
/*!40000 ALTER TABLE `cat_clasificacions` DISABLE KEYS */;
INSERT INTO `cat_clasificacions` VALUES (1,'Herbicida','Función','2024-08-28 18:10:12','2024-08-28 18:10:12'),(2,'Fungicida','Función','2024-08-28 18:10:23','2024-08-28 18:10:23'),(4,'Acaricida','Función','2024-08-28 18:11:55','2024-08-28 18:11:55'),(5,'Insecticidas','Función','2024-08-28 18:13:18','2024-08-28 18:13:18'),(6,'Nematicidas','Función','2024-08-28 18:13:47','2024-08-28 18:13:47'),(7,'Rodenticidas','Función','2024-08-28 18:14:02','2024-08-28 18:14:02'),(8,'Fertilizantes','Función','2024-08-28 18:14:15','2024-08-28 18:14:15'),(9,'Fitorreguladores','Función','2024-08-28 18:14:30','2024-08-28 18:14:30'),(10,'Bactericida','Función','2024-08-30 20:00:12','2024-08-30 20:00:12');
/*!40000 ALTER TABLE `cat_clasificacions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_enfermedades`
--

DROP TABLE IF EXISTS `cat_enfermedades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_enfermedades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cat_enfermedades_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_enfermedades`
--

LOCK TABLES `cat_enfermedades` WRITE;
/*!40000 ALTER TABLE `cat_enfermedades` DISABLE KEYS */;
INSERT INTO `cat_enfermedades` VALUES (1,'Botrytis','La podredumbre de Botrytis es una infección provocada por hongos en la que, con el tiempo, se puede apreciar al hongo que la causa y no solo los síntomas de la enfermedad latente, ya que las esporas de hongos que parecen un polvo gris se desarrollan sobre el tejido muerto o agonizante de las plantas y se les puede observar fácilmente.','2024-08-28 18:42:59','2024-08-28 18:42:59'),(2,'Cenicilla','Las cenicillas, también llamadas cenicillas polvorientas o mildiu polvorientos, son causadas por un grupo de hongos diversos, complejos en su forma, en sus estructuras reproductivas, rango de hospedantes y distribución geográfica y producen ascocarpos esféricos llamados casmotecios (previamente denominados cleistotecios) así como conidióforos e hifas hialinas, septadas uninucleadas, y conidios que al desarrollarse en grandes cantidades sobre las superficies afectadas de la planta forman un polvillo blanco a manera de ceniza, lo que las hace fáciles de reconocer.','2024-08-28 18:44:52','2024-08-28 18:44:52'),(3,'Peronospora','Peronospora sparsa es un patógeno biótrofo o parásito obligado que forma parte de los Oomycetes, los cuales son organismos miceliares semejantes a los hongos, que se conocen comúnmente como mohos acuáticos e incluyen saprófitos y patógenos de plantas, insectos, crustáceos, peces, animales vertebrados y de otros microorganismos. Uno de los aspectos más estudiados en los Oomycetes en los últimos años ha sido sus relaciones filogenéticas intra e intergenéricas','2024-08-28 18:46:29','2024-08-28 18:46:29'),(4,'Minador de hojas','Se trata de un díptero cuyas larvas se alimentan del parénquima foliar, teniendo preferencia por el haz de las hojas y dejando a su paso galerías serpenteantes sobre las mismas. Finalmente, estas galerías se necrosan. La hembra adulta también provoca daños en las hojas al depositar sus huevos sobre las mismas, dando lugar a puntos blanquecinos.','2024-08-28 19:38:35','2024-08-28 19:38:35'),(5,'Trips','Esta plaga habita principalmente sobre botones florales y hojas jóvenes, y más raramente sobre hojas senescentes. Los síntomas que se presentan son manchas de aspecto plateado-plomizo rodeadas de motitas negras que se corresponden a sus excrementos.','2024-08-28 19:38:56','2024-08-28 19:38:56'),(6,'Mosca blanca','Se trata de una plaga que provoca daños en los tejidos, preferiblemente jóvenes, al succionar la savia para su alimentación, y también al ovipositar. Además, originan daños indirectos al segregar una sustancia azucarada donde se instala el hongo negrilla.','2024-08-28 19:39:20','2024-08-28 19:39:20'),(7,'Araña roja','Se presenta principalmente si el ambiente es seco. Los síntomas que aparecen son unos puntitos de color amarillo en el haz de las hojas y a lo largo de los nervios principales. Posteriormente, estas punteaduras se tornan de color marrón y se abarquillan, obteniendo un aspecto polvoriento. Finalmente, dichas hojas se desecan y caen. Es frecuente encontrar finas telarañas en el envés de las hojas afectadas.','2024-08-28 19:39:45','2024-08-28 19:39:45'),(8,'Ácaros','Son conocidos como ácaros blancos. Éstos originan daños al realizar sus puestas sobre las hojas jóvenes del centro de la planta y en los botones florales. Las larvas provocan deformaciones en las lígulas, torsiones de la flor y reducción de su desarrollo perimetral (el grado de deformación depende de la densidad poblacional). En las hojas pueden ocasionar deformaciones de los bordes del limbo, plegamiento hacia el haz o el envés de la superficie foliar, engrosamiento del limbo y carácter quebradizo del mismo.','2024-08-28 19:40:03','2024-08-28 19:40:03'),(9,'Orugas','Estas diferentes especies de noctuidos aparecen con mayor frecuencia durante los meses cálidos, coincidiendo con el aumento de las poblaciones de estos insectos. Las larvas de estas plagas son muy voraces, ocasionando importantes daños sobre las hojas de la planta como consecuencia de su alimentación. En caso de fuertes infecciones, éstos pueden provocar daños también en las flores.','2024-08-28 19:40:25','2024-08-28 19:40:25'),(10,'Verticilosis','Verticillium dahliae es un hongo propio de épocas invernales. La verticilosis es una enfermedad que provoca la obstrucción de los nervios de las hojas. Los síntomas se manifiestan como un marchitamiento general de la planta, acompañado del amarillamiento progresivo de sus hojas y la decoloración de sus nervios, los cuales terminan por secarse. Finalmente, la planta muere.','2024-08-28 19:40:52','2024-08-28 19:40:52'),(11,'Podredumbre gris','Este hongo necesita tejidos heridos o senescentes para afectar a la planta, así como humedad ambiental y temperatura elevada. Su desarrollo se inicia sobre material senescente y en descomposición. De éste se traslada a las hojas y flores en donde produce los daños más importantes. Puede causar podredumbre de las plántulas (damping-off), marchitamiento de hojas y flores y podredumbre de la corona. En las hojas pueden aparecer lesiones marrones y en los pétalos de las flores manchas, necrosis de las puntas o marchitamiento completo. Cuando afecta a las lígulas, se denota la formación de pequeñas manchas grisáceas sobre su superficie, afectando a la posterior comercialización de estas flores, ya que el hongo continúa su evolución.','2024-08-28 19:41:28','2024-08-28 19:41:28'),(12,'Sclerotinia sclerotiorum:','Este hongo produce podredumbre blanda en la base de las hojas y en el cuello de las plantas. Se distingue por un abundante micelio algodonoso, sobre el que aparecen posteriormente nódulos negros que corresponden a los esclerocios.','2024-08-28 19:42:07','2024-08-28 19:42:07'),(13,'Bacteria','.','2024-08-30 20:23:24','2024-08-30 20:23:24');
/*!40000 ALTER TABLE `cat_enfermedades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_marcas`
--

DROP TABLE IF EXISTS `cat_marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_marcas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_marcas`
--

LOCK TABLES `cat_marcas` WRITE;
/*!40000 ALTER TABLE `cat_marcas` DISABLE KEYS */;
INSERT INTO `cat_marcas` VALUES (1,'Bayer','2024-08-28 18:14:46','2024-08-28 18:14:46'),(2,'Syngenta','2024-08-28 18:14:54','2024-08-28 18:14:54'),(3,'Atlantica','2024-08-28 18:17:09','2024-08-28 18:17:09'),(4,'Adama','2024-08-28 18:18:18','2024-08-28 18:18:18'),(5,'Coda','2024-08-28 18:18:31','2024-08-28 18:18:31'),(6,'Dupont','2024-08-28 18:18:39','2024-08-28 18:18:39'),(7,'Lapisa','2024-08-28 18:18:56','2024-08-28 18:18:56'),(8,'Rotam','2024-08-28 18:19:15','2024-08-28 18:19:15'),(9,'Agrocience','2024-08-28 18:19:45','2024-08-28 18:19:45'),(10,'Basf','2024-08-28 18:20:01','2024-08-28 18:20:01'),(11,'FMC','2024-08-28 18:20:29','2024-08-28 18:20:29'),(12,'Mazfer','2024-08-28 18:20:46','2024-08-28 18:20:46'),(13,'Monsanto','2024-08-28 18:21:03','2024-08-28 18:21:03'),(14,'SQM','2024-08-28 18:21:14','2024-08-28 18:21:14'),(15,'UPL','2024-08-28 18:21:32','2024-08-28 18:21:32');
/*!40000 ALTER TABLE `cat_marcas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_tipo_flors`
--

DROP TABLE IF EXISTS `cat_tipo_flors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_tipo_flors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_tipo_flors`
--

LOCK TABLES `cat_tipo_flors` WRITE;
/*!40000 ALTER TABLE `cat_tipo_flors` DISABLE KEYS */;
INSERT INTO `cat_tipo_flors` VALUES (1,'Rosa','2024-08-28 19:42:47','2024-08-28 19:42:47'),(2,'Gerbera','2024-08-28 19:43:03','2024-08-28 19:43:03'),(3,'Enraizada','2024-08-28 19:44:06','2024-08-30 19:58:07'),(4,'Gladiola','2024-08-28 19:44:20','2024-08-28 19:44:20'),(5,'Aster','2024-08-28 19:44:56','2024-08-28 19:44:56'),(6,'Solidago','2024-08-28 19:45:08','2024-08-28 19:45:08'),(7,'Astromelia','2024-08-28 19:45:32','2024-08-28 19:45:32'),(8,'Velos','2024-08-28 19:45:51','2024-08-28 19:45:51'),(9,'Gipsofilia','2024-08-28 19:46:10','2024-08-28 19:46:10'),(10,'General','2024-08-30 19:38:46','2024-08-30 19:39:00');
/*!40000 ALTER TABLE `cat_tipo_flors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `porcentaje_descuento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adeudo_total` decimal(10,2) NOT NULL,
  `abono_total` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `requiereFactura` tinyint(1) NOT NULL,
  `rfc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clientes_id_sucursal_foreign` (`id_sucursal`),
  CONSTRAINT `clientes_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Publico en general','0',0.00,0.00,0.00,0,NULL,1,4,'2024-08-30 20:16:54','2024-08-30 20:29:49'),(2,'Rene Gonzalez','0',0.00,0.00,0.00,0,NULL,1,4,'2024-08-30 20:37:27','2024-08-30 20:41:03');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proveedor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_compra` date NOT NULL,
  `total_compra` decimal(10,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_credito` date NOT NULL,
  `total_credito` decimal(10,2) NOT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compras_id_sucursal_foreign` (`id_sucursal`),
  CONSTRAINT `compras_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras`
--

LOCK TABLES `compras` WRITE;
/*!40000 ALTER TABLE `compras` DISABLE KEYS */;
INSERT INTO `compras` VALUES (1,'agroquimicos romano','2024-08-02',13820.00,'pagada','2024-09-01',0.00,4,'2024-08-30 20:43:27','2024-08-30 20:43:27');
/*!40000 ALTER TABLE `compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras_abonos`
--

DROP TABLE IF EXISTS `compras_abonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras_abonos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_compra` bigint unsigned NOT NULL,
  `cantidad_abonada` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compras_abonos_id_compra_foreign` (`id_compra`),
  CONSTRAINT `compras_abonos_id_compra_foreign` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras_abonos`
--

LOCK TABLES `compras_abonos` WRITE;
/*!40000 ALTER TABLE `compras_abonos` DISABLE KEYS */;
INSERT INTO `compras_abonos` VALUES (1,1,13820.00,'2024-08-30 20:43:27','2024-08-30 20:43:27'),(2,1,0.00,'2024-08-30 20:43:27','2024-08-30 20:43:27');
/*!40000 ALTER TABLE `compras_abonos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras_productos`
--

DROP TABLE IF EXISTS `compras_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras_productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_compra` bigint unsigned NOT NULL,
  `id_producto` bigint unsigned NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compras_productos_id_compra_foreign` (`id_compra`),
  KEY `compras_productos_id_producto_foreign` (`id_producto`),
  CONSTRAINT `compras_productos_id_compra_foreign` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id`),
  CONSTRAINT `compras_productos_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras_productos`
--

LOCK TABLES `compras_productos` WRITE;
/*!40000 ALTER TABLE `compras_productos` DISABLE KEYS */;
INSERT INTO `compras_productos` VALUES (1,1,2,12,235.00,'2024-08-30 20:43:27','2024-08-30 20:43:27'),(2,1,1,10,1100.00,'2024-08-30 20:43:27','2024-08-30 20:43:27');
/*!40000 ALTER TABLE `compras_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empresas`
--

DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aviso` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresas_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas`
--

LOCK TABLES `empresas` WRITE;
/*!40000 ALTER TABLE `empresas` DISABLE KEYS */;
INSERT INTO `empresas` VALUES (1,'Pixka','Calle dos de marzo s/n, San Lucas, Villa Guerrero.','7228259581','meztlitechsolutions@gmail.com','JIDE930407AS4','Si tiene algun requerimiento contactenos por whatsapp','2024-08-28 20:47:50','2024-08-28 20:56:49'),(2,'Agroquimicos Miranda','Madero, 51776 San Lucas, Méx.','729 271 9692','Jivonne080@gmail.com',NULL,'Gracias por su compra!!!','2024-08-28 21:00:10','2024-08-28 21:03:23');
/*!40000 ALTER TABLE `empresas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enfermedades_tipo_flors`
--

DROP TABLE IF EXISTS `enfermedades_tipo_flors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enfermedades_tipo_flors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_tipo_flor` bigint unsigned NOT NULL,
  `id_enfermedad` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enfermedades_tipo_flors_id_tipo_flor_foreign` (`id_tipo_flor`),
  KEY `enfermedades_tipo_flors_id_enfermedad_foreign` (`id_enfermedad`),
  CONSTRAINT `enfermedades_tipo_flors_id_enfermedad_foreign` FOREIGN KEY (`id_enfermedad`) REFERENCES `cat_enfermedades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enfermedades_tipo_flors_id_tipo_flor_foreign` FOREIGN KEY (`id_tipo_flor`) REFERENCES `cat_tipo_flors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enfermedades_tipo_flors`
--

LOCK TABLES `enfermedades_tipo_flors` WRITE;
/*!40000 ALTER TABLE `enfermedades_tipo_flors` DISABLE KEYS */;
INSERT INTO `enfermedades_tipo_flors` VALUES (1,1,1,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(2,1,2,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(3,1,3,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(4,1,4,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(5,1,5,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(6,1,6,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(7,1,7,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(8,1,8,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(9,1,9,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(10,1,10,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(11,1,11,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(12,1,12,'2024-08-28 19:42:47','2024-08-28 19:42:47'),(13,2,1,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(14,2,2,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(15,2,4,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(16,2,5,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(17,2,6,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(18,2,7,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(19,2,8,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(20,2,9,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(21,2,10,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(22,2,11,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(23,2,12,'2024-08-28 19:43:03','2024-08-28 19:43:03'),(35,4,1,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(36,4,2,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(37,4,4,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(38,4,5,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(39,4,6,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(40,4,7,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(41,4,8,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(42,4,9,'2024-08-28 19:44:20','2024-08-28 19:44:20'),(43,5,1,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(44,5,2,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(45,5,4,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(46,5,5,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(47,5,6,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(48,5,7,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(49,5,8,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(50,5,9,'2024-08-28 19:44:56','2024-08-28 19:44:56'),(51,6,1,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(52,6,2,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(53,6,4,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(54,6,5,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(55,6,6,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(56,6,7,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(57,6,8,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(58,6,9,'2024-08-28 19:45:08','2024-08-28 19:45:08'),(59,7,1,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(60,7,2,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(61,7,4,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(62,7,5,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(63,7,6,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(64,7,7,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(65,7,8,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(66,7,9,'2024-08-28 19:45:32','2024-08-28 19:45:32'),(67,8,1,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(68,8,2,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(69,8,4,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(70,8,5,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(71,8,6,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(72,8,7,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(73,8,8,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(74,8,9,'2024-08-28 19:45:51','2024-08-28 19:45:51'),(75,9,1,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(76,9,2,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(77,9,4,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(78,9,5,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(79,9,6,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(80,9,7,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(81,9,8,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(82,9,9,'2024-08-28 19:46:10','2024-08-28 19:46:10'),(116,10,1,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(117,10,2,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(118,10,3,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(119,10,4,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(120,10,5,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(121,10,6,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(122,10,7,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(123,10,8,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(124,10,9,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(125,10,10,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(126,10,11,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(127,10,12,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(128,10,13,'2024-08-30 20:23:42','2024-08-30 20:23:42'),(129,3,1,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(130,3,2,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(131,3,4,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(132,3,5,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(133,3,6,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(134,3,7,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(135,3,8,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(136,3,9,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(137,3,10,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(138,3,11,'2024-08-30 20:24:59','2024-08-30 20:24:59'),(139,3,13,'2024-08-30 20:24:59','2024-08-30 20:24:59');
/*!40000 ALTER TABLE `enfermedades_tipo_flors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2012_07_06_184849_create_empresas_table',1),(2,'2013_09_19_234556_sucursales',1),(3,'2014_10_12_000000_create_users_table',1),(4,'2014_10_12_100000_create_password_reset_tokens_table',1),(5,'2014_10_12_200000_add_two_factor_columns_to_users_table',1),(6,'2019_08_19_000000_create_failed_jobs_table',1),(7,'2019_12_14_000001_create_personal_access_tokens_table',1),(8,'2023_06_05_172413_create_sessions_table',1),(9,'2023_06_05_174029_create_cat_marcas_table',1),(10,'2023_06_05_174629_create_cat_clasificacions_table',1),(11,'2023_06_05_174641_create_cat_tipo_flors_table',1),(12,'2023_06_05_174656_create_cat_enfermedades_table',1),(13,'2023_06_05_174905_create_productos_table',1),(14,'2023_06_05_174906_create_enfermedades_tipo_flors_table',1),(15,'2023_06_05_174907_create_solucion_enfermedads_table',1),(16,'2023_06_05_174918_create_clientes_table',1),(17,'2023_06_05_174926_create_ventas_table',1),(18,'2023_06_05_174938_create_producto_ventas_table',1),(19,'2023_06_26_203915_create_alta_inventarios_table',1),(20,'2023_07_10_181218_create_abono_cuentas_table',1),(21,'2024_04_22_055700_create_compras_table',1),(22,'2024_04_22_060408_create_compras_productos_table',1),(23,'2024_04_22_060708_create_compras_abonos',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_ventas`
--

DROP TABLE IF EXISTS `producto_ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_ventas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` bigint unsigned NOT NULL,
  `id_venta` bigint unsigned NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `total_productos` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_ventas_id_producto_foreign` (`id_producto`),
  KEY `producto_ventas_id_venta_foreign` (`id_venta`),
  CONSTRAINT `producto_ventas_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`),
  CONSTRAINT `producto_ventas_id_venta_foreign` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_ventas`
--

LOCK TABLES `producto_ventas` WRITE;
/*!40000 ALTER TABLE `producto_ventas` DISABLE KEYS */;
INSERT INTO `producto_ventas` VALUES (1,1,1,1.00,1205.00,'2024-08-30 20:29:49','2024-08-30 20:29:49'),(2,1,2,3.00,3615.00,'2024-08-30 20:38:03','2024-08-30 20:38:03'),(3,1,3,1.00,1205.00,'2024-08-30 20:40:22','2024-08-30 20:40:22');
/*!40000 ALTER TABLE `producto_ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_clasificacion` bigint unsigned NOT NULL,
  `id_marca` bigint unsigned NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_ieps` decimal(10,2) NOT NULL,
  `ieps` bigint unsigned NOT NULL,
  `tamano` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` bigint unsigned NOT NULL,
  `ingrediente_activo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `productos_nombre_unique` (`nombre`),
  KEY `productos_id_clasificacion_foreign` (`id_clasificacion`),
  KEY `productos_id_marca_foreign` (`id_marca`),
  KEY `productos_id_usuario_foreign` (`id_usuario`),
  CONSTRAINT `productos_id_clasificacion_foreign` FOREIGN KEY (`id_clasificacion`) REFERENCES `cat_clasificacions` (`id`),
  CONSTRAINT `productos_id_marca_foreign` FOREIGN KEY (`id_marca`) REFERENCES `cat_marcas` (`id`),
  CONSTRAINT `productos_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'AGRIMEC LT',4,2,0.00,1136.79,1205.00,6,'1000ML',2,'ABAMECTINA','2024-08-30 19:36:51','2024-08-30 19:36:51'),(2,'Agrygent',10,2,0.00,235.00,250.00,6,'250grs',2,'oxitocentrina sulfato de cobre','2024-08-30 20:05:18','2024-08-30 20:25:22');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

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

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('0l1o7C4qcudatcx9xwMjFKYk5JnrCPDWlNmdaw1H',NULL,'167.94.145.101','','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUTJnb1Rmd054Ynd6MW1BOUtobENzU05aNk9BUHVyVW1yWDI3SHlHQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725054799),('0uUWCahuQa78ofywMHTqMWJtB7p3yv7qj3WA4Ojj',NULL,'205.210.31.14','','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN1NSYkdBOFY1ZkU3cUk1V2hsc0VRMHNpWmZQbnMxZHM4ek5PMEI2UCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725112502),('11mc6HFg29rnagUkexzpXWgHiFVakyVze8YSkurO',NULL,'213.32.122.82','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiS05vS2lpcHR4TkNnZ0ZoNGZpOE9qYnl2cm5qTUU2WGtqcUR6Szh0SCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725091285),('1dYmLZcPoGVELoKg7nCr60FOkOiT8axfCHEMQ8vj',NULL,'167.71.226.25','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiemFWeEk1VmQ0SjYwNjRhZGdZMXRTVUtTSnZwR3ZSbk5EQ014WTNtZCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vYWdyb3N5cy5lcmlramltZW5lei5kZXYiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725080948),('1qb8OpefEqBZ2dm8mOarKfONI5uQU8V5jRJmDWmm',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicWk2cUhxREdZd1UzRGNuTVJjVU54cmVHU0RpNXR4Q2xPOUZGa0RkYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvP3hkZWJ1Z2luZm89Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725083469),('2mXxCUHHNQC3mEnuGhdMCbVahMQ2xt7zk7fh5P53',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiS0FFdjNtaTlYWnUwVmswTDFYU1dHSmFQcDNWakY3S2w4UmJndVAyUSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vd3d3LmVyaWtqaW1lbmV6LmRldiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725083087),('2QevuPEsJCMyKwDRM5sqNLna4GmJdg9amjSJCKbh',NULL,'118.194.253.14','Mozilla/5.02713705 Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUzRSVWVJYUxyb2c4SjJHcmFiWFd3RzdQSlVBcUNBVjJUTDRMSGJ6aSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8veHhuZXQtNDAzLmFwcHNwb3QuY29tIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725082790),('3D7Cvb19HA2eQm7svt4LlKNpfo6EwYxV4tVOGCFd',NULL,'45.156.129.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU2VYQ3l6cUwzVmhVWFBKOUlSNWt0bHJQdEI1dWszMjJha0xJSDljdCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725086445),('3uSQQTnB5eBsh9YXWt9xg4byhNZrA2yAk3BJ8PVd',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidHZEVEpPdXpBNXRHa2ZmOFdjSDZuM1VWQVBvaVM5bTg5OXNReEZ1MCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvaW5kZXgucGhwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725083320),('7JY6gFDW8bK4oZAAUdsHHib1jmFViOfLnOBflTkR',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUUZ3ZmdRZzdramFkNEZMYzVuVGd6NUtSNXI5TEVqUEJHenN5OFNhRSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Lz9xPWluZm8iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725082317),('7JYkU7udGNkTqFu89R5EyZrS1TZVSCzcJRfnur1n',NULL,'161.35.2.122','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicVNUeXo5dEJua2ZqY251b1gwT3BEbGNOSEdiQkVFR3ppdURXOVNhbyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725066018),('8fZd8dNONlMOGm7TnvVPRm3BKKyjLYNcFZvK0Y4Z',NULL,'45.156.130.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSjZoR0xyaHNjU2dkM09rZ0RLVnlTaU80cGRQNXRzemFJUklmY0V4TCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725084015),('8KgECMRZYQPSX1OClO0VHQGct7mqStT6B8HYY2rj',NULL,'110.172.98.2','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT2QxRmhjUXF2U1FrMFZyYnhMNlpmdFlkcldYOGJJa1BQUUw0bmNlTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725137266),('92d6c2721n88O9e4rbdGm7aszj9qRKApKpH1ld2N',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT1JIbkdpVGVvOGVHRVhIMktMckhGclJkekFlWHpnRTJGYVAzZmNjQiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vd3d3LmVyaWtqaW1lbmV6LmRldi9pbmRleC5waHAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725082874),('9DGnXLiuJgWasfaylGOy6Q9TaxS4JJzU1pOXZ9Ky',NULL,'45.156.129.48','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoic1lhcHZPT2NEcnhySVZCNjFUbDl5a3pWTGRma2RvMDlnb1ZoRkhVdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725109092),('9HeyrJsOb6491ZMlrioQuKJEJn2qCbUpO5qJo7n1',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidGpVZmZ5RmN3Vks5ZmhDWXk2d0JaYVpWeXlUVGF4VG9wUW9LWm9wOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvP3E9aW5mbyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725083467),('BBolep8rGGGjZ8A1yIOecsZdohMOhzf0IHLIPdgt',NULL,'138.197.127.101','Mozilla/5.0 (X11; Linux x86_64; rv:73.0) Gecko/20100101 Firefox/73.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM2E1VU0yVUpteE8ydzdJVjQzWFdubjhwZ0Q1NmhJVUxmUlNSWkdGUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725090076),('BJoIfCwgBpfGClEPw4kLWsP9ZqxxvZUm1GxX24nk',NULL,'162.216.149.216','Expanse, a Palo Alto Networks company, searches across the global IPv4 space multiple times per day to identify customers&#39; presences on the Internet. If you would like to be excluded from our scans, please send IP addresses/domains to: scaninfo@paloaltonetworks.com','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSFE3aVRyV21JUDRZVUljYmRBV1RFeUszdTJsaUt2QkNyVFRyNFJ4MiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725087252),('BKCJBF1oQcUeiiPul2OVvMf6ccTywaK3LglnTIGL',NULL,'167.94.145.101','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYUxqZjFXSE5VeXZ0N2JpSk5TNDV5bG9aSnhFdTJxNFA1b0ZXMGhpUSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725054804),('byrJRf9YZuQtIriL0Ouigc9ITO4EA8rPcdOay30D',NULL,'199.45.154.134','','YTozOntzOjY6Il90b2tlbiI7czo0MDoidDVuYW9CS2hIQmQ4NU1EcW1IYTQzMlhoSkZ3aDA5cVJpVG5YaGJIRiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725076158),('DniwwRBki6jGEXtOSC8DBcLC0LGq1bYkfiTtgiKG',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib1RTY2xJeGdzalJReDZCVzdzanNGOFZseGgySFhVVWNZSzFydUYxUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvP3BocGluZm89MSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725083411),('EDjhoDmnCQjeJUqnPpMCuSLEelS2w9oI0iiYZbMe',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOThyWkdFdElrMnVPZUtQV0J3WjBXR25rR1hUOG9oSDRSRHhGMjVnRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHBzOi8vd3d3LmVyaWtqaW1lbmV6LmRldi8/eGRlYnVnaW5mbz0iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725083081),('eimyWaLt6u0vknu3G6SjRHljrcSa3EYvWN3l8cj0',NULL,'146.190.220.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicE1WQTJWR0swbDR5MkFMaVUxQ0h5RFZGdDFkTWlueWpRb0txUThZWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725086858),('g53oeSWk6RSLWVfCvBFTGxZR6oYABVwdc70hAzXy',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZG1UUXFGa2k3NFcyWmhWTGZMMHRQbW14RTRpRnVLSFo3RTkyZDRjYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHBzOi8vd3d3LmVyaWtqaW1lbmV6LmRldi8/cT1pbmZvIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725083079),('gEgBUi10oICXanWhZ68mt2bPxNnoMRphfF32abhb',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoienRQa2lNeHlqUnhIMmR1NkphODdQNmVEV29XMzZscGZIZmM1U2IxOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2L2luZGV4LnBocCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725082118),('HaRDnInV0gfy7l0JjKrN9EsBn1C3RpE5eTQxseYW',NULL,'68.183.89.157','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMFRxT3ZIMFVIaWx3WmYyajZiVXlnMXpycmltY3RZOTZRNWdxeXNFdSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725087914),('HIs1QlrhlrZH9YYoldg1H3Ro9FEUZbpAunGwwIpE',NULL,'199.45.154.134','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoieTlzRWRmWnZQVWl2cUtWMktLaXBJTW9aZFFYVWVXa0E2aDJjdFliZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725076162),('hkKmMlMwqMOVpllDRBL2hXYdta22hqBve0EXJfbX',NULL,'45.156.130.2','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYnBVdURJMkQ3WnV6cTRvZFhzS3J3OHRZaTJxVTZVdDJaTmttVnJoTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725134355),('ku5Af87dsU1mQfhzvvfUsbBeSLos4ZneBXTausnh',2,'189.138.34.92','Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiM2dRcUVjTWxwVmEzMnlOTDVMTTRBWUZ3QUt4c3JvZlRvamZvRDVXaiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9',1725051349),('lAflpL5k5hNN1U1ngmEBTmyfTafLrxaWmJACirnT',NULL,'110.172.98.2','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaEFKVDlLTE1CVzBXc1ludEI5cFdsclZ6SzVQU3Jva1gwQ295cE0wVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725063517),('lmaeW2yivUjSFLJqSsR7WstuM36Kk1MIlqcM0ksg',NULL,'109.230.113.12','Custom-AsyncHttpClient','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ2xlNUdaYWwzZmI0V1VqRXZMUDZJMmJzRWtjdkN4V2VKSFdKSUhJWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjAxOiJodHRwczovLzIxNi4yMzguODEuMTI4L2luZGV4LnBocD8lMkYlM0MlM0ZlY2hvJTI4bWQ1JTI4JTIyaGklMjIlMjklMjklM0IlM0YlM0UlMjAlMkZ0bXAlMkZpbmRleDEucGhwPSZjb25maWctY3JlYXRlJTIwJTJGPSZsYW5nPS4uJTJGLi4lMkYuLiUyRi4uJTJGLi4lMkYuLiUyRi4uJTJGLi4lMkZ1c3IlMkZsb2NhbCUyRmxpYiUyRnBocCUyRnBlYXJjbWQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725070387),('meh15sFBZvIedYTuTqyh3veszSDY1TBFIupVwOvT',NULL,'185.180.140.6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTUdwQ0sxVG95VXlvSms1dThxVUxrT3NPOERhYTBMbldScFZoSzdaMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725085660),('MkDeuYVxJo6cWYYOQ3RObhrCXBPDqssQ2BBukjBL',NULL,'94.232.46.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36 Edg/115.0.1901.203','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRlh1N0duNzl4amIzbzlrcVEzVVBRV05JeHZCMk8wcnlEamtLbzVVaCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725122437),('mnCWS29zlTTrr5fhmwVK873Drq2b5s9GGYEVgsXH',NULL,'43.130.16.212','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRVkwTGZVVjJqZVFjRmZhRTdZcm9WbjNXMnJZSjdVY2NKZ3FEMDFOaCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725095098),('n5iCBF9xPja07AVrkRWmSGuorywDNUOFv4ysVPFL',NULL,'172.168.41.211','Mozilla/5.0 zgrab/0.x','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQTdCaElBOWhpdmpLY25RanducmlRdlpOYU5zNlIwbWVtVmtxek1GRSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725081742),('NSpYrGGZ8xQ7mhmbANnOTqYdG55Idj8BeadivdsY',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicmFQZm1NYm5QVEEwaDd3RmRhSEFCQlo0NHpyWXRnWGpMR0VlWHFXTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725083477),('oEyaY47u10zUKajGDXIEKJQ0PnjG56YngaWqdA8s',NULL,'45.156.128.43','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU08yV1ZmMmw0b2VvV0FPa2RkR0ZlMnVwNU1ORGpjSmtmRXFJelptNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725084161),('OsYyPEjtPKZV7qHtQyoPYEIQMZAQPi0C3O6qH1OS',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU0M3Slk1TTJEblNKU2xYd3pTWmpiWXlSVk1EcXc3WmNnUVBKRVdmdCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vd3d3LmVyaWtqaW1lbmV6LmRldiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725082833),('OV5gEzY4BGUoUBbuC2NgQTUj25Sp7TyQ5TdVDaFb',NULL,'49.51.179.103','Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoidDVPNGFKdklja2FhMFV6Sk5OUjVtRDVkVkcwSHlkdGJjR0ZHSU54TSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725094432),('p4cfOkLpMPSooUCSsoRUY10OmLG9hcDUdq2vKIwT',NULL,'52.189.75.199','Mozilla/5.0 zgrab/0.x','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUVE5RGpIT3VpMFNxUzZXMk5YVEo1VWFsOTRjVEZ5ZERGd1c3U0ZITiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725142159),('PjHUoFdWg7xhACU5VsO1BqP9wFoUThCvUvgKEQbT',NULL,'107.150.117.65','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/34.0.1847.116 Chrome/34.0.1847.116 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN3RlQnFaU3h3SmpXUld0cDdvR0VBN2VxZVRWV0RlNlJQTHBkS3d0MSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725082698),('pMUi73MydJ5iTNGO1FKPYP8hjulKMcC9BTYrUJ5A',2,'189.138.34.92','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiVFZtVWxBUkxZc2xwY0xpY0tRQ09LbG9sWEsydGVlSWVYc0d2WjFmYiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0MjoiaHR0cHM6Ly9lcmlramltZW5lei5kZXYvcmVwb3J0ZS9pbnZlbnRhcmlvIjt9czoyMToicGFzc3dvcmRfaGFzaF9zYW5jdHVtIjtzOjYwOiIkMnkkMTAkbm13QmQ3SDFpT2IyWWdwYi9DbjRZT3QuMHplUVZGUldzRW1IMng5dHd5NVpUUmFMeHlUYmUiO3M6MTk6InR3b19mYWN0b3JfZW1wdHlfYXQiO2k6MTcyNTA1MTE2OTt9',1725051169),('q73stGunbIGufFaF7PRFcXjUzulcD0umHnwOudVt',NULL,'64.62.197.230','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.6045.160 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiREtWdHNxUTIwR2NTY08yeHI4ZVFYcExXb3oxWkt4dUJheXBXQldmUSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725077550),('rrWxW0cPe4dp3YKiAub5Col7w9nwF6K3Byw2IEvB',NULL,'198.105.124.189','Custom-AsyncHttpClient','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMkN4QUdGYUphNHZXUHFDaENkNXF5OWRGeVhVS1VSWjdzbmNRSDBKSSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjAxOiJodHRwczovLzIxNi4yMzguODEuMTI4L2luZGV4LnBocD8lMkYlM0MlM0ZlY2hvJTI4bWQ1JTI4JTIyaGklMjIlMjklMjklM0IlM0YlM0UlMjAlMkZ0bXAlMkZpbmRleDEucGhwPSZjb25maWctY3JlYXRlJTIwJTJGPSZsYW5nPS4uJTJGLi4lMkYuLiUyRi4uJTJGLi4lMkYuLiUyRi4uJTJGLi4lMkZ1c3IlMkZsb2NhbCUyRmxpYiUyRnBocCUyRnBlYXJjbWQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725130375),('SEEW4OLLhuB3ljVJtfOY7nR9QnD0LpahzcgZP5CM',NULL,'45.156.129.48','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiV3A1Uzl3RmV2QXVTdzU4angwZ2pnUUhyRGJGSFRLV2Y0Vm80YWNEbiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725109104),('SGRfDuHIMZtWWiAhGrspwAkJ1xbHJmOP0T1SgXcR',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMFZIT2Y5Rjdzbks3bHUyQmNSMmdVeGFEUTR6dnFnc2haMWY0UGVOaiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Lz9waHBpbmZvPTEiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725082237),('Sw3kFBV0bLl8RRVGLHGgy7at4av5hXSJ565jzdvG',NULL,'64.62.197.240','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.6045.160 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU0JISWx4Q1V6VzdhMG9EQ2dQOEhzWTBDeGdpa0s3WWd4Nm92ejJ0SSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725077027),('tN2S6UXKakWT63OeNYTwSIQqMCEJxEEYUIoMpTNi',NULL,'13.64.108.50','Mozilla/5.0 zgrab/0.x','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRmR1eWRXN0lFcUNJUjRqcFpRbkxVaDZua3VnV2NWdFYzM2lkYVRnSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725050121),('toNsEIoN4FWzvX7dszOdhKSDse9xyZ8VcHug2ekE',NULL,'83.97.73.245','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicVJ1TkRybWFXTmJpZTVXd1NKSjlDakVNZjNQUWhJaUloMjdrWGlIRCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTM6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvP1hERUJVR19TRVNTSU9OX1NUQVJUPXBocHN0b3JtIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725144459),('TxgjGWZtdTkDGowhbH6yGMTmhwD7S1I8GJjFlU78',NULL,'198.105.124.189','Custom-AsyncHttpClient','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWDdHaFZxeWRyTUFDUmE2TnZmR3JYUVNhNmZhczNnNnlwWUlOS09IOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6OTA6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvaW5kZXgucGhwP2xhbmc9Li4lMkYuLiUyRi4uJTJGLi4lMkYuLiUyRi4uJTJGLi4lMkYuLiUyRnRtcCUyRmluZGV4MSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725130376),('ud1Mxb0tbwlhWaDjCj5tVNObaYudNpqgrPPSYXLL',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOXB6SEFYcWlvckw0U1Q3SWVYTGhQWUVwQXpRUVFsbnlhOGRhaXJ4ciI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725082083),('uMTlZhR6k5FE8TnxNztnO0m3PWPlBzi2IbL8HBpy',NULL,'45.156.129.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT09Da0N2QWF4R3RPTXUyV25IMHBJRThlY0hqRExScktQZ0lyZ0tQMyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725052508),('uNx3TF2RJLFjCXJIwcAtTHmItt1kF7DkLe4u9YRN',NULL,'198.105.124.189','Custom-AsyncHttpClient','YTozOntzOjY6Il90b2tlbiI7czo0MDoibVptVXJuWDBja0R2Mk1JZVd3VE9TNGVOTjNFaHFBcFV3U0dHYUppeiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MTQ4OiJodHRwczovLzIxNi4yMzguODEuMTI4L2luZGV4LnBocD9mdW5jdGlvbj1jYWxsX3VzZXJfZnVuY19hcnJheSZzPSUyRmluZGV4JTJGJTVDdGhpbmslNUNhcHAlMkZpbnZva2VmdW5jdGlvbiZ2YXJzJTVCMCU1RD1tZDUmdmFycyU1QjElNUQlNUIwJTVEPUhlbGxvIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725130375),('uoRcas5Znn4VhTdvA2UmoXuPV0bIS7BCjuXDohuZ',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib1FBRDJQd085NFFuMVJ0UENhTzNtZ2hwUTFURm1iRnJ1MVcxN1p3MCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725082326),('uzLoL81KtysoLrPKUagLc2wCzAg6IZRGwNTOgWxN',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNmhJMGtlUVdDQ3RhNDg3RG54MmJBRXk0N3hUY3NkcTlhZG9jZ1FPZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHBzOi8vZXJpa2ppbWVuZXouZGV2Lz94ZGVidWdpbmZvPSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725082319),('VZjWndsf9Ng5mskGlZr8WCmDTIID0V3g2JSRetCP',NULL,'185.137.233.29','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibFlobVczNTRIa2U1UUJqbFdlT2VLY1ZyWldJU3ptWmdaMGVhUVJPcyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725100795),('WH4255IghH3llS5oe6C0jrSbidH9rG22nYxNbNLI',NULL,'104.155.80.132','python-requests/2.32.2','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ2FrN1dFdlZINEVjRkFlTWI5RGFLYnF4Ulo0NjFSMzh0UFRvNDNWVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725111057),('wTNtkv6ADIoPZ5oOXOvXrKDxMvtOxYgviWoD5U6n',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYmFRbHNEQWNTcW9DcWNRRTFXV0dXRVhsUWNXYmc5TzcwNFhOU1I0SSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725083289),('x3Buq765fCjYttU60yT3OimjEtJMCdqWd4snQlyl',NULL,'83.97.73.245','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZDQ1T1JIWllHbTg4NGtGUWdKNERPRXMwNktNUkVUOTdLS3VaZjBUZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTM6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvP1hERUJVR19TRVNTSU9OX1NUQVJUPXBocHN0b3JtIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725055925),('XqsuLFQ4mAboSAb4kNBP1lEYWsmcTBBlNR8T1vME',NULL,'109.230.113.12','Custom-AsyncHttpClient','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTkRLNW5oR0ZUbXl3VW9wVEJyZG5ISzVlTkpGY1hOZzlNNnVVWGhxUSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6OTA6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgvaW5kZXgucGhwP2xhbmc9Li4lMkYuLiUyRi4uJTJGLi4lMkYuLiUyRi4uJTJGLi4lMkYuLiUyRnRtcCUyRmluZGV4MSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1725070390),('xYwrlZjOnXDpHE4DqCCRojfvJeQaoFkXyYY57yGg',NULL,'18.232.184.244','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT01SYnNrZHJXeTVRN3I4RmNBRjdJcERTTTdaRVlEOWxjZThBYnM2WSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHBzOi8vd3d3LmVyaWtqaW1lbmV6LmRldi8/cGhwaW5mbz0xIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725082999),('XzJVgCcHuKpSWA4ZzaAeePzoZlDZuvAc6pXyJ3yu',NULL,'109.230.113.12','Custom-AsyncHttpClient','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWWdPeHFSS3RxVWZJZnVyNHJxV3FQb1N6QzJCa3o1RTJjeTZsUHdjWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MTQ4OiJodHRwczovLzIxNi4yMzguODEuMTI4L2luZGV4LnBocD9mdW5jdGlvbj1jYWxsX3VzZXJfZnVuY19hcnJheSZzPSUyRmluZGV4JTJGJTVDdGhpbmslNUNhcHAlMkZpbnZva2VmdW5jdGlvbiZ2YXJzJTVCMCU1RD1tZDUmdmFycyU1QjElNUQlNUIwJTVEPUhlbGxvIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1725070384),('zqs22vILRp8EeBTiHbS83QZ29hThvoURroqDHYfV',1,'189.138.34.92','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoieksxRm81UWFuWXZIUXBPbmlCU055RWFuVndoVWo2UGVjQlhYaDhqYyI7czozOiJ1cmwiO2E6MDp7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1725051282),('ZT7PZARYG7w31z2rSCZIHhCjCDEaRUUP8G6PzUd4',NULL,'152.32.235.96','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWU1oNUQyMTJrVWNWeVpFMHZGRWdpenpGMXB1MTlEeG92cFVlQ3JWVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vMjE2LjIzOC44MS4xMjgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1725119493);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solucion_enfermedads`
--

DROP TABLE IF EXISTS `solucion_enfermedads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solucion_enfermedads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` bigint unsigned NOT NULL,
  `id_enfermedad_tipo_flor` bigint unsigned NOT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  `dosis_bomba_ml` decimal(10,2) NOT NULL,
  `dosis_tambo_ml` decimal(10,2) NOT NULL,
  `condiciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `solucion_enfermedads_id_producto_foreign` (`id_producto`),
  KEY `solucion_enfermedads_id_enfermedad_tipo_flor_foreign` (`id_enfermedad_tipo_flor`),
  KEY `solucion_enfermedads_id_sucursal_foreign` (`id_sucursal`),
  CONSTRAINT `solucion_enfermedads_id_enfermedad_tipo_flor_foreign` FOREIGN KEY (`id_enfermedad_tipo_flor`) REFERENCES `enfermedades_tipo_flors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `solucion_enfermedads_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `solucion_enfermedads_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solucion_enfermedads`
--

LOCK TABLES `solucion_enfermedads` WRITE;
/*!40000 ALTER TABLE `solucion_enfermedads` DISABLE KEYS */;
INSERT INTO `solucion_enfermedads` VALUES (2,1,15,4,10.00,150.00,'.','2024-08-30 19:55:47','2024-08-30 19:55:47'),(5,2,139,4,16.00,240.00,NULL,'2024-08-30 20:26:09','2024-08-30 20:26:09'),(6,1,122,4,10.00,150.00,NULL,'2024-08-30 20:49:39','2024-08-30 20:49:39'),(7,1,131,4,10.00,150.00,NULL,'2024-08-30 20:50:01','2024-08-30 20:50:01');
/*!40000 ALTER TABLE `solucion_enfermedads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sucursales`
--

DROP TABLE IF EXISTS `sucursales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sucursales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_matriz` tinyint(1) NOT NULL,
  `id_empresa` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sucursales_id_empresa_foreign` (`id_empresa`),
  CONSTRAINT `sucursales_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sucursales`
--

LOCK TABLES `sucursales` WRITE;
/*!40000 ALTER TABLE `sucursales` DISABLE KEYS */;
INSERT INTO `sucursales` VALUES (1,'Matriz','Calle dos de marzo s/n, San Lucas, Villa Guerrero.','7228259581','meztlitechsolutions@gmail.com',1,1,'2024-08-28 20:47:50','2024-08-28 20:47:50'),(4,'Matriz','Madero, 51776 San Lucas, Méx.','729 271 9692',NULL,1,2,'2024-08-28 21:09:05','2024-08-28 21:09:05');
/*!40000 ALTER TABLE `sucursales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_team_id` bigint unsigned DEFAULT NULL,
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_id_sucursal_foreign` (`id_sucursal`),
  CONSTRAINT `users_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Erik Jimenez','erikedu5@gmail.com','2024-08-28 20:47:50','$2y$10$KSddlFDCsE4YJg7n.r6unO7YbxU4uQKY3dvl1jMX4gkQO8YL73yt2',NULL,NULL,NULL,'superAdmin',1,'cAFeqyniZNdw5mUyEVDzP6bn96KV6WPUyNY1f5Nifq3DTKQzSeIBiNk5advZ',NULL,NULL,'2024-08-28 20:47:50','2024-08-28 20:47:50'),(2,'Ivonne Jiménez Díaz','jivonne080@gmail.com',NULL,'$2y$10$nmwBd7H1iOb2Ygpb/Cn4YOt.0zeQVFRWsEmH2x9twy5ZTRaLxyTbe',NULL,NULL,NULL,'admin',4,NULL,NULL,NULL,'2024-08-28 21:10:54','2024-08-30 20:52:48'),(3,'Jacob Villegas','jacob@gmail.com',NULL,'$2y$10$tdTiHQQJx18bUgS3vKvbhO2Zy0q1jLYZH3pn8vEDT7akROyciO95a',NULL,NULL,NULL,'vendedor',4,NULL,NULL,NULL,'2024-08-30 20:36:14','2024-08-30 20:36:14');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `total` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_venta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `venta_pagada` tinyint(1) NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `id_cliente` bigint unsigned NOT NULL,
  `id_usuario` bigint unsigned NOT NULL,
  `id_sucursal` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ventas_id_cliente_foreign` (`id_cliente`),
  KEY `ventas_id_usuario_foreign` (`id_usuario`),
  KEY `ventas_id_sucursal_foreign` (`id_sucursal`),
  CONSTRAINT `ventas_id_cliente_foreign` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  CONSTRAINT `ventas_id_sucursal_foreign` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `ventas_id_usuario_foreign` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,'1205.00','Contado',1,'2024-08-30',1,2,4,'2024-08-30 20:29:49','2024-08-30 20:29:49'),(2,'3615.00','Credito',1,'2024-08-30',2,3,4,'2024-08-30 20:38:03','2024-08-30 20:41:03'),(3,'1205.00','Credito',1,'2024-08-30',2,3,4,'2024-08-30 20:40:22','2024-08-30 20:41:03');
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-08-31 23:01:40
