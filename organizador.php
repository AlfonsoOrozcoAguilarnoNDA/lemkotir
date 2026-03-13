<?php
/*
    This file is part of the project "lemkotir".

    Copyright (C) 2026 Alfonso Orozco Aguilar

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; version 2.1 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program; if not, write to the Free Software Foundation,
    Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
/**
 * ============================================================================
 * PROYECTO: n/a
 * ============================================================================
 * AUTOR:         Alfonso Orozco Aguilar (vibecodingmexico.com)
 * PERFIL:        DevOps / Programador desde 1991 / Contaduría
 * FECHA:         13 de Marzo, 2026
 * APARIENCIA:    Rediseño visual por Claude, estándar MiniMax (13 marzo 2026)
 * REQUISITOS:    PHP 5.3 - 8.4+ 
 * LICENCIA:      LGPL 2.1 Antes MIT (kimi mismo autor)(Libre uso, mantener crédito del autor)
 * 
 * OBJETIVO: Funciones de salvar ideas de LLM, sobre base KIMI
 *           Apariencia visual: estándar MiniMax favoritos (Bootstrap 4.6, Metro)
 *
 * NOTA TÉCNICA:
 * Lógica: kimi2.php (corregida por Alfonso Orozco Aguilar)
 * Visual: rediseño siguiendo el estándar del favoritos MiniMax-M2.5
 * https://vibecodingmexico.com/organizador-de-ias/
 *
 * CREATE TABLE SQL:
 * 
 * CREATE TABLE IF NOT EXISTS ai_backups (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     proyecto VARCHAR(100),
 *     ia_utilizada VARCHAR(50),
 *     tipo VARCHAR(20),
 *     contenido LONGTEXT,
 *     nombre_archivo VARCHAR(150),
 *     num_version DECIMAL(14,6),
 *     comentarios LONGTEXT,
 *     calificacion DECIMAL(14,6),
 *     visible VARCHAR(2),
 *     fecha DATETIME,
 *     contrasena_ver VARCHAR(255),
 *     tamanio DECIMAL(14,6),
 *     hash_md5 VARCHAR(32),
 *     hash_sha1 VARCHAR(40),
 *     INDEX idx_proyecto (proyecto),
 *     INDEX idx_tipo (tipo),
 *     INDEX idx_visible (visible),
 *     INDEX idx_fecha (fecha)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */

// --- CONFIGURACIÓN — edita esto antes de usar ---
define('PASS_MAESTRA', 'tupass123'); // para agregar, editar y borrar
define('PASS_REGISTROS', 'tupass123');
define('IPS_PERMITIDAS', ['127.0.0.1', '192.168.1.1','201.103.232.198','::1']); // agrega tus IPs aquí

// Configuración de base de datos
include_once "config.php";
//$host     = 'localhost';
//$dbname   = 'dffdfdgtir'; // Nombre de tu base de datos
//$username = $dbname;
//$password = 'dfgdgd';

define('DB_HOST', 'localhost');
define('DB_USER', $dbname);
define('DB_PASS', $username);
define('DB_NAME', $dbname);

// Iniciar sesión
session_start();

// Headers anti-caché
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// Verificación de IP
$ip_visitante = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!in_array($ip_visitante, IPS_PERMITIDAS)) {
    die('<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso Denegado</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>body{background:linear-gradient(135deg,#1a2a6c 0%,#2c3e50 50%,#4a69bd 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;}</style>
    </head><body><div class="text-center text-white"><i class="fas fa-lock" style="font-size:4rem;margin-bottom:1rem;"></i>
    <h2>Acceso no autorizado</h2>
    <p>IP: ' . htmlspecialchars($ip_visitante) . '</p></div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    </body></html>');
}

// Conexión a base de datos
/*
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
*/
$mysqli=$link;

// Crear tabla si no existe
$create_table_sql = "CREATE TABLE IF NOT EXISTS ai_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto VARCHAR(100),
    ia_utilizada VARCHAR(50),
    tipo VARCHAR(20),
    contenido LONGTEXT,
    nombre_archivo VARCHAR(150),
    num_version DECIMAL(14,6),
    comentarios LONGTEXT,
    calificacion DECIMAL(14,6),
    visible VARCHAR(2),
    fecha DATETIME,
    contrasena_ver VARCHAR(255) NULL,
    tamanio DECIMAL(14,6),
    hash_md5 VARCHAR(32),
    hash_sha1 VARCHAR(40),
    INDEX idx_proyecto (proyecto),
    INDEX idx_tipo (tipo),
    INDEX idx_visible (visible),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$mysqli->query($create_table_sql);

// Verificar si hay datos POST pero $_POST está vacío (problema de tamaño)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
    $error_tamano = true;
}

// Funciones auxiliares
function estaAutenticado() {
    return isset($_SESSION['auth']) && $_SESSION['auth'] === true;
}

function requerirAuth() {
    if (!estaAutenticado()) {
        header('Location: ?action=login');
        exit;
    }
}

function calcularHashes($contenido) {
    return [
        'md5'     => md5($contenido),
        'sha1'    => sha1($contenido),
        'tamanio' => round(strlen($contenido) / 1024, 6)
    ];
}

function validarBase64Imagen($base64) {
    return preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/i', $base64);
}

function limpiarOutput($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ============================================================
// PROCESAMIENTO DE ACCIONES
// ============================================================
$action  = $_GET['action'] ?? 'list';
$mensaje = '';
$error   = '';

// Login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pass_maestra']) && $_POST['pass_maestra'] === PASS_MAESTRA) {
        $_SESSION['auth'] = true;
        header('Location: ?');
        exit;
    } else {
        $error = "Contraseña incorrecta";
    }
}

// Logout
if ($action === 'logout') {
    session_destroy();
    header('Location: ?');
    exit;
}

// Borrar registro
if ($action === 'delete' && isset($_GET['id'])) {
    requerirAuth();
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT nombre_archivo, num_version FROM ai_backups WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result   = $stmt->get_result();
    $registro = $result->fetch_assoc();
    if (!$registro) { header('Location: ?'); exit; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['confirmacion'] ?? '') === 'BORRAR') {
            $stmt = $mysqli->prepare("DELETE FROM ai_backups WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            header('Location: ?mensaje=Registro eliminado correctamente');
            exit;
        } else {
            $error = "Debes escribir BORRAR en mayúsculas para confirmar";
        }
    }
}

// Guardar registro (add/edit)
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requerirAuth();

    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $post_max = ini_get('post_max_size');
        die("<div class='container mt-5'><div class='alert alert-danger'><h4>⚠️ Error: Datos demasiado grandes</h4>
            <p>post_max_size actual: <strong>{$post_max}</strong></p>
            <p>Tamaño enviado: " . round($_SERVER['CONTENT_LENGTH'] / 1024 / 1024, 2) . " MB</p></div>
            <a href='javascript:history.back()' class='btn btn-secondary'>Volver</a></div>");
    }

    $id           = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $proyecto     = $_POST['proyecto']     ?? '';
    $ia_utilizada = $_POST['ia_utilizada'] ?? '';
    $tipo         = $_POST['tipo']         ?? 'prompt';
    $contenido    = $_POST['contenido']    ?? '';

    // Imagen subida como archivo
    if ($tipo === 'imagen' && isset($_FILES['archivo_imagen']) && $_FILES['archivo_imagen']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['archivo_imagen']['tmp_name'];
        $file_type  = $_FILES['archivo_imagen']['type'];
        $allowed    = ['image/jpeg','image/png','image/webp','image/gif'];
        if (in_array($file_type, $allowed)) {
            $contenido = 'data:' . $file_type . ';base64,' . base64_encode(file_get_contents($file_tmp));
        }
    }

    if ($tipo === 'imagen' && !validarBase64Imagen($contenido) && !empty($contenido)) {
        $error = "El contenido no parece ser una imagen base64 válida (debe comenzar con data:image/[jpg|png|webp|gif];base64,)";
    } else {
        $nombre_archivo = $_POST['nombre_archivo'] ?? '';
        $num_version    = floatval($_POST['num_version']  ?? 1);
        $comentarios    = $_POST['comentarios']    ?? '';
        $calificacion   = floatval($_POST['calificacion'] ?? 0);
        $visible        = $_POST['visible']        ?? 'SI';
        $contrasena_ver = $_POST['contrasena_ver'] ?? '';
        $hashes         = calcularHashes($contenido);

        if (!empty($contrasena_ver)) {
            $contrasena_ver = password_hash($contrasena_ver, PASSWORD_DEFAULT);
        } else {
            $contrasena_ver = '';
        }

        if ($id > 0) {
            $stmt = $mysqli->prepare("UPDATE ai_backups SET 
                proyecto = ?, ia_utilizada = ?, tipo = ?, contenido = ?, 
                nombre_archivo = ?, num_version = ?, comentarios = ?, 
                calificacion = ?, visible = ?, tamanio = ?, hash_md5 = ?, hash_sha1 = ?,
                contrasena_ver = IF(? = '', contrasena_ver, ?)
                WHERE id = ?");
            $stmt->bind_param("sssssdsdsdssssi",
                $proyecto, $ia_utilizada, $tipo, $contenido,
                $nombre_archivo, $num_version, $comentarios,
                $calificacion, $visible, $hashes['tamanio'], $hashes['md5'], $hashes['sha1'],
                $contrasena_ver, $contrasena_ver, $id
            );
        } else {
            $stmt = $mysqli->prepare("INSERT INTO ai_backups 
                (proyecto, ia_utilizada, tipo, contenido, nombre_archivo, num_version, 
                comentarios, calificacion, visible, fecha, contrasena_ver, tamanio, hash_md5, hash_sha1) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            $stmt->bind_param("sssssdsdssdss",
                $proyecto, $ia_utilizada, $tipo, $contenido, $nombre_archivo, $num_version,
                $comentarios, $calificacion, $visible, $contrasena_ver,
                $hashes['tamanio'], $hashes['md5'], $hashes['sha1']
            );
        }

        if ($stmt->execute()) {
            header('Location: ?mensaje=Registro guardado correctamente');
            exit;
        } else {
            $error = "Error al guardar: " . $stmt->error;
        }
    }
}

// Nueva versión
if ($action === 'newversion' && isset($_GET['id'])) {
    requerirAuth();
    $id   = intval($_GET['id']);
    $stmt = $mysqli->prepare("SELECT * FROM ai_backups WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result   = $stmt->get_result();
    $registro = $result->fetch_assoc();
    if ($registro) {
        $form_data = [
            'id'            => 0,
            'proyecto'      => $registro['proyecto'],
            'ia_utilizada'  => $registro['ia_utilizada'],
            'tipo'          => $registro['tipo'],
            'nombre_archivo'=> $registro['nombre_archivo'],
            'num_version'   => $registro['num_version'] + 1.000000,
            'comentarios'   => '',
            'calificacion'  => $registro['calificacion'],
            'visible'       => $registro['visible'],
            'contenido'     => '',
            'contrasena_ver'=> ''
        ];
        $action = 'edit';
    }
}

// Verificar contraseña de registro
if ($action === 'unlock' && isset($_POST['id']) && isset($_POST['pass_registro'])) {
    $id   = intval($_POST['id']);
    $pass = $_POST['pass_registro'];
    $stmt = $mysqli->prepare("SELECT contrasena_ver FROM ai_backups WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $reg = $stmt->get_result()->fetch_assoc();
    if ($reg && password_verify($pass, $reg['contrasena_ver'])) {
        $_SESSION['unlocked_' . $id] = true;
        header('Location: ?action=view&id=' . $id);
        exit;
    } else {
        $error  = "Contraseña incorrecta";
        $action = 'view';
        $_GET['id'] = $id;
    }
}

if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>AI Backup System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        :root {
            --metro-blue:   #3498db;
            --metro-green:  #27ae60;
            --metro-red:    #e74c3c;
            --metro-orange: #e67e22;
            --metro-purple: #9b59b6;
            --metro-teal:   #009688;
            --metro-dark:   #34495e;
            --metro-cyan:   #00bcd4;
            --nav-bg:       linear-gradient(135deg, #2c3e50, #34495e);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, sans-serif;
            background-color: #f0f2f5;
            padding-top: 70px;
            padding-bottom: 80px;
        }

        /* ---- NAVBAR ---- */
        .navbar {
            background: var(--nav-bg) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,.3);
        }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        .navbar-brand small { font-size:.75rem; opacity:.7; }

        /* ---- CARDS ---- */
        .card-metro {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,.12);
            overflow: hidden;
            border: none;
            margin-bottom: 1.5rem;
        }
        .card-header-metro {
            background: var(--nav-bg);
            color: #fff;
            padding: 14px 20px;
            border-radius: 0;
        }
        .card-header-metro h4,
        .card-header-metro h5 { margin-bottom: 0; }

        /* ---- BOTONES ---- */
        .btn-metro {
            background: var(--nav-bg);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            transition: all .25s ease;
        }
        .btn-metro:hover, .btn-metro:focus {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,.25);
            color: #fff;
        }
        .btn-metro-danger {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            transition: all .25s ease;
        }
        .btn-metro-danger:hover { transform: translateY(-2px); color: #fff; }

        /* ---- TABLA ---- */
        .table-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,.1);
            overflow: hidden;
        }
        .table thead th {
            background: var(--nav-bg);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .table tbody tr:hover { background: #f8f9fa; }
        .table td, .table th { vertical-align: middle; }

        /* ---- FILTROS ---- */
        .filter-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
            padding: 20px 24px;
            margin-bottom: 1.5rem;
        }
        .filter-card h5 { color: #2c3e50; margin-bottom: 1rem; }

        /* ---- DIFF ---- */
        .diff-old { background-color: #ffe6e6; text-decoration: line-through; color: #cc0000; }
        .diff-new { background-color: #e6ffe6; color: #006600; font-weight: bold; }

        /* ---- MISC ---- */
        .img-preview  { max-width:100%; max-height:500px; border:1px solid #ddd; border-radius:8px; padding:5px; }
        .hash-text    { font-family:monospace; font-size:.85rem; color:#666; word-break:break-all; }
        .textarea-code { font-family:monospace; min-height:300px; }
        .locked-icon  { color: var(--metro-red); }

        /* ---- BADGES ---- */
        .badge-ia     { background: var(--metro-blue);   color:#fff; }
        .badge-tipo   { background: var(--metro-teal);   color:#fff; }
        .badge-bueno  { background: var(--metro-green);  color:#fff; }
        .badge-medio  { background: var(--metro-orange); color:#fff; }
        .badge-malo   { background: var(--metro-red);    color:#fff; }

        /* ---- FOOTER FIJO ---- */
        footer.footer-fixed {
            background: var(--nav-bg);
            color: rgba(255,255,255,.85);
            position: fixed;
            bottom: 0;
            width: 100%;
            z-index: 1030;
            padding: 8px 0;
            font-size: .8rem;
            text-align: center;
        }
        footer.footer-fixed strong { color: #fff; }

        /* ---- LOGIN / ACCESO ---- */
        .login-wrap {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

<!-- ============================================================ -->
<!-- NAVBAR                                                        -->
<!-- ============================================================ -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="?">
            <i class="fas fa-database mr-2"></i>AI Backup System
            <small class="ml-2">| vibecodingmexico.com</small>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="?"><i class="fas fa-list mr-1"></i>Ver Registros</a>
                </li>
                <?php if (estaAutenticado()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="?action=add"><i class="fas fa-plus-circle mr-1"></i>Agregar Nuevo</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ml-auto">
                <?php if (estaAutenticado()): ?>
                    <li class="nav-item">
                        <span class="navbar-text text-success mr-3">
                            <i class="fas fa-unlock mr-1"></i>Modo Edición
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="?action=logout" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-sign-out-alt mr-1"></i>Cerrar Sesión
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <span class="navbar-text text-warning mr-3">
                            <i class="fas fa-lock mr-1"></i>Solo Lectura
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="?action=login" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-key mr-1"></i>Autenticar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ============================================================ -->
<!-- CONTENIDO PRINCIPAL                                           -->
<!-- ============================================================ -->
<div class="container">

    <?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <i class="fas fa-check-circle mr-2"></i><?php echo limpiarOutput($mensaje); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo limpiarOutput($error); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>

    <?php if (isset($error_tamano)): ?>
    <div class="alert alert-danger mt-3">
        <h5><i class="fas fa-exclamation-triangle mr-2"></i>Error de Tamaño de POST</h5>
        <p><strong>post_max_size:</strong> <?php echo ini_get('post_max_size'); ?></p>
        <p><strong>upload_max_filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
        <p class="mb-0">Reduce el tamaño de la imagen o comprime antes de convertir a base64.</p>
    </div>
    <?php endif; ?>

    <?php
    // ==============================================================
    // LOGIN
    // ==============================================================
    if ($action === 'login'):
    ?>
    <div class="login-wrap">
        <div style="width:100%;max-width:420px;">
            <div class="card-metro">
                <div class="card-header-metro">
                    <h4><i class="fas fa-lock mr-2"></i>Acceso Maestro</h4>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <div class="form-group">
                            <label>Contraseña Maestra</label>
                            <input type="password" name="pass_maestra" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-metro btn-block">
                            <i class="fas fa-sign-in-alt mr-2"></i>Acceder
                        </button>
                        <a href="?" class="btn btn-outline-secondary btn-block mt-2">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    // ==============================================================
    // FORMULARIO AGREGAR / EDITAR
    // ==============================================================
    elseif ($action === 'edit' || ($action === 'add' && estaAutenticado())):
        requerirAuth();

        $edit_mode = false;
        $data = [
            'id' => 0, 'proyecto' => '', 'ia_utilizada' => 'ChatGPT',
            'tipo' => 'prompt', 'contenido' => '', 'nombre_archivo' => '',
            'num_version' => 1.000000, 'comentarios' => '',
            'calificacion' => 0, 'visible' => 'SI', 'contrasena_ver' => ''
        ];

        if (isset($_GET['id'])) {
            $id   = intval($_GET['id']);
            $stmt = $mysqli->prepare("SELECT * FROM ai_backups WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $data      = $row;
                $edit_mode = true;
            }
        } elseif (isset($form_data)) {
            $data = $form_data;
        }
    ?>
    <div class="mt-4">
        <div class="card-metro">
            <div class="card-header-metro d-flex justify-content-between align-items-center">
                <h4>
                    <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus-circle'; ?> mr-2"></i>
                    <?php echo $edit_mode ? 'Editar Registro' : 'Nuevo Registro'; ?>
                </h4>
                <a href="?" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>
            <div class="card-body p-4">
                <form method="post" action="?action=save" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-folder mr-1"></i> Proyecto</label>
                                <input type="text" name="proyecto" class="form-control"
                                    value="<?php echo limpiarOutput($data['proyecto']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-robot mr-1"></i> IA Utilizada</label>
                                <select name="ia_utilizada" class="form-control">
                                    <?php foreach (['ChatGPT','Claude','Gemini','Grok','Cohere','Kimi','MiniMax','Llama','Otro'] as $ia): ?>
                                    <option value="<?php echo $ia; ?>" <?php echo $data['ia_utilizada']==$ia?'selected':''; ?>>
                                        <?php echo $ia; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-tag mr-1"></i> Tipo</label>
                                <select name="tipo" class="form-control" id="tipoSelect">
                                    <?php foreach (['prompt'=>'Prompt','imagen'=>'Imagen','idea'=>'Idea','respuesta'=>'Respuesta','codigo'=>'Código','otro'=>'Otro'] as $v=>$l): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $data['tipo']==$v?'selected':''; ?>><?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label><i class="fas fa-file-code mr-1"></i> Nombre / Identificador</label>
                                <input type="text" name="nombre_archivo" class="form-control"
                                    value="<?php echo limpiarOutput($data['nombre_archivo']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-code-branch mr-1"></i> Versión</label>
                                <input type="number" name="num_version" class="form-control" step="0.000001"
                                    value="<?php echo $data['num_version']; ?>" required>
                                <small class="text-muted">Ej: 1.000000</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left mr-1"></i> Contenido</label>
                        <textarea name="contenido" id="contenidoTextarea" class="form-control textarea-code" rows="10"><?php
                            echo ($data['tipo'] === 'imagen') ? '' : limpiarOutput($data['contenido']);
                        ?></textarea>
                        <div id="imagenUpload" style="display:<?php echo $data['tipo']==='imagen'?'block':'none'; ?>;">
                            <small class="text-muted mt-2 d-block">Para imágenes: pega base64 arriba o sube un archivo:</small>
                            <input type="file" name="archivo_imagen" class="form-control-file mt-2"
                                accept="image/jpeg,image/png,image/webp,image/gif">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-comment mr-1"></i> Comentarios</label>
                                <textarea name="comentarios" class="form-control" rows="3"><?php
                                    echo limpiarOutput($data['comentarios']);
                                ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-star mr-1"></i> Calificación (0-10)</label>
                                <input type="number" name="calificacion" class="form-control"
                                    step="0.1" min="0" max="10" value="<?php echo $data['calificacion']; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-eye mr-1"></i> Visible</label>
                                <select name="visible" class="form-control">
                                    <option value="SI" <?php echo $data['visible']=='SI'?'selected':''; ?>>SI</option>
                                    <option value="NO" <?php echo $data['visible']=='NO'?'selected':''; ?>>NO</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-key mr-1"></i> Contraseña de protección (opcional)</label>
                        <input type="password" name="contrasena_ver" class="form-control"
                            placeholder="Dejar vacío para mantener actual o sin protección">
                        <small class="text-muted">Si se establece, se requerirá para ver el contenido.</small>
                    </div>

                    <button type="submit" class="btn btn-metro btn-lg">
                        <i class="fas fa-save mr-2"></i>Guardar Registro
                    </button>
                    <a href="?" class="btn btn-outline-secondary btn-lg ml-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('tipoSelect').addEventListener('change', function() {
            document.getElementById('imagenUpload').style.display = this.value === 'imagen' ? 'block' : 'none';
        });
    </script>

    <?php
    // ==============================================================
    // VER REGISTRO
    // ==============================================================
    elseif ($action === 'view' && isset($_GET['id'])):
        $id   = intval($_GET['id']);
        $stmt = $mysqli->prepare("SELECT contrasena_ver FROM ai_backups WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $check    = $stmt->get_result()->fetch_assoc();
        $bloqueado = $check && !empty($check['contrasena_ver']) && !isset($_SESSION['unlocked_' . $id]);

        if ($bloqueado):
    ?>
    <div class="login-wrap">
        <div style="width:100%;max-width:420px;">
            <div class="card-metro">
                <div class="card-header-metro" style="background:linear-gradient(135deg,#e67e22,#d35400);">
                    <h4><i class="fas fa-lock mr-2"></i>Contenido Protegido</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted">Este registro requiere contraseña para visualizarse.</p>
                    <form method="post" action="?action=unlock">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <div class="form-group">
                            <input type="password" name="pass_registro" class="form-control"
                                placeholder="Contraseña del registro" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-metro btn-block">
                            <i class="fas fa-unlock mr-2"></i>Desbloquear
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
        else:
            $stmt = $mysqli->prepare("SELECT * FROM ai_backups WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $reg = $stmt->get_result()->fetch_assoc();

            if ($reg):
                $stmt_ver = $mysqli->prepare("SELECT id, num_version, fecha FROM ai_backups 
                    WHERE proyecto = ? AND nombre_archivo = ? AND id != ? ORDER BY num_version DESC");
                $stmt_ver->bind_param("ssi", $reg['proyecto'], $reg['nombre_archivo'], $id);
                $stmt_ver->execute();
                $otras_versiones = $stmt_ver->get_result();

                // Color de calificación
                $cal = floatval($reg['calificacion']);
                $cal_class = $cal >= 8 ? 'badge-bueno' : ($cal >= 5 ? 'badge-medio' : 'badge-malo');
    ?>
    <div class="mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb" style="background:transparent;padding:0;">
                <li class="breadcrumb-item"><a href="?">Inicio</a></li>
                <li class="breadcrumb-item active">Registro #<?php echo $id; ?></li>
            </ol>
        </nav>

        <div class="card-metro">
            <div class="card-header-metro d-flex justify-content-between align-items-center">
                <h4>
                    <i class="fas fa-eye mr-2"></i>
                    <?php echo limpiarOutput($reg['nombre_archivo']); ?>
                    <span class="badge badge-light ml-2" style="font-size:.8rem;">
                        v<?php echo number_format($reg['num_version'], 6); ?>
                    </span>
                </h4>
                <div>
                    <?php if (estaAutenticado()): ?>
                    <a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="?action=newversion&id=<?php echo $id; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-code-branch"></i>
                    </a>
                    <a href="?action=delete&id=<?php echo $id; ?>" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4">

                <!-- Metadatos -->
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-2">
                        <small class="text-muted d-block">Proyecto</small>
                        <strong><?php echo limpiarOutput($reg['proyecto']); ?></strong>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <small class="text-muted d-block">IA</small>
                        <span class="badge badge-ia"><?php echo limpiarOutput($reg['ia_utilizada']); ?></span>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <small class="text-muted d-block">Tipo</small>
                        <span class="badge badge-tipo"><?php echo limpiarOutput($reg['tipo']); ?></span>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <small class="text-muted d-block">Fecha</small>
                        <strong><?php echo $reg['fecha']; ?></strong>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-6 col-md-3 mb-2">
                        <small class="text-muted d-block">Calificación</small>
                        <span class="badge <?php echo $cal_class; ?>"><?php echo $cal; ?>/10</span>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <small class="text-muted d-block">Visible</small>
                        <strong><?php echo $reg['visible']; ?></strong>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <small class="text-muted d-block">Tamaño</small>
                        <strong><?php echo number_format($reg['tamanio'], 2); ?> KB</strong>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <?php if (!empty($reg['contrasena_ver'])): ?>
                        <small class="text-muted d-block">Estado</small>
                        <span class="text-danger"><i class="fas fa-lock mr-1"></i>Protegido</span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>

                <!-- Contenido -->
                <h5 class="mb-3"><i class="fas fa-align-left mr-2"></i>Contenido</h5>
                <?php if ($reg['tipo'] === 'imagen'): ?>
                    <?php if (validarBase64Imagen($reg['contenido'])): ?>
                        <img src="<?php echo limpiarOutput($reg['contenido']); ?>" class="img-preview" alt="Imagen">
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            La imagen no tiene formato base64 válido (jpg, png, webp, gif).
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <textarea class="form-control textarea-code" rows="15" readonly><?php
                        echo limpiarOutput($reg['contenido']);
                    ?></textarea>
                <?php endif; ?>

                <?php if (!empty($reg['comentarios'])): ?>
                <div class="mt-3">
                    <h6><i class="fas fa-comment mr-1"></i> Comentarios</h6>
                    <div class="alert alert-secondary">
                        <?php echo nl2br(limpiarOutput($reg['comentarios'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <hr>

                <!-- Hashes -->
                <h6><i class="fas fa-fingerprint mr-1"></i> Hashes de Integridad</h6>
                <div class="hash-text"><strong>MD5:</strong>  <?php echo $reg['hash_md5']; ?></div>
                <div class="hash-text"><strong>SHA1:</strong> <?php echo $reg['hash_sha1']; ?></div>

                <!-- Otras versiones -->
                <?php if ($otras_versiones->num_rows > 0): ?>
                <hr>
                <h5><i class="fas fa-history mr-2"></i>Otras Versiones</h5>
                <div class="list-group mb-3">
                    <?php while ($ver = $otras_versiones->fetch_assoc()): ?>
                    <a href="?action=view&id=<?php echo $ver['id']; ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Versión <?php echo number_format($ver['num_version'], 6); ?>
                        <span class="text-muted small"><?php echo $ver['fecha']; ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>

                <?php if (isset($_GET['compare']) && estaAutenticado()):
                    $cid       = intval($_GET['compare']);
                    $stmt_c    = $mysqli->prepare("SELECT contenido, num_version FROM ai_backups WHERE id = ?");
                    $stmt_c->bind_param("i", $cid);
                    $stmt_c->execute();
                    $comp = $stmt_c->get_result()->fetch_assoc();
                    if ($comp):
                        $lines1 = explode("\n", $reg['contenido']);
                        $lines2 = explode("\n", $comp['contenido']);
                ?>
                <h6>Comparación con v<?php echo number_format($comp['num_version'], 6); ?></h6>
                <div class="border p-3 bg-light" style="font-family:monospace;font-size:.88rem;max-height:400px;overflow-y:auto;border-radius:8px;">
                    <?php
                    $max = max(count($lines1), count($lines2));
                    for ($i = 0; $i < $max; $i++) {
                        $l1 = $lines1[$i] ?? '';
                        $l2 = $lines2[$i] ?? '';
                        if ($l1 !== $l2) {
                            if ($l1 !== '') echo "<div class='diff-old'>- " . limpiarOutput($l1) . "</div>";
                            if ($l2 !== '') echo "<div class='diff-new'>+ " . limpiarOutput($l2) . "</div>";
                        } else {
                            echo "<div>&nbsp;&nbsp;" . limpiarOutput($l1) . "</div>";
                        }
                    }
                    ?>
                </div>
                <?php endif; else: if (estaAutenticado()): ?>
                <form method="get" class="form-inline">
                    <input type="hidden" name="action" value="view">
                    <input type="hidden" name="id"     value="<?php echo $id; ?>">
                    <label class="mr-2">Comparar con:</label>
                    <select name="compare" class="form-control form-control-sm mr-2">
                        <?php $otras_versiones->data_seek(0); while ($ver = $otras_versiones->fetch_assoc()): ?>
                        <option value="<?php echo $ver['id']; ?>">v<?php echo number_format($ver['num_version'],6); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-metro">
                        <i class="fas fa-exchange-alt mr-1"></i>Comparar
                    </button>
                </form>
                <?php endif; endif; ?>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="?" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
            else: echo '<div class="alert alert-danger mt-4">Registro no encontrado.</div>';
            endif;
        endif;

    // ==============================================================
    // BORRAR REGISTRO
    // ==============================================================
    elseif ($action === 'delete' && isset($_GET['id'])):
        requerirAuth();
    ?>
    <div class="login-wrap">
        <div style="width:100%;max-width:560px;">
            <div class="card-metro">
                <div class="card-header-metro" style="background:linear-gradient(135deg,#c0392b,#e74c3c);">
                    <h4><i class="fas fa-exclamation-triangle mr-2"></i>Confirmar Eliminación</h4>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning">
                        <strong>Archivo:</strong> <?php echo limpiarOutput($registro['nombre_archivo']); ?><br>
                        <strong>Versión:</strong> <?php echo number_format($registro['num_version'], 6); ?>
                    </div>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Esta acción NO se puede deshacer.</strong>
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label class="font-weight-bold text-danger" style="font-size:1.1rem;">
                                Escribe <span style="letter-spacing:3px;background:#e74c3c;color:#fff;padding:2px 8px;border-radius:4px;">BORRAR</span> en mayúsculas para confirmar:
                            </label>
                            <input type="text" name="confirmacion" class="form-control form-control-lg"
                                required autofocus autocomplete="off"
                                style="text-transform:uppercase;font-weight:bold;font-size:1.2rem;">
                        </div>
                        <button type="submit" class="btn btn-metro-danger btn-lg btn-block">
                            <i class="fas fa-trash-alt mr-2"></i>Eliminar Permanentemente
                        </button>
                    </form>
                    <a href="?" class="btn btn-outline-secondary btn-block mt-2">Cancelar</a>
                </div>
            </div>
        </div>
    </div>

    <?php
    // ==============================================================
    // LISTADO PRINCIPAL
    // ==============================================================
    else:
        $filtro_proyecto   = $_GET['filtro_proyecto']   ?? '';
        $filtro_ia         = $_GET['filtro_ia']         ?? '';
        $filtro_tipo       = $_GET['filtro_tipo']       ?? '';
        $filtro_visible    = $_GET['filtro_visible']    ?? 'SI';
        $filtro_fecha_desde= $_GET['filtro_fecha_desde']?? '';
        $filtro_fecha_hasta= $_GET['filtro_fecha_hasta']?? '';
        $busqueda          = $_GET['busqueda']          ?? '';

        $pagina    = max(1, intval($_GET['pagina'] ?? 1));
        $por_pagina = 10;
        $offset    = ($pagina - 1) * $por_pagina;

        $where  = "WHERE 1=1";
        $params = [];
        $types  = "";

        if ($filtro_visible !== 'todos') { $where .= " AND visible = ?";          $params[] = $filtro_visible;           $types .= "s"; }
        if ($filtro_proyecto)            { $where .= " AND proyecto LIKE ?";       $params[] = "%$filtro_proyecto%";      $types .= "s"; }
        if ($filtro_ia)                  { $where .= " AND ia_utilizada = ?";      $params[] = $filtro_ia;                $types .= "s"; }
        if ($filtro_tipo)                { $where .= " AND tipo = ?";              $params[] = $filtro_tipo;              $types .= "s"; }
        if ($filtro_fecha_desde)         { $where .= " AND fecha >= ?";            $params[] = $filtro_fecha_desde." 00:00:00"; $types .= "s"; }
        if ($filtro_fecha_hasta)         { $where .= " AND fecha <= ?";            $params[] = $filtro_fecha_hasta." 23:59:59"; $types .= "s"; }
        if ($busqueda)                   { $where .= " AND (contenido LIKE ? OR comentarios LIKE ?)"; $params[] = "%$busqueda%"; $params[] = "%$busqueda%"; $types .= "ss"; }

        $stmt_c = $mysqli->prepare("SELECT COUNT(*) as total FROM ai_backups $where");
        if (!empty($types)) $stmt_c->bind_param($types, ...$params);
        $stmt_c->execute();
        $total_registros = $stmt_c->get_result()->fetch_assoc()['total'];
        $total_paginas   = ceil($total_registros / $por_pagina);

        $params_pag   = array_merge($params, [$por_pagina, $offset]);
        $types_pag    = $types . "ii";
        $stmt = $mysqli->prepare("SELECT * FROM ai_backups $where ORDER BY fecha DESC LIMIT ? OFFSET ?");
        $stmt->bind_param($types_pag, ...$params_pag);
        $stmt->execute();
        $registros = $stmt->get_result();
    ?>

    <!-- FILTROS -->
    <div class="filter-card mt-4">
        <h5><i class="fas fa-filter mr-2"></i>Filtros y Búsqueda</h5>
        <form method="get">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="text" name="filtro_proyecto" class="form-control"
                        placeholder="Proyecto" value="<?php echo limpiarOutput($filtro_proyecto); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="filtro_ia" class="form-control">
                        <option value="">Todas las IAs</option>
                        <?php foreach (['ChatGPT','Claude','Gemini','Grok','Cohere','Kimi','MiniMax','Llama'] as $ia): ?>
                        <option value="<?php echo $ia; ?>" <?php echo $filtro_ia==$ia?'selected':''; ?>><?php echo $ia; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="filtro_tipo" class="form-control">
                        <option value="">Todos los tipos</option>
                        <?php foreach (['prompt','imagen','idea','respuesta','codigo','otro'] as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $filtro_tipo==$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="filtro_visible" class="form-control">
                        <option value="SI"    <?php echo $filtro_visible=='SI'   ?'selected':''; ?>>Visibles</option>
                        <option value="NO"    <?php echo $filtro_visible=='NO'   ?'selected':''; ?>>Ocultos</option>
                        <option value="todos" <?php echo $filtro_visible=='todos'?'selected':''; ?>>Todos</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="text" name="busqueda" class="form-control"
                        placeholder="Buscar en contenido..." value="<?php echo limpiarOutput($busqueda); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="date" name="filtro_fecha_desde" class="form-control"
                        value="<?php echo $filtro_fecha_desde; ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="filtro_fecha_hasta" class="form-control"
                        value="<?php echo $filtro_fecha_hasta; ?>">
                </div>
                <div class="col-md-6 mb-2">
                    <button type="submit" class="btn btn-metro mr-2">
                        <i class="fas fa-search mr-1"></i>Filtrar
                    </button>
                    <a href="?" class="btn btn-outline-secondary mr-2">
                        <i class="fas fa-times mr-1"></i>Limpiar
                    </a>
                    <?php if (estaAutenticado()): ?>
                    <a href="?action=add" class="btn btn-success">
                        <i class="fas fa-plus mr-1"></i>Agregar Nuevo
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLA -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Proyecto</th>
                        <th>IA</th>
                        <th>Tipo</th>
                        <th>Versión</th>
                        <th>Calif.</th>
                        <th>KB</th>
                        <th>Archivo</th>
                        <th>Vis.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $registros->fetch_assoc()):
                        $cal    = floatval($row['calificacion']);
                        $ccls   = $cal >= 8 ? 'badge-bueno' : ($cal >= 5 ? 'badge-medio' : 'badge-malo');
                    ?>
                    <tr>
                        <td><small><?php echo date('Y-m-d H:i', strtotime($row['fecha'])); ?></small></td>
                        <td><?php echo limpiarOutput($row['proyecto']); ?></td>
                        <td><span class="badge badge-ia"><?php echo limpiarOutput($row['ia_utilizada']); ?></span></td>
                        <td><span class="badge badge-tipo"><?php echo limpiarOutput($row['tipo']); ?></span></td>
                        <td><small><?php echo number_format($row['num_version'], 6); ?></small></td>
                        <td><span class="badge <?php echo $ccls; ?>"><?php echo $cal; ?></span></td>
                        <td><small><?php echo number_format($row['tamanio'], 2); ?></small></td>
                        <td>
                            <?php echo limpiarOutput($row['nombre_archivo']); ?>
                            <?php if (!empty($row['contrasena_ver'])): ?>
                            <i class="fas fa-lock locked-icon ml-1" title="Protegido"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['visible']; ?></td>
                        <td>
                            <a href="?action=view&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-info mb-1" title="Ver"><i class="fas fa-eye"></i></a>
                            <?php if (estaAutenticado()): ?>
                            <a href="?action=edit&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-warning mb-1" title="Editar"><i class="fas fa-edit"></i></a>
                            <a href="?action=delete&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-danger mb-1" title="Borrar"><i class="fas fa-trash"></i></a>
                            <a href="?action=newversion&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-success mb-1" title="Nueva Versión"><i class="fas fa-code-branch"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($registros->num_rows === 0): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No se encontraron registros
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="p-3">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina-1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo $i==$pagina?'active':''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$i])); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina+1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <p class="text-center text-muted small mt-2">
                Mostrando <?php echo $registros->num_rows; ?> de <?php echo $total_registros; ?> registros
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div><!-- /container -->

<!-- ============================================================ -->
<!-- FOOTER FIJO                                                   -->
<!-- ============================================================ -->
<footer class="footer-fixed">
    <div class="container">
        <i class="fas fa-database mr-1"></i> AI Backup System &nbsp;|&nbsp;
        <i class="fas fa-code mr-1"></i> PHP <?php echo phpversion(); ?> &nbsp;|&nbsp;
        <i class="fas fa-network-wired mr-1"></i> IP: <?php echo $ip_visitante; ?> &nbsp;|&nbsp;
        <?php echo estaAutenticado() ? '<i class="fas fa-unlock mr-1"></i>Modo Edición' : '<i class="fas fa-lock mr-1"></i>Solo Lectura'; ?>
        &nbsp;&nbsp;<span style="opacity:.6;">⚠️ Este sistema NO hace respaldo de su propia base de datos. Un respaldo que no existe no es un respaldo.</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $mysqli->close(); ?>
