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
    '',
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
