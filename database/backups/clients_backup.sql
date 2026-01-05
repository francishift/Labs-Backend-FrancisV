/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: labs_db
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `cif_nif` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `excel_created_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(3,'Miguel Lobato Montoya','48962168k',NULL,NULL,NULL,'Calle Ana Belén Sánchez, 2','Las pajanosas','41219','Sevilla','España','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(7,'Activa19 Comunicaciones, SL.','B10785251','activa19comunicaciones@gmail.com',NULL,NULL,'Plaza Alcalde Horacio Hermoso, 13, 1ºA','Sevilla','41013','Sevilla','España','2025-12-30 00:00:00','2025-12-30 10:13:26','2025-12-30 17:37:00'),
(8,'Intracom',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'España','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(9,'SKYLIFE ENGINEERING, S.L.','B91936997',NULL,NULL,NULL,'Parque Científico y Tecnológico Cartuja C/ Américo Vespucio 5, Bloque 1 Local A 8-12','Sevilla','41092','Sevilla','España','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(14,'ARACO ABOGADOS Y ASESORES, S.L.P.','B90341223','aperezportero@araco.es',NULL,NULL,'Avenida De los Descubrimientos Circuito 2 40','Mairena del Aljarafe','41927',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:26','2026-01-01 22:02:55'),
(16,'Baudouin Lamourère','X8172901N',NULL,NULL,NULL,'Avenida del Sur 45','Mairena del Aljarafe','41927',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:26','2025-12-31 10:12:16'),
(17,'Cazalla Motor S.L.','B91745505',NULL,NULL,NULL,'Calle Olivillas 10','Cazalla de la Sierra','41370',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(21,'Etron Automoción','B90255720',NULL,NULL,NULL,'Calle torneo 76','Sevilla','41002',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(22,'F.C. 12 S.L.','B41659186',NULL,NULL,NULL,'Calle Canalejas 11','sevilla','41001',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(23,'FaroGomez S.L.','B91920355',NULL,NULL,NULL,'Plaza de San Sebastian 1','Sevilla','41004',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(24,'Feuvert Ibérica S.A','A79783254',NULL,NULL,NULL,'Calle Condesa de Venadito 1 1 izq','Madrid','28027',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:26','2025-12-31 10:49:54'),
(28,'Gonzalo Mártínez Menéndez','28913419G',NULL,NULL,NULL,'Calle Arfe 7','Sevilla','41001',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(30,'Hostalpa S.L','B83867515',NULL,NULL,NULL,'Calle Guzman el bueno 68 5 d','Madrid','28016',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:26','2025-12-31 12:09:03'),
(32,'Ildefonso Fernandez Herrero','72027159Z',NULL,NULL,NULL,'Calle El Cruce de Santibañez 29','Villacarriedo','39649',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(36,'La Taberna de Gamazo S.L.','B90264672',NULL,NULL,NULL,'Calle Gamazo 6','Sevilla','41001',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:26','2025-12-31 10:47:29'),
(41,'Papeles del Norte S.L','B31002330',NULL,NULL,NULL,'Calle Harinas 17','Sevilla','41001',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:26','2025-12-30 17:19:55'),
(43,'Pastoriza Grupo Hotelero','B70449103',NULL,NULL,NULL,'Calle Balnco rajoy 77 bajo','Vimianzo A','15129',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(44,'PromoCaixa','A58481730',NULL,NULL,NULL,'Calle Garn Via de Carles III 105','Barcelona','8028',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(45,'Universidad Pablo de Olavide CABD','Q9150016E',NULL,NULL,NULL,'Universidad Pablo de Olavide Edif.20','Sevilla','41013',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(48,'RM Translation and Editing S.L.','B93679454',NULL,NULL,NULL,'Calle Fernández Fermina 16 3','Málaga','29006',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(51,'Sol y Playa 2011, S.L.','B93160489',NULL,NULL,NULL,'Carretera Sevilla Portugal 85.4','Aracena','21200',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(53,'Talleres Rafael Romero','B41993593',NULL,NULL,NULL,'Carretera del Pedroso 5','Cazalla de la Sierra','41370',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(54,'Talleres Ramos - JOSE RAMOS FERNANDEZ','47200454S',NULL,NULL,NULL,'Poligono Los Manantiales 41','Sevilla CAZALLA DE LA','41370',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(56,'Concesur S.A.','A41032848',NULL,NULL,NULL,'Ctra. Sevilla Málaga Km 5.5','Sevilla','41500',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(61,'José Antonio Bogas Mesones','02612715F',NULL,NULL,NULL,'Paterna 3 - 6','Valencia','46110',NULL,'ES','2025-12-30 17:19:55','2025-12-30 10:13:27','2025-12-30 17:19:55'),
(70,'José Luis Lledó González','28517746T',NULL,NULL,NULL,'c/ Tetuán 33 Pl 4','Sevilla','41001',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(73,'Jesús Cruz Álvarez','48929239M',NULL,NULL,NULL,'Calle La Breña 23','Bollullos de la Mitación (Sevilla)','41110',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(74,'Alberto Domínguez Zambrano','47500592A','info@estudioalbertodominguez.com',NULL,NULL,'C/ Pedro Criado, 2 C','La Rinconada (Sevilla)','41307',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2026-01-02 11:05:55'),
(77,'EINTE S.L.','B91830844','administracion@einte.com',NULL,NULL,'Poligono Pisa, Calle Manufactura Edificio E','Mairena del Aljarafe','41927',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(78,'ALBERT LEGIHO CONSULTORES, S.L.','B41519950','joseangel@iafm.com',NULL,NULL,'Calle Jauregi 4-6','Sevilla','41003',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-30 17:36:52'),
(79,'alGenio marketing online s.l.u','B90085960','admin@algenio.com',NULL,NULL,'Calle Leonardo da vinci 18 4 8','Sevilla','41092',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2026-01-02 11:07:03'),
(80,'AMARA SEXOLOGÍA Y GÉNERO SC','G91487785','info@centroamara.com',NULL,NULL,'Calle Rosario 16 1 b','Sevilla','41001',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2026-01-02 11:07:20'),
(81,'Arengalia S.L','B91150797','administracion@arengalia.es',NULL,NULL,'Avenida Médicos Sin Fronteras, 31 – 1o, puerta 4','Sevilla','41020',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(82,'BB Arquitectura de Espectáculos S.L.','B91673707',NULL,NULL,NULL,'Calle Feria 47 2ªI','Sevilla','41002',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(83,'BioChem AGROLOGÍA SLU','B90464389',NULL,NULL,NULL,'Carretera A-362 Kilometro 4.7','Utrera','41710',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(84,'convertClick, S.C.','J90128976','julian@convertclick.es',NULL,NULL,'Calle San Jacinto 16','Sevilla','41010',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(85,'English Language Institute, S.L.','B41395690','myriam@eli.es',NULL,NULL,'Calle Larra 1','Sevilla','41015',NULL,NULL,'2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(86,'Enrique Galera Espinosa','09194026Y',NULL,NULL,NULL,'Calle Antonio Guzmán 1 4ºa','Sevilla','41007',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(87,'Restaurante La Yedra','28589058N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(88,'JOSÉ DAVID FERNÁNDEZ GENIZ','28927580C',NULL,NULL,NULL,'Calle Butrón 19 2 B','Sevilla','41003',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(89,'Euro Integral de Servicios informáticos S.L.','B91323972','comercial@einte.com',NULL,NULL,'Poligono Industrial Pisa C/ Diseño Nave 6','Mairena del Aljarafe','41927',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(90,'Fada Catec',NULL,'prubiano@catec.aero',NULL,NULL,'Calle Parque Tecnológico y Aeronáutico de Anda','La Rinconada','41039',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(91,'Federación Andalucía Acoge','G41516030',NULL,NULL,NULL,'Calle Cabeza del Rey Don Pedro','Sevilla','41001',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(92,'Formación y consultoría en gestión y dirección S.L','B91879809',NULL,NULL,NULL,'Calle Moraleja 6 3 a','Sevilla','41020',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-31 12:08:46'),
(93,'G-ON Sistemas S.L.','B11541109',NULL,NULL,NULL,'Calle Torriceli 5 Local','Sevilla','41092',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-31 12:08:54'),
(94,'INDRA BPO SERVICIOS , S.L.U','B60096435',NULL,NULL,NULL,'Avenida de Bruselas 35','Alcobendas','28108',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(95,'Instituto Andaluz de Formacióny empleo S.L.','B41779810','joseangel@iafm.com',NULL,NULL,'Calle Jauregui 4 b','Sevilla','41003',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-31 12:09:18'),
(96,'Joaquín Vidal López','22138066Z',NULL,NULL,NULL,'Calle Jamaica 1 Bungalow B','Petrer','03610',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(97,'Judit Egea Castellano','52223107C',NULL,NULL,NULL,'Calle Real 78','Castilleja de la Cuesta','41950',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(98,'kanvasmedia','12345678X',NULL,NULL,NULL,'Calle xxx 12','madrid','12345',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(99,'ENDIMION S.A.','A33354838','magica@llanes.as',NULL,NULL,'El Allende s/n','Llanes, Asturias','33508',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2026-01-01 13:05:53'),
(100,'Lemon Audiovisual S.L.','B90157835','adrian@lemonaudiovisual.com',NULL,NULL,'Calle San Gregorio 5 4 10','Sevilla','41004',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(101,'Pedro Cabañas Gallego',NULL,'pedro@pedrocabanas.net',NULL,NULL,'Calle Garci Pérez 1 1 Dch','Sevilla','41003',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(102,'Promociones Inmobiliarias de Sevilla S.L.','B91056044',NULL,NULL,NULL,'Calle Emilia Pardo Bazán 10','San Jose de La Rinconada','41300',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(103,'RED LOGISTICA DE MAQUINARIA SL','B91747741',NULL,NULL,NULL,'Via AUTOVIA SEVILLA-MALAGA KM4,8','Alcalá de Guadaira','41500',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(104,'Relatoras','22222222F',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(105,'Rent wheeler S.L.','B41888827','cmartinez@alpesur.com',NULL,NULL,'Poligono Industrial La Red 82 c/8','Alcalá de Guadaira','41500',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(106,'Tambor del Llano, SL','B90012840',NULL,NULL,NULL,'Calle Cañada Grande-Los Alamillos','Grazalema','11610',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(107,'TARKEMOTO, SL','B91965160','angel@tarkemoto.com',NULL,NULL,'Plaza Virgen de la Amargura 12 4 C','Sevilla','41010',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(108,'Universidad de Sevilla','Q4118001I',NULL,NULL,NULL,'Calle San Fernando 4','Sevilla','41004',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(109,'UTE EVENTISIMO S.L.U. Y ABBSOLUTE COMUNICACIÓN S.L.','U90432980',NULL,NULL,NULL,'C/Balance 16','Mairena del Aljarafe','41927',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(110,'ALPESUR, Alquiler de Maquinaria SL','B90210576','cmartinez@alpesur.com',NULL,NULL,'AUTOVIA SEVILLA-MALAGA KM 4,8','Alcala de Guadaira (Sevilla)','41500',NULL,NULL,'2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-31 09:13:43'),
(111,'Over Limit Aventura S.L.','B91287672','imurube@grupooverlimit.com',NULL,NULL,'P.L los Llanos c/ Baleares 278a','Salteras, Sevilla','41909',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-30 19:52:06'),
(112,'U.T.E. ANDALUCIA ABBSOLUTE COMUNICACIÓN, SL Y EVENTISIMO, SLU','U67737510',NULL,NULL,NULL,'Avda. de los Descubrimientos 11, 4º Izda.','Sevilla','41092',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(113,'HUMAN SMART LAB, S.L','B05496245',NULL,NULL,NULL,'C/ Jáuregui, 4-6 Portal A, local B','Sevilla','41003',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-31 12:09:07'),
(114,'U.T.E. ADF 2022 EVENTISIMO, S.L.U Y ABBSOLUTE COMUNICACIÓN, S.L.','U09940842',NULL,NULL,NULL,'Calle Balance 16','Mairena del Aljarafe, Sevilla','41927',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(115,'Javier Fernández de la Morena','28593046K',NULL,NULL,NULL,'C/ Madrid 4, 5º E','Pamplona, Navarra','31016',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(116,'Hospimed S.L.','B18775817',NULL,NULL,NULL,'PQ. EMP. Los Llanos','Salteras (Sevilla)','41909',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(117,'JESUS CORTES DIAZ','77800756J','aetrafico@gmail.com',NULL,NULL,'Ronda de Triana 36 A','Sevilla','41010',NULL,'ES','2025-12-30 00:00:00','2025-12-30 10:13:27','2025-12-31 19:14:39'),
(118,'AVENSIS EVENTS, S.L.U','B91996843',NULL,NULL,NULL,'Calle Olivo, 1','Santiponce (Sevilla)','41970',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(119,'La Pepa PC SL','B90253097',NULL,NULL,NULL,'Avda. Aljarafe, 14, 1ª Planta, Local 1','Tomares (Sevilla)','41940',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(120,'Autoridad Portuaria de Sevilla','Q4167008D',NULL,NULL,NULL,'Avenida de Moliní, 6','Sevilla','41012',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(121,'Asociación unaAuna','G90340100','correo@TU_DOMINIO',NULL,NULL,'Calle Rosario, nº 16, 1b','Sevilla','41001',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(122,'Marta Ovelar Díaz','32084801P',NULL,NULL,NULL,'C/Ciprés, 11','Cazalla de la Sierra (Sevilla)','41370',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(123,'Susana Aguilera Girón','33530815N',NULL,NULL,NULL,'C/ Piscis nº2, 1º A','Madrid','28007',NULL,'ES','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(124,'DOXMEDIA S.L.','B90057878','oscar@doxmedia.es','660292919',NULL,'CALLE ANTONIO SUSILLO, 46 - PISO 2 B','SEVILLA','41002',NULL,'España','2025-12-30 17:19:56','2025-12-30 10:13:27','2025-12-30 17:19:56'),
(126,'ABBSOLUTE COMUNICACIÓN, S.L.','B91348755','facturas@grupoabsolute.com',NULL,NULL,'Avenida de los descubrimientos 11 4 izq','Sevilla','41092',NULL,'ES','2025-12-27 00:00:00','2025-12-30 17:19:55','2026-01-03 11:00:08');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-04 11:42:59
