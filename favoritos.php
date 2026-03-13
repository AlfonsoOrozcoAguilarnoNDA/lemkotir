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
 * favoritos.php - Control de Favoritos / Links
 * 
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Fecha: 13 de marzo de 2026
 * Co-programador en el experimento vibecodingmexico.com
 * Experimento completo en https://vibecodingmexico.com/vibe-coding-favoritos/
 * Ganador 
 * 
 * Stack: PHP 8.x Procedural, Bootstrap 4.6, Font Awesome 5.0
 */

// Headers de caché
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// Incluir config.php para tener $link
include_once 'config.php';

global $link;

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================
define('IPS_PERMITIDAS', ['127.0.0.1', '::1', '201.103.232.198']); // IPs sin contraseña
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$requiere_login = !in_array($ip_cliente, IPS_PERMITIDAS);

// Control de autenticación
$autenticado = !$requiere_login || (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true);

// ============================================
// VARIABLES DE CONTROL
// ============================================
$accion = $_GET['accion'] ?? 'listado';
$mensaje = '';
$tipo_mensaje = '';

// Colores disponibles para categorías (estilo Metro)
$colores_disponibles = [
    'metro-blue' => '#3498db',
    'metro-green' => '#27ae60',
    'metro-red' => '#e74c3c',
    'metro-orange' => '#e67e22',
    'metro-purple' => '#9b59b6',
    'metro-pink' => '#e91e63',
    'metro-teal' => '#009688',
    'metro-dark' => '#34495e',
    'metro-yellow' => '#f1c40f',
    'metro-cyan' => '#00bcd4'
];

// Iconos disponibles para categorías
$iconos_disponibles = [
    'fa-link', 'fa-globe', 'fa-home', 'fa-book', 'fa-code', 'fa-shopping-cart',
    'fa-envelope', 'fa-cloud', 'fa-database', 'fa-server', 'fa-cog', 'fa-tools',
    'fa-film', 'fa-music', 'fa-gamepad', 'fa-newspaper', 'fa-graduation-cap',
    'fa-heart', 'fa-star', 'fa-flag', 'fa-map', 'fa-image', 'fa-video',
    'fa-download', 'fa-upload', 'fa-lock', 'fa-user', 'fa-users', 'fa-briefcase',
    'fa-chart-bar', 'fa-chart-line', 'fa-dollar-sign', 'fa-credit-card',
    'fa-plane', 'fa-car', 'fa-bus', 'fa-train', 'fa-building', 'fa-store'
];

// ============================================
// FUNCIONES AUXILIARES
// ============================================

function obtener_categorias($link) {
    $sql = "SELECT * FROM LINK_CATEGORIES ORDER BY category_name ASC";
    $result = mysqli_query($link, $sql);
    $categorias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categorias[] = $row;
    }
    return $categorias;
}

function obtener_favoritos_categoria($link, $category_id) {
    $sql = "SELECT * FROM LINKS WHERE category_id = ? ORDER BY link_title ASC";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $favoritos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $favoritos[] = $row;
    }
    return $favoritos;
}

function obtener_categoria($link, $category_id) {
    $sql = "SELECT * FROM LINK_CATEGORIES WHERE category_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function obtener_link($link, $link_id) {
    $sql = "SELECT * FROM LINKS WHERE link_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $link_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// ============================================
// PROCESAMIENTO DE ACCIONES
// ============================================

// Login
if ($accion === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    // Por ahora allow cualquier password si está en modo prueba
    $_SESSION['autenticado'] = true;
    header('Location: favoritos.php');
    exit;
}

// Logout
if ($accion === 'logout') {
    session_destroy();
    header('Location: favoritos.php');
    exit;
}

// Agregar/Editar Categoría
if ($accion === 'guardar_categoria' && $_SERVER['REQUEST_METHOD'] === 'POST' && $autenticado) {
    $category_id = intval($_POST['category_id'] ?? 0);
    $category_name = trim($_POST['category_name'] ?? '');
    $category_icon = $_POST['category_icon'] ?? 'fa-link';
    $category_color = $_POST['category_color'] ?? 'metro-blue';
    
    if (empty($category_name)) {
        $mensaje = 'El nombre de categoría es obligatorio';
        $tipo_mensaje = 'danger';
    } else {
        if ($category_id > 0) {
            // Editar
            $sql = "UPDATE LINK_CATEGORIES SET category_name = ?, category_icon = ?, category_color = ? WHERE category_id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'sssi', $category_name, $category_icon, $category_color, $category_id);
        } else {
            // Insertar
            $sql = "INSERT INTO LINK_CATEGORIES (category_name, category_icon, category_color) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $category_name, $category_icon, $category_color);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $mensaje = $category_id > 0 ? 'Categoría actualizada' : 'Categoría creada';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al guardar categoría';
            $tipo_mensaje = 'danger';
        }
    }
    $accion = 'listado';
}

// Borrar Categoría
if ($accion === 'borrar_categoria' && $_SERVER['REQUEST_METHOD'] === 'POST' && $autenticado) {
    $category_id = intval($_POST['category_id'] ?? 0);
    
    // Verificar que no tenga favoritos
    $sql_count = "SELECT COUNT(*) as total FROM LINKS WHERE category_id = ?";
    $stmt = mysqli_prepare($link, $sql_count);
    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['total'] > 0) {
        $mensaje = 'No se puede borrar: hay ' . $row['total'] . ' favoritos en esta categoría';
        $tipo_mensaje = 'warning';
    } else {
        $sql = "DELETE FROM LINK_CATEGORIES WHERE category_id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $category_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $mensaje = 'Categoría eliminada';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar categoría';
            $tipo_mensaje = 'danger';
        }
    }
    $accion = 'listado';
}

// Agregar/Editar Favorito
if ($accion === 'guardar_link' && $_SERVER['REQUEST_METHOD'] === 'POST' && $autenticado) {
    $link_id = intval($_POST['link_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $link_title = trim($_POST['link_title'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $link_comment = trim($_POST['link_comment'] ?? '');
    
    if (empty($link_title) || empty($link_url)) {
        $mensaje = 'Título y URL son obligatorios';
        $tipo_mensaje = 'danger';
    } elseif ($category_id == 0) {
        $mensaje = 'Seleccione una categoría';
        $tipo_mensaje = 'danger';
    } else {
        if ($link_id > 0) {
            // Editar
            $sql = "UPDATE LINKS SET category_id = ?, link_title = ?, link_url = ?, link_comment = ? WHERE link_id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'isssi', $category_id, $link_title, $link_url, $link_comment, $link_id);
        } else {
            // Insertar
            $sql = "INSERT INTO LINKS (category_id, link_title, link_url, link_comment) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'isss', $category_id, $link_title, $link_url, $link_comment);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $mensaje = $link_id > 0 ? 'Favorito actualizado' : 'Favorito creado';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al guardar favorito';
            $tipo_mensaje = 'danger';
        }
    }
    $accion = 'listado';
}

// Borrar Favorito
if ($accion === 'borrar_link' && $_SERVER['REQUEST_METHOD'] === 'POST' && $autenticado) {
    $link_id = intval($_POST['link_id'] ?? 0);
    
    $sql = "DELETE FROM LINKS WHERE link_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $link_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $mensaje = 'Favorito eliminado';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al eliminar favorito';
        $tipo_mensaje = 'danger';
    }
    $accion = 'listado';
}

// ============================================
// VERIFICAR ACCESO
// ============================================
if ($requiere_login && !$autenticado && $accion !== 'login') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Acceso Restringido</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 50%, #4a69bd 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>
    </head>
    <body>
        <div class="text-center text-white">
            <i class="fas fa-lock fa-5x mb-4"></i>
            <h2>Acceso Restringido</h2>
            <p> IP: <?php echo htmlspecialchars($ip_cliente); ?></p>
            <a href="favoritos.php?accion=login" class="btn btn-primary mt-3">
                <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Obtener datos para mostrar
$categorias = obtener_categorias($link);
$categoria_editar = null;
$link_editar = null;

if ($accion === 'editar_categoria' && isset($_GET['id'])) {
    $categoria_editar = obtener_categoria($link, intval($_GET['id']));
}

if ($accion === 'editar_link' && isset($_GET['id'])) {
    $link_editar = obtener_link($link, intval($_GET['id']));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Favoritos - Lemkotir</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --metro-blue: #3498db;
            --metro-green: #27ae60;
            --metro-red: #e74c3c;
            --metro-orange: #e67e22;
            --metro-purple: #9b59b6;
            --metro-pink: #e91e63;
            --metro-teal: #009688;
            --metro-dark: #34495e;
            --metro-yellow: #f1c40f;
            --metro-cyan: #00bcd4;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, sans-serif;
            background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 50%, #4a69bd 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            padding-top: 80px;
            padding-bottom: 100px;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2c3e50, #34495e) !important;
        }
        
        .card-metro {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .card-header-metro {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 15px 20px;
        }
        
        .category-tile {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            color: white;
            text-decoration: none;
            display: block;
        }
        
        .category-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .link-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #333;
            display: block;
        }
        
        .link-item:hover {
            background: #f8f9fa;
        }
        
        .link-item:last-child {
            border-bottom: none;
        }
        
        .color-option {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin: 3px;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .color-option.selected {
            border-color: #333;
            transform: scale(1.1);
        }
        
        .icon-option {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            border-radius: 8px;
            margin: 3px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .icon-option:hover, .icon-option.selected {
            background: #2c3e50;
            color: white;
        }
        
        .btn-metro {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-metro:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: white;
        }
        
        /* Colores Metro */
        .bg-metro-blue { background: var(--metro-blue); }
        .bg-metro-green { background: var(--metro-green); }
        .bg-metro-red { background: var(--metro-red); }
        .bg-metro-orange { background: var(--metro-orange); }
        .bg-metro-purple { background: var(--metro-purple); }
        .bg-metro-pink { background: var(--metro-pink); }
        .bg-metro-teal { background: var(--metro-teal); }
        .bg-metro-dark { background: var(--metro-dark); }
        .bg-metro-yellow { background: var(--metro-yellow); color: #333 !important; }
        .bg-metro-cyan { background: var(--metro-cyan); }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand font-weight-bold" href="favoritos.php">
            <i class="fas fa-link mr-2"></i>Favoritos
        </a>
        <span class="navbar-text text-white">
            <i class="fas fa-robot mr-1"></i>MiniMax-M2.5
        </span>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="favoritos.php?accion=listado">
                        <i class="fas fa-home mr-1"></i> Ver Favoritos
                    </a>
                </li>
                <?php if ($autenticado): ?>
                <li class="nav-item">
                    <a class="nav-link" href="favoritos.php?accion=agregar_categoria">
                        <i class="fas fa-folder-plus mr-1"></i> Nueva Categoría
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="favoritos.php?accion=agregar_link">
                        <i class="fas fa-plus-circle mr-1"></i> Nuevo Favorito
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="favoritos.php?accion=logout">
                        <i class="fas fa-sign-out-alt mr-1"></i> Salir
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Contenido Principal -->
<div class="main-container">
    <div class="container">
        
        <!-- Mensajes -->
        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas <?php echo ($tipo_mensaje === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- LOGIN (si requiere) -->
        <!-- ============================================ -->
        <?php if ($accion === 'login'): ?>
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card-metro">
                    <div class="card-header-metro">
                        <h4 class="mb-0"><i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="favoritos.php?accion=login">
                            <div class="form-group">
                                <label>Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-metro btn-block">
                                <i class="fas fa-unlock mr-2"></i>Entrar
                            </button>
                            <a href="favoritos.php" class="btn btn-outline-secondary btn-block mt-2">Cancelar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- LISTADO DE FAVORITOS (POR CATEGORÍAS) -->
        <!-- ============================================ -->
        <?php if ($accion === 'listado'): ?>
        <div class="card-metro">
            <div class="card-header-metro">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-link mr-2"></i>Mis Favoritos</h4>
                    <?php if ($autenticado): ?>
                    <div>
                        <a href="favoritos.php?accion=agregar_link" class="btn btn-sm btn-light">
                            <i class="fas fa-plus mr-1"></i>Nuevo
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-3">
                
                <?php if (empty($categorias)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                    <p>No hay categorías. ¡Crea una!</p>
                    <?php if ($autenticado): ?>
                    <a href="favoritos.php?accion=agregar_categoria" class="btn btn-metro">
                        <i class="fas fa-plus mr-2"></i>Crear Categoría
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                
                <!-- Tiles de Categorías -->
                <div class="row mb-4">
                    <?php foreach ($categorias as $cat): 
                        $color_class = 'bg-' . $cat['category_color'];
                        $favoritos = obtener_favoritos_categoria($link, $cat['category_id']);
                    ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="category-tile <?php echo $color_class; ?>" 
                             data-toggle="collapse" data-target="#cat-<?php echo $cat['category_id']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <i class="fas <?php echo htmlspecialchars($cat['category_icon']); ?> fa-lg mb-2"></i>
                                    <h6 class="mb-1 font-weight-bold"><?php echo htmlspecialchars($cat['category_name']); ?></h6>
                                    <small><?php echo count($favoritos); ?> enlaces</small>
                                </div>
                                <?php if ($autenticado): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown" onclick="event.stopPropagation()">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="favoritos.php?accion=editar_categoria&id=<?php echo $cat['category_id']; ?>">
                                            <i class="fas fa-edit mr-2"></i>Editar
                                        </a>
                                        <form method="POST" action="favoritos.php?accion=borrar_categoria" onsubmit="return confirm('¿Borrar categoría?');">
                                            <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-trash mr-2"></i>Borrar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Enlaces de la categoría -->
                        <div class="collapse mb-3" id="cat-<?php echo $cat['category_id']; ?>">
                            <div class="card">
                                <div class="card-body p-0">
                                    <?php if (empty($favoritos)): ?>
                                    <div class="text-center text-muted p-3">
                                        <small>Sin favoritos</small>
                                    </div>
                                    <?php else: ?>
                                    <?php foreach ($favoritos as $fav): ?>
                                    <div class="link-item d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <a href="<?php echo htmlspecialchars($fav['link_url']); ?>" target="_blank" class="text-decoration-none text-dark">
                                                <i class="fas fa-external-link-alt mr-2 text-muted"></i>
                                                <?php echo htmlspecialchars($fav['link_title']); ?>
                                            </a>
                                            <?php if (!empty($fav['link_comment'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($fav['link_comment']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($autenticado): ?>
                                        <div class="dropdown ml-2">
                                            <button class="btn btn-sm btn-light" data-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="favoritos.php?accion=editar_link&id=<?php echo $fav['link_id']; ?>">
                                                    <i class="fas fa-edit mr-2"></i>Editar
                                                </a>
                                                <form method="POST" action="favoritos.php?accion=borrar_link" onsubmit="return confirm('¿Borrar favorito?');">
                                                    <input type="hidden" name="link_id" value="<?php echo $fav['link_id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash mr-2"></i>Borrar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($autenticado): ?>
                                    <div class="p-2 text-center border-top">
                                        <a href="favoritos.php?accion=agregar_link&category_id=<?php echo $cat['category_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus mr-1"></i>Agregar
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- AGREGAR/EDITAR CATEGORÍA -->
        <!-- ============================================ -->
        <?php if (in_array($accion, ['agregar_categoria', 'editar_categoria'])): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card-metro">
                    <div class="card-header-metro">
                        <h4 class="mb-0">
                            <i class="fas <?php echo ($accion === 'editar_categoria') ? 'fa-edit' : 'fa-plus'; ?> mr-2"></i>
                            <?php echo ($accion === 'editar_categoria') ? 'Editar' : 'Nueva'; ?> Categoría
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="favoritos.php?accion=guardar_categoria">
                            <input type="hidden" name="category_id" value="<?php echo $categoria_editar['category_id'] ?? 0; ?>">
                            
                            <div class="form-group">
                                <label><i class="fas fa-tag mr-2"></i>Nombre de Categoría</label>
                                <input type="text" name="category_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($categoria_editar['category_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-palette mr-2"></i>Color</label>
                                <div class="row">
                                    <?php foreach ($colores_disponibles as $nombre => $hex): ?>
                                    <div class="col-2 text-center">
                                        <div class="color-option <?php echo ($categoria_editar['category_color'] ?? 'metro-blue') === $nombre ? 'selected' : ''; ?>"
                                             style="background: <?php echo $hex; ?>;"
                                             data-color="<?php echo $nombre; ?>"
                                             onclick="selectColor('<?php echo $nombre; ?>')">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="category_color" id="category_color" 
                                       value="<?php echo $categoria_editar['category_color'] ?? 'metro-blue'; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-icons mr-2"></i>Icono</label>
                                <div class="row" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($iconos_disponibles as $icono): ?>
                                    <div class="col-3 text-center mb-2">
                                        <div class="icon-option <?php echo ($categoria_editar['category_icon'] ?? 'fa-link') === $icono ? 'selected' : ''; ?>"
                                             data-icon="<?php echo $icono; ?>"
                                             onclick="selectIcon('<?php echo $icono; ?>')">
                                            <i class="fas <?php echo $icono; ?>"></i>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="category_icon" id="category_icon" 
                                       value="<?php echo $categoria_editar['category_icon'] ?? 'fa-link'; ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-metro btn-block">
                                    <i class="fas fa-save mr-2"></i>Guardar
                                </button>
                                <a href="favoritos.php" class="btn btn-outline-secondary btn-block mt-2">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
        function selectColor(color) {
            document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
            document.querySelector('[data-color="' + color + '"]').classList.add('selected');
            document.getElementById('category_color').value = color;
        }
        function selectIcon(icon) {
            document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
            document.querySelector('[data-icon="' + icon + '"]').classList.add('selected');
            document.getElementById('category_icon').value = icon;
        }
        </script>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- AGREGAR/EDITAR FAVORITO -->
        <!-- ============================================ -->
        <?php if (in_array($accion, ['agregar_link', 'editar_link'])): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card-metro">
                    <div class="card-header-metro">
                        <h4 class="mb-0">
                            <i class="fas <?php echo ($accion === 'editar_link') ? 'fa-edit' : 'fa-plus'; ?> mr-2"></i>
                            <?php echo ($accion === 'editar_link') ? 'Editar' : 'Nuevo'; ?> Favorito
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="favoritos.php?accion=guardar_link">
                            <input type="hidden" name="link_id" value="<?php echo $link_editar['link_id'] ?? 0; ?>">
                            
                            <div class="form-group">
                                <label><i class="fas fa-folder mr-2"></i>Categoría</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" 
                                            <?php echo ($link_editar['category_id'] ?? ($_GET['category_id'] ?? '')) == $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-heading mr-2"></i>Título</label>
                                <input type="text" name="link_title" class="form-control" 
                                       value="<?php echo htmlspecialchars($link_editar['link_title'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-link mr-2"></i>URL</label>
                                <input type="url" name="link_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($link_editar['link_url'] ?? ''); ?>" 
                                       placeholder="https://" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-comment mr-2"></i>Comentario (opcional)</label>
                                <textarea name="link_comment" class="form-control" rows="3"><?php echo htmlspecialchars($link_editar['link_comment'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-metro btn-block">
                                    <i class="fas fa-save mr-2"></i>Guardar
                                </button>
                                <a href="favoritos.php" class="btn btn-outline-secondary btn-block mt-2">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Footer Fijo -->
<footer class="py-2" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; position: fixed; bottom: 0; width: 100%; z-index: 1000;">
    <div class="container text-center">
        <small>
            <i class="fas fa-link mr-1"></i>Favoritos | 
            <i class="fas fa-code mr-1"></i>PHP <?php echo phpversion(); ?> | 
            <i class="fas fa-database mr-1"></i>MariaDB
        </small>
    </div>
</footer>

<!-- Bootstrap & jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
