/*
SQLyog Community
MySQL - 10.4.17-MariaDB : Database - perfect_teeth
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`perfect_teeth` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `perfect_teeth`;

/*Table structure for table `citas` */

DROP TABLE IF EXISTS `citas`;

CREATE TABLE `citas` (
  `id_cita` int(11) NOT NULL AUTO_INCREMENT,
  `id_paciente` int(11) NOT NULL,
  `id_doctor` int(11) NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `id_consultas` int(11) NOT NULL,
  `estado` varchar(11) NOT NULL,
  PRIMARY KEY (`id_cita`),
  KEY `consultas-pacientes` (`id_consultas`),
  KEY `doctor-cita` (`id_doctor`),
  KEY `id_paciente` (`id_paciente`),
  CONSTRAINT `consultas-pacientes` FOREIGN KEY (`id_consultas`) REFERENCES `consultas` (`id_consultas`),
  CONSTRAINT `doctor-cita` FOREIGN KEY (`id_doctor`) REFERENCES `doctor` (`id_doctor`),
  CONSTRAINT `id_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;

/*Data for the table `citas` */

insert  into `citas`(`id_cita`,`id_paciente`,`id_doctor`,`fecha_cita`,`hora_cita`,`id_consultas`,`estado`) values 
(1,2,1,'2021-07-12','11:00:00',2,'A'),
(2,1,2,'2021-07-02','12:00:00',1,'A'),
(3,2,1,'2021-07-08','10:00:00',1,'I'),
(4,2,1,'2021-07-16','11:00:00',1,'I'),
(5,2,1,'2021-07-19','12:34:00',3,'I'),
(6,2,2,'2021-07-13','11:22:00',8,'I'),
(7,10,2,'2021-07-15','11:00:00',5,'I'),
(8,2,1,'2021-07-21','02:01:00',1,'I'),
(9,2,1,'2021-07-21','12:03:00',1,'I'),
(10,2,1,'2021-07-15','15:02:00',1,'I'),
(18,2,1,'2021-07-22','12:00:00',1,'I'),
(19,2,1,'2021-07-20','11:02:00',1,'I'),
(20,10,1,'2021-07-31','10:01:00',1,'I'),
(21,10,3,'2021-08-11','10:10:00',4,'I'),
(22,10,1,'1970-01-01','08:02:00',1,'I'),
(23,10,3,'2021-08-18','10:10:00',1,'I'),
(24,10,7,'2021-08-27','10:10:00',1,'A'),
(25,1,3,'2021-07-30','10:10:00',4,'I'),
(26,1,7,'2021-08-19','12:00:00',1,'A'),
(27,1,7,'2021-08-27','11:00:00',2,'I'),
(30,13,7,'2025-10-03','09:00:00',2,'A'),
(31,13,1,'2025-11-05','10:00:00',1,'A'),
(32,20,7,'2025-11-05','10:00:00',1,'A'),
(33,13,7,'2025-11-05','11:00:00',1,'A'),
(34,13,7,'2025-11-05','12:00:00',1,'I');

/*Table structure for table `consultas` */

DROP TABLE IF EXISTS `consultas`;

CREATE TABLE `consultas` (
  `id_consultas` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(255) NOT NULL,
  PRIMARY KEY (`id_consultas`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

/*Data for the table `consultas` */

insert  into `consultas`(`id_consultas`,`tipo`) values 
(1,'Revisión general'),
(2,'Limpieza bucal'),
(3,'Empastes'),
(4,'Endodoncia'),
(5,'Ortodoncia'),
(6,'Prótesis'),
(7,'Implantes'),
(8,'Cirugías bucales'),
(9,'Cosmética dental ');

/*Table structure for table `doctor` */

DROP TABLE IF EXISTS `doctor`;

CREATE TABLE `doctor` (
  `id_doctor` int(11) NOT NULL AUTO_INCREMENT,
  `nombreD` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `sexo` varchar(255) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `correo_eletronico` varchar(255) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `id_especialidad` int(255) NOT NULL,
  PRIMARY KEY (`id_doctor`),
  KEY `id_especialidad` (`id_especialidad`),
  CONSTRAINT `id_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/*Data for the table `doctor` */

insert  into `doctor`(`id_doctor`,`nombreD`,`apellido`,`sexo`,`fecha_nacimiento`,`telefono`,`correo_eletronico`,`clave`,`id_especialidad`) values 
(1,'Francisco','Rosario','Masculino','2001-07-05','8097983519','franciscoRosario@hotmail.com','1234',2),
(2,'Stewar','Diaz','Masculino','2000-07-16','8099891736','Stewar@company.com','1234',3),
(3,'Alernis','Hernandez','Femenino','2000-07-13','8097686677','arlenis@company.com','1234',4),
(7,'Juaan','Perez','Masculino','2000-07-20','809889013','juan@hotmail.com','1234',5);

/*Table structure for table `especialidad` */

DROP TABLE IF EXISTS `especialidad`;

CREATE TABLE `especialidad` (
  `id_especialidad` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(255) NOT NULL,
  PRIMARY KEY (`id_especialidad`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

/*Data for the table `especialidad` */

insert  into `especialidad`(`id_especialidad`,`tipo`) values 
(1,'Odontólogo general'),
(2,'Odontopediatra'),
(3,'Ortodoncista'),
(4,'Patólogo oral'),
(5,'Endodoncista');

/*Table structure for table `odontograma` */

DROP TABLE IF EXISTS `odontograma`;

CREATE TABLE `odontograma` (
  `id_odontograma` int(11) NOT NULL AUTO_INCREMENT,
  `id_paciente` int(11) NOT NULL,
  `diente` int(11) NOT NULL,
  `lado` varchar(50) NOT NULL,
  `procedimiento` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_odontograma`),
  KEY `id_paciente` (`id_paciente`),
  CONSTRAINT `odontograma_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Data for the table `odontograma` */

/*Table structure for table `paciente_diagnostico` */

DROP TABLE IF EXISTS `paciente_diagnostico`;

CREATE TABLE `paciente_diagnostico` (
  `id_diagnostico` int(11) NOT NULL AUTO_INCREMENT,
  `id_cita` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `medicina` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_diagnostico`),
  KEY `id_cita_fk` (`id_cita`),
  CONSTRAINT `id_cita_fk` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

/*Data for the table `paciente_diagnostico` */

insert  into `paciente_diagnostico`(`id_diagnostico`,`id_cita`,`descripcion`,`medicina`) values 
(12,1,'Todo bien','Listerin'),
(13,1,'Todo bien',''),
(14,2,'Bien',''),
(16,26,'adadada','adadadad');

/*Table structure for table `pacientes` */

DROP TABLE IF EXISTS `pacientes`;

CREATE TABLE `pacientes` (
  `id_paciente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `sexo` varchar(60) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `correo_electronico` varchar(255) NOT NULL,
  `clave` varchar(255) NOT NULL,
  PRIMARY KEY (`id_paciente`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;

/*Data for the table `pacientes` */

insert  into `pacientes`(`id_paciente`,`nombre`,`apellido`,`cedula`,`telefono`,`sexo`,`fecha_nacimiento`,`correo_electronico`,`clave`) values 
(1,'Edward','Brito Diaza','','8498779910','Masculino','2001-07-11','edwardbrito11@hotmail.com','12'),
(2,'Yessica Maria','Villavizar','','891892281','Femenino','2001-11-06','yessicavillavizar@hotmail.com','12'),
(10,'      Jose Alberto','    Nuñez','','89098878193','Masculino','2000-07-07','jose@hotmail.com','122'),
(12,'Mathias','Olmedo','','0983361289','Masculino','2002-10-10','glol5489@gmail.com','olmedofra1208'),
(13,'Mathias','Javier ','','0983361289','Masculino','2002-10-10','mathias@gmail.com','123456'),
(18,'pruebaa','baprue','','0982278923','Masculino','0000-00-00','prueba@gmail.com','1234'),
(19,'Sandraa','Penayo','','0992534504','Femenino','2000-01-02','sandrapenayo3@gmail.com','123455'),
(20,'andres','barreto','','0981548850','Masculino','1996-06-18','barreto@gmail.com','1234'),
(21,'Cristiano','Ronaldo','4589652','0985478568','Masculino','1995-04-15','cristiano@gmail.com','12345');

/*Table structure for table `pagos` */

DROP TABLE IF EXISTS `pagos`;

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_presupuesto` int(11) NOT NULL,
  `id_paciente` int(11) DEFAULT NULL,
  `id_secretaria` int(11) DEFAULT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `monto` decimal(10,2) NOT NULL,
  `saldo_restante` decimal(10,2) DEFAULT 0.00,
  `metodo` enum('efectivo','transferencia','tarjeta') DEFAULT 'efectivo',
  `observacion` text DEFAULT NULL,
  `recibo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `id_presupuesto` (`id_presupuesto`),
  KEY `id_paciente` (`id_paciente`),
  KEY `id_secretaria` (`id_secretaria`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_presupuesto`) REFERENCES `presupuesto` (`id_presupuesto`) ON DELETE CASCADE,
  CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE SET NULL,
  CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`id_secretaria`) REFERENCES `secretaria` (`id_secretaria`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

/*Data for the table `pagos` */

insert  into `pagos`(`id_pago`,`id_presupuesto`,`id_paciente`,`id_secretaria`,`fecha_pago`,`monto`,`saldo_restante`,`metodo`,`observacion`,`recibo_path`) values 
(1,25,NULL,NULL,'2025-11-05 11:04:58',800000.00,0.00,'efectivo','',NULL),
(2,26,NULL,NULL,'2025-11-05 15:13:29',500000.00,0.00,'efectivo','pago ',NULL),
(3,14,19,NULL,'2025-11-05 19:05:45',50000.00,0.00,'efectivo','',NULL),
(4,27,NULL,NULL,'2025-11-05 20:07:55',100000.00,0.00,'efectivo','',NULL);

/*Table structure for table `presupuesto` */

DROP TABLE IF EXISTS `presupuesto`;

CREATE TABLE `presupuesto` (
  `id_presupuesto` int(11) NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_doctor` int(11) NOT NULL,
  `id_paciente` int(11) DEFAULT NULL,
  `paciente_nombre` varchar(120) NOT NULL,
  `paciente_correo` varchar(160) DEFAULT NULL,
  `paciente_telefono` varchar(50) DEFAULT NULL,
  `paciente_documento` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','enviado','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `enviado_at` datetime DEFAULT NULL,
  `enviado_via` enum('whatsapp','email','otro') DEFAULT NULL,
  `enviado_a` varchar(40) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_presupuesto`),
  UNIQUE KEY `folio` (`folio`),
  KEY `id_doctor` (`id_doctor`),
  KEY `idx_presupuesto_id_paciente` (`id_paciente`),
  CONSTRAINT `fk_presupuesto_doctor` FOREIGN KEY (`id_doctor`) REFERENCES `doctor` (`id_doctor`),
  CONSTRAINT `fk_presupuesto_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4;

/*Data for the table `presupuesto` */

insert  into `presupuesto`(`id_presupuesto`,`folio`,`fecha`,`id_doctor`,`id_paciente`,`paciente_nombre`,`paciente_correo`,`paciente_telefono`,`paciente_documento`,`total`,`estado`,`enviado_at`,`enviado_via`,`enviado_a`,`pdf_path`) values 
(1,'PT-2025-03817','2025-10-20 20:43:37',7,13,'Mathias Olmedo','mathias@gmail.com','0983361289','6129024',190000.00,'enviado',NULL,NULL,NULL,NULL),
(2,'PT-2025-03946','2025-10-20 20:45:46',7,13,'Mathias Olmedo','mathias@gmail.com','0983361289','6129024',700000.00,'pendiente',NULL,NULL,NULL,NULL),
(3,'PT-2025-04515','2025-10-20 20:55:15',7,13,'Mathias Javier','mathias@gmail.com','0983361289','',700000.00,'pendiente',NULL,NULL,NULL,NULL),
(4,'PT-2025-07663','2025-10-20 21:47:43',7,19,'Sandra Penayo','sandrapenayo3@gmail.com','0992534504','',200000.00,'pendiente',NULL,NULL,NULL,NULL),
(5,'PT-2025-89434','2025-10-21 20:30:34',7,19,'Sandra Penayo','sandrapenayo3@gmail.com','0992534504','',500000.00,'pendiente',NULL,NULL,NULL,NULL),
(6,'PT-2025-94043','2025-10-21 21:47:23',7,13,'Mathias Javier','mathias@gmail.com','0983361289','',500000.00,'pendiente',NULL,NULL,NULL,NULL),
(7,'PT-2025-94640','2025-10-21 21:57:20',7,19,'Sandra Penayo','sandrapenayo3@gmail.com','0992534504','',150000.00,'pendiente',NULL,NULL,NULL,NULL),
(8,'PT-2025-94812','2025-10-21 22:00:12',7,19,'Sandra Penayo','sandrapenayo3@gmail.com','0992534504','',200000.00,'pendiente',NULL,NULL,NULL,NULL),
(9,'PT-2025-96608','2025-10-21 22:30:09',7,NULL,'Jose Alberto     Nuñez','jose@hotmail.com','89098878193','',500000.00,'pendiente',NULL,NULL,NULL,NULL),
(10,'PT-2025-96920','2025-10-21 22:35:20',7,NULL,'Jose Alberto     Nuñez','jose@hotmail.com','89098878193','',500000.00,'pendiente',NULL,NULL,NULL,NULL),
(11,'PT-2025-97204','2025-10-21 22:40:04',7,NULL,'Jose Alberto     Nuñez','jose@hotmail.com','89098878193','',500000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_11.pdf'),
(12,'PT-2025-97268','2025-10-21 22:41:08',7,19,'Sandra Penayo','sandrapenayo3@gmail.com','0992534504','',750000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_12.pdf'),
(13,'PT-2025-73205','2025-10-22 19:46:45',7,13,'Mathias Javier','mathias@gmail.com','0983361289','',920000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_13.pdf'),
(14,'PT-2025-73739','2025-10-22 19:55:39',7,19,'Sandra Penayo','sandrapenayo3@gmail.com','0992534504','',100000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_14.pdf'),
(15,'PT-2025-02916','2025-11-04 21:35:16',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_15.pdf'),
(16,'PT-2025-02922','2025-11-04 21:35:22',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_16.pdf'),
(17,'PT-2025-02925','2025-11-04 21:35:25',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_17.pdf'),
(18,'PT-2025-02926','2025-11-04 21:35:26',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_18.pdf'),
(19,'PT-2025-02927','2025-11-04 21:35:27',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_19.pdf'),
(20,'PT-2025-02928','2025-11-04 21:35:28',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_20.pdf'),
(21,'PT-2025-03212','2025-11-04 21:40:12',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'enviado','2025-11-05 23:41:25','whatsapp','595983361289','presupuestos/presupuesto_21.pdf'),
(22,'PT-2025-03214','2025-11-04 21:40:14',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'enviado','2025-11-05 23:38:33','whatsapp','595983361289','presupuestos/presupuesto_22.pdf'),
(23,'PT-2025-03215','2025-11-04 21:40:15',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'enviado','2025-11-05 19:37:15','whatsapp','595983361289','presupuestos/presupuesto_23.pdf'),
(24,'PT-2025-03216','2025-11-04 21:40:16',7,NULL,'Mathias Olmedo','glol5489@gmail.com','0983361289','',200000.00,'enviado','2025-11-05 15:18:42','whatsapp','595983361289','presupuestos/presupuesto_24.pdf'),
(25,'PT-2025-46493','2025-11-05 09:41:33',7,NULL,'pruebaa baprue','prueba@gmail.com','0982278923','',1000000.00,'enviado','2025-11-05 13:37:19','whatsapp','595982278923','presupuestos/presupuesto_25.pdf'),
(26,'PT-2025-66197','2025-11-05 15:09:57',7,NULL,'andres barreto','barreto@gmail.com','0981548850','',750000.00,'enviado','2025-11-05 15:12:57','whatsapp','595981548850','presupuestos/presupuesto_26.pdf'),
(27,'PT-2025-81521','2025-11-05 19:25:21',7,13,'Mathias Javier','mathias@gmail.com','0983361289','',800000.00,'enviado','2025-11-05 19:36:09','whatsapp','595983361289','presupuestos/presupuesto_27.pdf'),
(28,'PT-2025-09234','2025-11-11 22:00:34',7,21,'Cristiano Ronaldo','cristiano@gmail.com','0985478568','',260000.00,'pendiente',NULL,NULL,NULL,'presupuestos/presupuesto_28.pdf'),
(29,'PT-2025-09238','2025-11-11 22:00:38',7,21,'Cristiano Ronaldo','cristiano@gmail.com','0985478568','',260000.00,'enviado',NULL,NULL,NULL,'presupuestos/presupuesto_29.pdf'),
(30,'PT-2025-09239','2025-11-11 22:00:39',7,21,'Cristiano Ronaldo','cristiano@gmail.com','0985478568','',260000.00,'aprobado','2025-11-13 23:26:48','whatsapp','595985478568','presupuestos/presupuesto_30.pdf'),
(36,'PT-2025-09240','2025-11-11 22:00:40',7,21,'Cristiano Ronaldo','cristiano@gmail.com','0985478568','',260000.00,'enviado','2025-11-13 23:25:52','whatsapp','595985478568','presupuestos/presupuesto_36.pdf'),
(37,'PT-2025-09241','2025-11-11 22:00:41',7,21,'Cristiano Ronaldo','cristiano@gmail.com','0985478568','',260000.00,'enviado',NULL,NULL,NULL,'presupuestos/presupuesto_37.pdf'),
(38,'PT-2025-56503','2025-11-12 11:08:23',7,20,'andres barreto','barreto@gmail.com','0981548850','',360000.00,'enviado','2025-11-12 15:09:39','whatsapp','595981548850','presupuestos/presupuesto_38.pdf'),
(39,'PT-2025-75990','2025-11-13 20:19:50',7,12,'Mathias Olmedo','glol5489@gmail.com','0983361289','',180000.00,'aprobado',NULL,NULL,NULL,'presupuestos/presupuesto_39.pdf');

/*Table structure for table `presupuesto_detalle` */

DROP TABLE IF EXISTS `presupuesto_detalle`;

CREATE TABLE `presupuesto_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_presupuesto` int(11) NOT NULL,
  `diente` varchar(5) NOT NULL,
  `lado` varchar(20) NOT NULL,
  `procedimiento` varchar(80) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_presupuesto` (`id_presupuesto`),
  CONSTRAINT `fk_detalle_presupuesto` FOREIGN KEY (`id_presupuesto`) REFERENCES `presupuesto` (`id_presupuesto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4;

/*Data for the table `presupuesto_detalle` */

insert  into `presupuesto_detalle`(`id`,`id_presupuesto`,`diente`,`lado`,`procedimiento`,`precio`,`observacion`) values 
(1,1,'17','Derecho','Endodoncia',190000.00,'endodoncia'),
(2,2,'15','Derecho','Caries',500000.00,'El paciente tiene caries '),
(3,2,'43','Lateral','Endodoncia',200000.00,'Necesita endodoncia '),
(4,3,'15','Izquierdo','Caries',200000.00,'caries'),
(5,3,'11','Posterior','Implante',500000.00,'implante '),
(6,4,'15','Frontal','Amalgama',200000.00,'Amalgama'),
(7,5,'18','Derecho','Resina',500000.00,'Resina urgente'),
(8,6,'16','Posterior','Amalgama',500000.00,'adadad'),
(9,7,'45','Izquierdo','Caries',150000.00,'Caries urgente'),
(10,8,'14','Derecho','Caries',200000.00,'adadad'),
(11,9,'46','Lateral','Implante',500000.00,'Implante urgente'),
(12,10,'46','Lateral','Implante',500000.00,'Implante urgente'),
(13,11,'46','Lateral','Implante',500000.00,'Implante urgente'),
(14,12,'15','Izquierdo','Caries',500000.00,'Sandra tiene caries '),
(15,12,'11','Frontal','Endodoncia',250000.00,'Endodoncia urgente'),
(16,13,'13','Izquierdo','Caries',420000.00,'El paciente tiene caries'),
(17,13,'42','Frontal','Implante',500000.00,'El paciente necesita implante '),
(18,14,'15','Frontal','Caries',100000.00,'hola\n'),
(19,15,'14','null','caries',200000.00,''),
(20,16,'14','null','caries',200000.00,''),
(21,17,'14','null','caries',200000.00,''),
(22,18,'14','null','caries',200000.00,''),
(23,19,'14','null','caries',200000.00,''),
(24,20,'14','null','caries',200000.00,''),
(25,21,'15','Lateral','Caries',200000.00,''),
(26,22,'15','Lateral','Caries',200000.00,''),
(27,23,'15','Lateral','Caries',200000.00,''),
(28,24,'15','Lateral','Caries',200000.00,''),
(29,25,'17','Posterior','Implante',1000000.00,'Implante necesario'),
(30,26,'15','Izquierdo','Caries',500000.00,''),
(31,26,'11','Frontal','Resina',250000.00,''),
(32,27,'11','Frontal','Amalgama',800000.00,''),
(33,28,'15','Derecho','Caries',120000.00,'TIene caries '),
(34,28,'11','Lateral','Caries',140000.00,'Caries intradental\n'),
(35,29,'15','Derecho','Caries',120000.00,'TIene caries '),
(36,29,'11','Lateral','Caries',140000.00,'Caries intradental\n'),
(37,30,'15','Derecho','Caries',120000.00,'TIene caries '),
(38,30,'11','Lateral','Caries',140000.00,'Caries intradental\n'),
(39,36,'15','Derecho','Caries',120000.00,'TIene caries '),
(40,36,'11','Lateral','Caries',140000.00,'Caries intradental\n'),
(41,37,'15','Derecho','Caries',120000.00,'TIene caries '),
(42,37,'11','Lateral','Caries',140000.00,'Caries intradental\n'),
(43,38,'15','Izquierdo','Caries',170000.00,'adaad'),
(44,38,'21','Frontal','Resina',190000.00,'ada'),
(45,39,'13','Izquierdo','Caries',180000.00,'zdz');

/*Table structure for table `secretaria` */

DROP TABLE IF EXISTS `secretaria`;

CREATE TABLE `secretaria` (
  `id_secretaria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `correo_electronico` varchar(160) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `telefono` varchar(40) DEFAULT NULL,
  `estado` enum('A','I') NOT NULL DEFAULT 'A',
  PRIMARY KEY (`id_secretaria`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

/*Data for the table `secretaria` */

insert  into `secretaria`(`id_secretaria`,`nombre`,`apellido`,`correo_electronico`,`clave`,`telefono`,`estado`) values 
(1,'Secretaria','General','secretaria@clinic.com','secret123','5959XXXXXXX','A');

/*Table structure for table `superadmin` */

DROP TABLE IF EXISTS `superadmin`;

CREATE TABLE `superadmin` (
  `id_superadmin` int(11) NOT NULL AUTO_INCREMENT,
  `correo_electronico` varchar(255) NOT NULL,
  `clave` varchar(255) NOT NULL,
  PRIMARY KEY (`id_superadmin`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

/*Data for the table `superadmin` */

insert  into `superadmin`(`id_superadmin`,`correo_electronico`,`clave`) values 
(1,'admin@admin.com','admin');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
