-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306	
-- Tiempo de generación: 20-11-2025 a las 03:49:56
-- Versión del servidor: 8.0.41
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `siete_veintitres`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `nombre_admin` varchar(100) NOT NULL,
  `email_admin` varchar(100) NOT NULL,
  `telefono_admin` varchar(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`id_admin`, `nombre_admin`, `email_admin`, `telefono_admin`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Carlos Mancilla', 'cmmc.0411@gmail.com', '7471491562', '$2b$12$Uq1fhGncoYTuu3G0H8WorevLIkHv0Q9qLJjTKv3Vo7zI0gn99.71i', '2025-10-08 03:30:29', '2025-11-16 03:43:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id_asistencia` int NOT NULL,
  `id_rider` int NOT NULL,
  `id_horario` int NOT NULL,
  `numero_bici` int DEFAULT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus_asistencia` enum('pendiente','presente','ausente') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bicicletas`
--

CREATE TABLE `bicicletas` (
  `id_bicicleta` int NOT NULL,
  `estado` enum('Disponible','Mantenimiento','Fuera de Servicio') DEFAULT 'Disponible',
  `descripcion` text,
  `fecha_registro` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cafeteria`
--

CREATE TABLE `cafeteria` (
  `id_venta_cafeteria` int NOT NULL,
  `producto` varchar(100) NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) GENERATED ALWAYS AS ((`cantidad` * `precio_unitario`)) STORED,
  `vendedor` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `coaches`
--

CREATE TABLE `coaches` (
  `id_coach` int NOT NULL,
  `nombre_coach` varchar(100) NOT NULL,
  `email_coach` varchar(100) NOT NULL,
  `telefono_coach` varchar(10) NOT NULL,
  `rfc_coach` varchar(13) NOT NULL,
  `estatus` enum('Activo','Inactivo') DEFAULT 'Activo',
  `foto_coach` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `esta_activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresos`
--

CREATE TABLE `egresos` (
  `id_egreso` int NOT NULL,
  `tipo_egreso` varchar(50) DEFAULT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `responsable` varchar(100) NOT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_imagen` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_clases`
--

CREATE TABLE `horario_clases` (
  `id_horario` int NOT NULL,
  `nombre_clase_especifico` varchar(100) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `id_coach` int NOT NULL,
  `cupo_maximo` int NOT NULL,
  `estatus` enum('Programada','Cancelada','Realizada') DEFAULT 'Programada',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

CREATE TABLE `ingresos` (
  `id_ingreso` int NOT NULL,
  `tipo_ingreso` enum('Membresia','Visita','Otro','Venta Cafeteria') NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `responsable` varchar(100) NOT NULL,
  `id_rider` int DEFAULT NULL,
  `id_venta_cafeteria` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id_mantenimiento` int NOT NULL,
  `id_bicicleta` int NOT NULL,
  `descripcion` text,
  `responsable` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `membresias`
--

CREATE TABLE `membresias` (
  `id_membresia` int NOT NULL,
  `id_rider` int NOT NULL,
  `id_tipo_membresia` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` enum('Activa','Vencida','Inactiva','Por Vencer') DEFAULT 'Activa',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `clases_restantes` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_coaches`
--

CREATE TABLE `perfiles_coaches` (
  `id` int NOT NULL,
  `coach_id` int NOT NULL,
  `especialidad` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_img_perfil` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_img_gustos` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `link_facebook` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link_instagram` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservaciones`
--

CREATE TABLE `reservaciones` (
  `id_reservacion` int NOT NULL,
  `id_rider` int NOT NULL,
  `id_horario` int NOT NULL,
  `id_bicicleta` int DEFAULT NULL,
  `fecha_reserva` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus` enum('Activa','Cancelada') DEFAULT 'Activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `riders`
--

CREATE TABLE `riders` (
  `id_rider` int NOT NULL,
  `codigo_acceso` varchar(4) NOT NULL,
  `nombre_rider` varchar(100) NOT NULL,
  `email_rider` varchar(100) NOT NULL,
  `telefono_rider` varchar(10) NOT NULL,
  `foto_rider` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `esta_activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Activo, 0=Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_membresia`
--

CREATE TABLE `tipos_membresia` (
  `id_tipo_membresia` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `precio` decimal(10,2) NOT NULL,
  `periodo` varchar(50) DEFAULT NULL,
  `limite_clases` int DEFAULT NULL,
  `caracteristicas` text,
  `es_popular` tinyint(1) DEFAULT '0',
  `estatus` enum('Activo','Inactivo') DEFAULT 'Activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL,
  `id_rider` int DEFAULT NULL,
  `id_coach` int DEFAULT NULL,
  `id_admin` int DEFAULT NULL,
  `tipo_usuario` enum('rider','coach','admin') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_rider`, `id_coach`, `id_admin`, `tipo_usuario`, `created_at`, `updated_at`) VALUES
(3, NULL, NULL, 1, 'admin', '2025-10-08 03:48:02', '2025-10-08 03:48:02');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email_admin` (`email_admin`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `id_rider` (`id_rider`),
  ADD KEY `id_horario` (`id_horario`);

--
-- Indices de la tabla `bicicletas`
--
ALTER TABLE `bicicletas`
  ADD PRIMARY KEY (`id_bicicleta`);

--
-- Indices de la tabla `cafeteria`
--
ALTER TABLE `cafeteria`
  ADD PRIMARY KEY (`id_venta_cafeteria`);

--
-- Indices de la tabla `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id_coach`),
  ADD UNIQUE KEY `email_coach` (`email_coach`),
  ADD UNIQUE KEY `rfc_coach` (`rfc_coach`);

--
-- Indices de la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id_egreso`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `horario_clases`
--
ALTER TABLE `horario_clases`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `id_coach` (`id_coach`);

--
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id_ingreso`),
  ADD KEY `id_rider` (`id_rider`),
  ADD KEY `id_venta_cafeteria` (`id_venta_cafeteria`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id_mantenimiento`),
  ADD KEY `id_bicicleta` (`id_bicicleta`);

--
-- Indices de la tabla `membresias`
--
ALTER TABLE `membresias`
  ADD PRIMARY KEY (`id_membresia`),
  ADD KEY `id_rider` (`id_rider`),
  ADD KEY `id_tipo_membresia` (`id_tipo_membresia`);

--
-- Indices de la tabla `perfiles_coaches`
--
ALTER TABLE `perfiles_coaches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coach_id` (`coach_id`);

--
-- Indices de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD PRIMARY KEY (`id_reservacion`),
  ADD KEY `id_rider` (`id_rider`),
  ADD KEY `id_horario` (`id_horario`),
  ADD KEY `fk_reservaciones_bicicletas` (`id_bicicleta`);

--
-- Indices de la tabla `riders`
--
ALTER TABLE `riders`
  ADD PRIMARY KEY (`id_rider`),
  ADD UNIQUE KEY `codigo_acceso` (`codigo_acceso`),
  ADD UNIQUE KEY `email_rider` (`email_rider`);

--
-- Indices de la tabla `tipos_membresia`
--
ALTER TABLE `tipos_membresia`
  ADD PRIMARY KEY (`id_tipo_membresia`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `id_rider` (`id_rider`),
  ADD KEY `id_coach` (`id_coach`),
  ADD KEY `id_admin` (`id_admin`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id_asistencia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `bicicletas`
--
ALTER TABLE `bicicletas`
  MODIFY `id_bicicleta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `cafeteria`
--
ALTER TABLE `cafeteria`
  MODIFY `id_venta_cafeteria` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id_coach` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `egresos`
--
ALTER TABLE `egresos`
  MODIFY `id_egreso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `horario_clases`
--
ALTER TABLE `horario_clases`
  MODIFY `id_horario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id_ingreso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id_mantenimiento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `membresias`
--
ALTER TABLE `membresias`
  MODIFY `id_membresia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `perfiles_coaches`
--
ALTER TABLE `perfiles_coaches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  MODIFY `id_reservacion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `riders`
--
ALTER TABLE `riders`
  MODIFY `id_rider` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `tipos_membresia`
--
ALTER TABLE `tipos_membresia`
  MODIFY `id_tipo_membresia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`id_rider`) REFERENCES `riders` (`id_rider`),
  ADD CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`id_horario`) REFERENCES `horario_clases` (`id_horario`);

--
-- Filtros para la tabla `horario_clases`
--
ALTER TABLE `horario_clases`
  ADD CONSTRAINT `horario_clases_ibfk_1` FOREIGN KEY (`id_coach`) REFERENCES `coaches` (`id_coach`);

--
-- Filtros para la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD CONSTRAINT `ingresos_ibfk_1` FOREIGN KEY (`id_rider`) REFERENCES `riders` (`id_rider`),
  ADD CONSTRAINT `ingresos_ibfk_2` FOREIGN KEY (`id_venta_cafeteria`) REFERENCES `cafeteria` (`id_venta_cafeteria`);

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`id_bicicleta`) REFERENCES `bicicletas` (`id_bicicleta`);

--
-- Filtros para la tabla `membresias`
--
ALTER TABLE `membresias`
  ADD CONSTRAINT `membresias_ibfk_1` FOREIGN KEY (`id_rider`) REFERENCES `riders` (`id_rider`),
  ADD CONSTRAINT `membresias_ibfk_2` FOREIGN KEY (`id_tipo_membresia`) REFERENCES `tipos_membresia` (`id_tipo_membresia`);

--
-- Filtros para la tabla `perfiles_coaches`
--
ALTER TABLE `perfiles_coaches`
  ADD CONSTRAINT `perfiles_coaches_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id_coach`);

--
-- Filtros para la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD CONSTRAINT `fk_reservaciones_bicicletas` FOREIGN KEY (`id_bicicleta`) REFERENCES `bicicletas` (`id_bicicleta`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `reservaciones_ibfk_1` FOREIGN KEY (`id_rider`) REFERENCES `riders` (`id_rider`),
  ADD CONSTRAINT `reservaciones_ibfk_2` FOREIGN KEY (`id_horario`) REFERENCES `horario_clases` (`id_horario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rider`) REFERENCES `riders` (`id_rider`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_coach`) REFERENCES `coaches` (`id_coach`),
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
