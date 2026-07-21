/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

DROP TABLE IF EXISTS `asistencia_docentes`;
CREATE TABLE `asistencia_docentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `docente_id` int NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('Presente','Inasistente') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `registrado_por` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `docente_id` (`docente_id`),
  CONSTRAINT `asistencia_docentes_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `asistencia_docentes` WRITE;
UNLOCK TABLES;

DROP TABLE IF EXISTS `asistencias`;
CREATE TABLE `asistencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `sesion_id` int NOT NULL,
  `estado` enum('Presente','Inasistente','Tardanza','Justificado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registrado_por` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencia_estudiante_sesion` (`estudiante_id`,`sesion_id`),
  KEY `sesion_id` (`sesion_id`),
  KEY `idx_asistencias_estudiante_estado` (`estudiante_id`,`estado`),
  CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `asistencias` WRITE;
UNLOCK TABLES;

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `modulo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalles` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `auditoria` WRITE;
UNLOCK TABLES;

DROP TABLE IF EXISTS `auditoria_asistencias`;
CREATE TABLE `auditoria_asistencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asistencia_id` int NOT NULL,
  `estudiante_id` int NOT NULL,
  `sesion_id` int NOT NULL,
  `estado_anterior` enum('Presente','Inasistente','Tardanza','Justificado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_nuevo` enum('Presente','Inasistente','Tardanza','Justificado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modificado_por` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo_cambio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asistencia_id` (`asistencia_id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `sesion_id` (`sesion_id`),
  CONSTRAINT `auditoria_asistencias_ibfk_1` FOREIGN KEY (`asistencia_id`) REFERENCES `asistencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auditoria_asistencias_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auditoria_asistencias_ibfk_3` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `docentes`;
CREATE TABLE `docentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `programa_id` int DEFAULT NULL,
  `unidad_didactica_id` int DEFAULT NULL,
  `seccion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_docentes_programa` (`programa_id`),
  KEY `fk_docentes_unidad` (`unidad_didactica_id`),
  CONSTRAINT `fk_docentes_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_docentes_unidad` FOREIGN KEY (`unidad_didactica_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=902 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `docentes` WRITE;
INSERT INTO `docentes` VALUES ('901','DOC-DEMO','Demo Docente','99999991','demo.docente@vrht.edu.pe','1','1','A','docente','Activo');
UNLOCK TABLES;

DROP TABLE IF EXISTS `estudiantes`;
CREATE TABLE `estudiantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `programa_id` int DEFAULT NULL,
  `periodo_curricular_id` int DEFAULT NULL,
  `unidad_didactica_id` int DEFAULT NULL,
  `seccion` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_sesiones` int NOT NULL DEFAULT '0',
  `inasistencias` int NOT NULL DEFAULT '0',
  `estado` enum('Activo','En riesgo','Inhabilitado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_estudiantes_programa` (`programa_id`),
  KEY `fk_estudiantes_periodo` (`periodo_curricular_id`),
  KEY `fk_estudiantes_unidad` (`unidad_didactica_id`),
  KEY `idx_estudiantes_programa` (`programa_id`,`periodo_curricular_id`),
  CONSTRAINT `fk_estudiantes_periodo` FOREIGN KEY (`periodo_curricular_id`) REFERENCES `periodos_curriculares` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudiantes_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudiantes_unidad` FOREIGN KEY (`unidad_didactica_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `estudiantes` WRITE;
INSERT INTO `estudiantes` VALUES ('902','EST-DEMO','99999992','Demo Estudiante','1','1','1','A','2','0','Activo');
UNLOCK TABLES;

DROP TABLE IF EXISTS `horarios`;
CREATE TABLE `horarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `programa_id` int NOT NULL,
  `unidad_didactica_id` int NOT NULL,
  `docente_id` int NOT NULL,
  `seccion` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `programa_id` (`programa_id`),
  KEY `unidad_didactica_id` (`unidad_didactica_id`),
  KEY `docente_id` (`docente_id`),
  CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `horarios_ibfk_2` FOREIGN KEY (`unidad_didactica_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `horarios_ibfk_3` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `horarios` WRITE;
UNLOCK TABLES;

DROP TABLE IF EXISTS `modulos_formativos`;
CREATE TABLE `modulos_formativos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `programa_id` int NOT NULL,
  `numero` int NOT NULL,
  `nombre` varchar(220) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  KEY `programa_id` (`programa_id`),
  CONSTRAINT `modulos_formativos_ibfk_1` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `modulos_formativos` WRITE;
INSERT INTO `modulos_formativos` VALUES ('1','1','1','Módulo I - Desarrollo de Software y Gestión de Base de Datos','Activo'),
('2','1','2','Módulo II - Desarrollo de Soluciones y Aplicaciones','Activo'),
('3','1','3','Módulo III - Integración y Sistemas Empresariales','Activo'),
('4','2','1','Módulo I - Asistencia Contable','Activo'),
('5','2','2','Módulo II - Análisis Contable','Activo'),
('6','2','3','Módulo III - Gestión Financiera','Activo'),
('7','3','1','Módulo I - Promoción de la Salud','Activo'),
('8','3','2','Módulo II - Asistencia Hospitalaria','Activo'),
('9','3','3','Módulo III - Cuidados Especializados','Activo');
UNLOCK TABLES;

DROP TABLE IF EXISTS `periodos_academicos`;
CREATE TABLE `periodos_academicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('Activo','Cerrado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `periodos_academicos` WRITE;
INSERT INTO `periodos_academicos` VALUES ('1','2026-I','2026-03-01','2026-07-31','Activo'),
('2','2025-II','2025-08-01','2025-12-31','Cerrado');
UNLOCK TABLES;

DROP TABLE IF EXISTS `periodos_curriculares`;
CREATE TABLE `periodos_curriculares` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modulo_id` int NOT NULL,
  `nombre` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modulo_id` (`modulo_id`),
  CONSTRAINT `periodos_curriculares_ibfk_1` FOREIGN KEY (`modulo_id`) REFERENCES `modulos_formativos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `periodos_curriculares` WRITE;
INSERT INTO `periodos_curriculares` VALUES ('1','1','I'),
('2','1','II'),
('3','2','III'),
('4','2','IV'),
('5','3','V'),
('6','3','VI'),
('7','4','I'),
('8','4','II'),
('9','5','III'),
('10','5','IV'),
('11','6','V'),
('12','6','VI'),
('13','7','I'),
('14','7','II'),
('15','8','III'),
('16','8','IV'),
('17','9','V'),
('18','9','VI');
UNLOCK TABLES;

DROP TABLE IF EXISTS `programas`;
CREATE TABLE `programas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `programas` WRITE;
INSERT INTO `programas` VALUES ('1','DSI','Desarrollo de Sistemas de Información','Activo'),
('2','CON','Contabilidad','Activo'),
('3','ENF','Enfermería Técnica','Activo');
UNLOCK TABLES;

DROP TABLE IF EXISTS `respaldos`;
CREATE TABLE `respaldos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `tamanio` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sesiones`;
CREATE TABLE `sesiones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `programa_id` int DEFAULT NULL,
  `unidad_didactica_id` int DEFAULT NULL,
  `seccion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `docente_id` int DEFAULT NULL,
  `periodo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2026-I',
  `estado` enum('Pendiente','Registrada','Cerrada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id`),
  KEY `fk_sesiones_docente` (`docente_id`),
  KEY `fk_sesiones_programa` (`programa_id`),
  KEY `fk_sesiones_unidad` (`unidad_didactica_id`),
  KEY `idx_sesiones_fecha_hora` (`fecha`,`hora`),
  CONSTRAINT `fk_sesiones_docente` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sesiones_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sesiones_unidad` FOREIGN KEY (`unidad_didactica_id`) REFERENCES `unidades_didacticas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `sesiones` WRITE;
UNLOCK TABLES;

DROP TABLE IF EXISTS `unidades_didacticas`;
CREATE TABLE `unidades_didacticas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `periodo_curricular_id` int NOT NULL,
  `nombre` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id`),
  KEY `periodo_curricular_id` (`periodo_curricular_id`),
  CONSTRAINT `unidades_didacticas_ibfk_1` FOREIGN KEY (`periodo_curricular_id`) REFERENCES `periodos_curriculares` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `unidades_didacticas` WRITE;
INSERT INTO `unidades_didacticas` VALUES ('1','1','Fundamentos de Programación','Activo'),
('2','1','Arquitectura de Entornos Web','Activo'),
('3','1','Interfaz Gráfica de Usuario','Activo'),
('4','1','Tecnologías de Información y Comunicación','Activo'),
('5','1','Mantenimiento de Equipos de Cómputo','Activo'),
('6','2','Estructura de Datos y Programación Orientada a Objetos','Activo'),
('7','2','Desarrollo de Entornos Web','Activo'),
('8','2','Prototipos Web y Móviles','Activo'),
('9','2','Sistemas de Información','Activo'),
('10','2','Reparación de Equipos de Cómputo','Activo'),
('11','3','Lenguaje de Programación Visual','Activo'),
('12','3','Programación Web','Activo'),
('13','3','Base de Datos','Activo'),
('14','3','Análisis y Diseño de Sistemas','Activo'),
('15','3','Diseño de Redes de Comunicación','Activo'),
('16','4','Programación Distribuida','Activo'),
('17','4','Aplicaciones Web','Activo'),
('18','4','Administración de Base de Datos','Activo'),
('19','4','Seguridad de la Información','Activo'),
('20','4','Configuración de Redes de Comunicación','Activo'),
('21','5','Diseño de Aplicaciones Móviles','Activo'),
('22','5','Desarrollo de Soluciones Web','Activo'),
('23','5','Desarrollo de Proyecto TI','Activo'),
('24','5','Calidad de Software','Activo'),
('25','5','Marketing Digital','Activo'),
('26','6','Desarrollo de Aplicaciones Móviles','Activo'),
('27','6','Integración de Sistemas Empresariales','Activo'),
('28','6','Desarrollo de Sistemas de Información','Activo'),
('29','6','Sistema de Gestión de Contenidos','Activo'),
('30','7','Principios Contables','Activo'),
('31','7','Documentación Comercial y Contable','Activo'),
('32','7','Legislación Tributaria','Activo'),
('33','7','Registro de Libros Principales','Activo'),
('34','8','Administración General','Activo'),
('35','8','Legislación Mercantil y Societaria','Activo'),
('36','8','Legislación Laboral','Activo'),
('37','8','Registro de Libros Auxiliares','Activo'),
('38','8','Planeamiento Estratégico','Activo'),
('39','9','Calculo Financiero','Activo'),
('40','9','Tributación y Tratamiento Contable','Activo'),
('41','9','Contabilidad de Sociedades Mercantiles','Activo'),
('42','9','Sistemas Administrativos del Sector Público','Activo'),
('43','9','Técnica Presupuestal','Activo'),
('44','10','Supervisión de Operadores Contables','Activo'),
('45','10','Contabilidad de Costos','Activo'),
('46','10','Contabilidad Gubernamental','Activo'),
('47','10','Aplicativos Informáticos Contables','Activo'),
('48','10','Planeamiento y Control Financiero','Activo'),
('49','11','Formulación de Estados Financieros','Activo'),
('50','11','Contabilidad de Entidades Financieras','Activo'),
('51','11','Procesos de Auditoria','Activo'),
('52','11','Análisis Estadístico Contable','Activo'),
('53','11','Gestión Financiera','Activo'),
('54','12','Finanzas Empresariales','Activo'),
('55','12','Auditoria Tributaria','Activo'),
('56','12','Análisis e Interpretación de los Estados Financieros','Activo'),
('57','12','Formulación y Evaluación de Proyectos','Activo'),
('58','13','Anatomía y Fisiología Humana','Activo'),
('59','13','Primeros Auxilios','Activo'),
('60','13','Epidemiología','Activo'),
('61','13','Educación para la Salud','Activo'),
('62','13','Salud en Desastres Naturales','Activo'),
('63','14','Salud Comunitaria','Activo'),
('64','14','Inmunizaciones','Activo'),
('65','14','Salud Pública','Activo'),
('66','14','Matemática Aplicada a la salud','Activo'),
('67','14','Salud Ocupacional','Activo'),
('68','15','Documentación en Salud','Activo'),
('69','15','Bioseguridad en Salud','Activo'),
('70','15','Básica Hospitalaria','Activo'),
('71','15','Patología','Activo'),
('72','16','Muestras Biológicas','Activo'),
('73','16','Procedimientos Invasivos y no Invasivos','Activo'),
('74','16','Nutrición y Dietas','Activo'),
('75','16','Administración de Medicamentos','Activo'),
('76','17','Salud Materna','Activo'),
('77','17','Salud Mental','Activo'),
('78','17','Médico Quirúrgico','Activo'),
('79','17','Adulto Mayor','Activo'),
('80','18','Salud del Niño y Adolescente','Activo'),
('81','18','Medicina Alternativa','Activo'),
('82','18','Fisioterapia y Rehabilitación','Activo'),
('83','18','Salud Bucal','Activo');
UNLOCK TABLES;

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','docente','estudiante') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `correo` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `usuarios` WRITE;
INSERT INTO `usuarios` VALUES ('1','Admin Principal','admin','$2y$10$FVviD8RbVSmSnbDVD0ysDu3qOJp/gedARst6MPyN14GCR7M5hZGtm','admin','Activo','admin@vrht.edu.pe'),
('901','Demo Docente','docente','$2y$10$9v2hC.Iq7o6lq9GE5m0IXOwfW9Qe.NVtmCXIADLL.GkEmCHykG3um','docente','Activo','demo.docente@vrht.edu.pe'),
('902','Demo Estudiante','estudiante','$2y$10$kVLMkkV98n/mI2H5AdE3Z.KZG.E23WHjMOQ4574vG9RdfvluaYz4W','estudiante','Activo','demo.estudiante@vrht.edu.pe');
UNLOCK TABLES;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
