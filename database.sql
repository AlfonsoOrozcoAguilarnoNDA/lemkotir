CREATE TABLE `cat_users` (
`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
`Rol` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'N/A',
`username` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL,
`email` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL,
`password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Legacy/Base password',
`password_2026` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Updated secure hash 2026',
`first_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
`last_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
`gender` char(1) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
`headline` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
`bio` mediumtext COLLATE utf8mb4_unicode_520_ci,
`profile_image` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '_defaultUser.png',
`profile_blob` mediumblob,
`profile_mime_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`last_login_at` timestamp NULL DEFAULT NULL,
`plano_2026` varchar(200) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
`PAPELERA` varchar(2) COLLATE utf8mb4_unicode_520_ci DEFAULT 'NO',
`whatsapp` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `idx_email` (`email`),
UNIQUE KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `ch_derechos` ( `NUM_DERECHO` int(11) NOT NULL AUTO_INCREMENT,
`DESCRIPCION` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
PRIMARY KEY (`NUM_DERECHO`), UNIQUE KEY `idx_descripcion` (`DESCRIPCION`) )
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci; 

CREATE TABLE `ch_derechosusuarios` ( `IDENTIDAD` int(11) NOT NULL AUTO_INCREMENT, 
    `NUM_DERECHO` int(11) DEFAULT NULL, `idUsuario` int(11) DEFAULT NULL, 
    PRIMARY KEY (`IDENTIDAD`), KEY `idx_derecho` (`NUM_DERECHO`), 
    KEY `idx_usuario` (`idUsuario`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `dominios2020` (
  `dominio` varchar(65) NOT NULL,
  `servidores` varchar(250) NOT NULL,
  `registered` date DEFAULT NULL,
  `expiration` date DEFAULT NULL,
  `registrar` varchar(50) DEFAULT NULL,
  `showit` varchar(3) DEFAULT 'YES',
  `iscustomer` varchar(3) NOT NULL DEFAULT 'NO',
  `type` varchar(25) NOT NULL,
  `NOTA` varchar(2000) DEFAULT NULL,
  `last_updated` date DEFAULT NULL,
  PRIMARY KEY (`dominio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `LINK_CATEGORIES` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_icon` varchar(50) NOT NULL DEFAULT 'fa-link',
  `category_color` varchar(20) NOT NULL DEFAULT 'metro-blue',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `LINKS` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `link_title` varchar(200) NOT NULL,
  `link_url` text NOT NULL,
  `link_comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`link_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `LINKS_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `LINK_CATEGORIES` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_backups ( id INT AUTO_INCREMENT PRIMARY KEY, 
    proyecto VARCHAR(100), 
    ia_utilizada VARCHAR(50), 
    tipo VARCHAR(20), contenido LONGTEXT,
    nombre_archivo VARCHAR(150), 
    num_version DECIMAL(14,6), 
    comentarios LONGTEXT, 
    calificacion DECIMAL(14,6),
    visible VARCHAR(2), 
    fecha DATETIME, contrasena_ver VARCHAR(255), 
    tamanio DECIMAL(14,6), hash_md5 VARCHAR(32), 
    hash_sha1 VARCHAR(40), 
    INDEX idx_proyecto (proyecto), 
    INDEX idx_tipo (tipo), 
    INDEX idx_visible (visible),
    INDEX idx_fecha (fecha) )
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SQL PARA CREAR USUARIO newadmin CON PASSWORD HASHEADO
-- ============================================================

-- Generar hash para 'lemkotir*' (usando password_hash de PHP)
-- Hash bcrypt: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO `cat_users` (
    `Rol`,
    `username`,
    `email`,
    `password`,
    `password_2026`,
    `first_name`,
    `last_name`,
    `gender`,
    `headline`,
    `bio`,
    `profile_image`,
    `PAPELERA`,
    `whatsapp`
) VALUES (
    'Administrador',
    'newadmin',
    'newadmin@dominio.com',
    'lemkotir*',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'Sistema',
    'M',
    'Administrador del Sistema',
    'Usuario administrador del sistema',
    '_defaultUser.png',
    'NO',
    NULL
);

-- ============================================================
-- NOTA: El hash $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi 
-- corresponde a la contraseña: lemkotir*
-- ============================================================
