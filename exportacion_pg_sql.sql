-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 10-11-2025 a las 22:32:49
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14


--
-- Estructura de tabla para la tabla email_verifications
--

DROP TABLE IF EXISTS email_verifications;
CREATE TABLE IF NOT EXISTS email_verifications (
  id SERIAL NOT NULL,
  user_id int NOT NULL,
  token varchar(100) NOT NULL,
  expires_at timestamp NOT NULL,
  PRIMARY KEY (id)
);

--
-- Volcado de datos para la tabla email_verifications
--

INSERT INTO email_verifications (id, user_id, token, expires_at) VALUES
(11, 21, '9d9cc9ee704a3a6b5a524fb079325d7b536a9af598f41698a0254dbe4049fe29', '2025-10-21 08:18:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla password_resets
--

DROP TABLE IF EXISTS password_resets;
CREATE TABLE IF NOT EXISTS password_resets (
  id SERIAL NOT NULL,
  user_id int NOT NULL,
  token varchar(100) NOT NULL,
  expires_at timestamp NOT NULL,
  PRIMARY KEY (id)
);

--
-- Volcado de datos para la tabla password_resets
--

INSERT INTO password_resets (id, user_id, token, expires_at) VALUES
(16, 3, 'c4709341bd5b80a483baa80a6fbcab767d27aa1b161acb6a5ec76ffd2f63743a', '2025-08-14 06:57:21'),
(18, 18, '126f644ef5a115b5e6eee3db8fd9cd9a4e616182722243b935866d49fde35fe8', '2025-08-28 07:45:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla productos
--

DROP TABLE IF EXISTS productos;
CREATE TABLE IF NOT EXISTS productos (
  id SERIAL NOT NULL,
  nombre varchar(255) NOT NULL,
  precio decimal(10,2) NOT NULL,
  descripcion text,
  PRIMARY KEY (id)
);

--
-- Volcado de datos para la tabla productos
--

INSERT INTO productos (id, nombre, precio, descripcion) VALUES
(1, 'Laptop Dell XPS', 350.00, 'Potente laptop para trabajo y estudio.'),
(2, 'Teclado Mecánico HyperX', 350.00, 'Teclado con switches rojos, ideal para gaming.'),
(3, 'Monitor Samsung Curvo', 350.00, 'Monitor de 27 pulgadas, resolución Full HD.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla usuarios
--

DROP TABLE IF EXISTS usuarios;
CREATE TABLE IF NOT EXISTS usuarios (
  id SERIAL NOT NULL,
  username varchar(50) NOT NULL,
  password varchar(255) NOT NULL,
  email varchar(100) DEFAULT NULL,
  nombre varchar(100) DEFAULT NULL,
  fecha_registro timestamp DEFAULT CURRENT_TIMESTAMP,
  estado boolean DEFAULT TRUE,
  correo_verificado boolean DEFAULT FALSE,
  role_name varchar(50) DEFAULT 'user',
  PRIMARY KEY (id)
);

--
-- Volcado de datos para la tabla usuarios
--

INSERT INTO usuarios (id, username, password, email, nombre, fecha_registro, estado, correo_verificado, role_name) VALUES
(1, 'hlovs65', '$2y$10$CQDj7xByO8vFMoW//xGWTejSKBxgY6b59gJnXe1EVKeUBZACDW8uW', 'salvadorlopezvillafan@gmail.com', 'salvador lopez villafan', '2025-10-22 00:15:17', FALSE, TRUE, 'user');
