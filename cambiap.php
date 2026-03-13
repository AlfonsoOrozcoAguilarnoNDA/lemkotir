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
 * cambiap.php - Caso de Prueba: Seguridad del Sistema
 * 
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Fecha: 13 de marzo de 2026
 * Co-programador en el experimento vibecodingmexico.com
 * 
 * Objetivo: Proteger contenido con permiso "VER SISTEMA"
 */

// Configuración de caché y UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: validate.php?module=login');
    exit;
}

include_once 'config.php';

global $link;

/**
 * Función robusta de validación de permisos (misma que en permisos.php)
 */
function tiene_permiso($nombre_derecho) {
    global $link;
    
    // Sanitización crítica del parámetro
    $nombre_derecho = strtoupper(trim($nombre_derecho));
    $nombre_derecho = preg_replace('/\s+/', ' ', $nombre_derecho);
    
    if (empty($nombre_derecho)) {
        return false; // } termina validación vacío
    }
    
    // Auto-inserción: Si el derecho NO existe, crearlo
    $sql_check = "SELECT NUM_DERECHO FROM ch_derechos WHERE DESCRIPCION = ?";
    $stmt = mysqli_prepare($link, $sql_check);
    mysqli_stmt_bind_param($stmt, 's', $nombre_derecho);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $sql_insert = "INSERT INTO ch_derechos (DESCRIPCION) VALUES (?)";
        $stmt_insert = mysqli_prepare($link, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, 's', $nombre_derecho);
        mysqli_stmt_execute($stmt_insert);
    } // } termina auto-inserción
    
    // Obtener el NUM_DERECHO
    $sql_get = "SELECT NUM_DERECHO FROM ch_derechos WHERE DESCRIPCION = ?";
    $stmt_get = mysqli_prepare($link, $sql_get);
    mysqli_stmt_bind_param($stmt_get, 's', $nombre_derecho);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $row_derecho = mysqli_fetch_assoc($result_get);
    
    if (!$row_derecho) {
        return false; // } termina validación derecho
    }
    
    $num_derecho = $row_derecho['NUM_DERECHO'];
    $user_id = $_SESSION['user_id'];
    
    // Validación en caliente
    $sql_permiso = "SELECT IDENTIDAD FROM ch_derechosusuarios 
                    WHERE NUM_DERECHO = ? AND idUsuario = ?";
    $stmt_permiso = mysqli_prepare($link, $sql_permiso);
    mysqli_stmt_bind_param($stmt_permiso, 'ii', $num_derecho, $user_id);
    mysqli_stmt_execute($stmt_permiso);
    $result_permiso = mysqli_stmt_get_result($stmt_permiso);
    
    return mysqli_num_rows($result_permiso) > 0; // } termina validación permiso
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Cambios - Sistema</title>
    <!-- Bootstrap 4.6 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 5.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --metal-blue: #2c3e50;
            --metal-light: #34495e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 50%, #4a69bd 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-container {
            padding-top: 80px;
            padding-bottom: 60px;
            min-height: 100vh;
        }
        
        .system-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .system-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
        }
        
        .info-row {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .info-value {
            font-family: 'Courier New', monospace;
            color: #495057;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .no-permission {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
        }
        
        .btn-volver {
            background: transparent;
            border: 2px solid white;
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            background: white;
            color: #2c3e50;
        }
        
        .lock-icon {
            font-size: 3rem;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding-top: 70px;
                padding-bottom: 50px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, #2c3e50, #34495e);">
    <div class="container">
        <a class="navbar-brand font-weight-bold" href="?module=dashboard">
            <i class="fas fa-server mr-2"></i>Cambios
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="?module=dashboard">
                        <i class="fas fa-home mr-1"></i>Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?module=logout">
                        <i class="fas fa-sign-out-alt mr-1"></i>Salir
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-container">
    <div class="container">
        <!-- Título -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="text-white font-weight-bold">
                    <i class="fas fa-server mr-2"></i>Información del Sistema
                </h3>
                <a href="?module=dashboard" class="btn btn-volver">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
        
        <!-- Verificación de Permiso -->
        <?php if (tiene_permiso("VER SISTEMA")): ?>
        
        <!-- Información del Sistema (Con Permiso) -->
        <div class="system-card">
            <div class="system-header">
                <h4 class="mb-0">
                    <i class="fas fa-desktop mr-2"></i>Datos del Servidor
                </h4>
            </div>
            <div class="p-4">
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fas fa-server mr-1"></i>php_uname()
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo htmlspecialchars(php_uname()); ?></code>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fab fa-linux mr-1"></i>PHP_OS
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo htmlspecialchars(PHP_OS); ?></code>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fas fa-code mr-1"></i>Versión PHP
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo phpversion(); ?></code>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fas fa-award mr-1"></i>php_uname('s')
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo htmlspecialchars(php_uname('s')); ?></code>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fas fa-desktop mr-1"></i>php_uname('n')
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo htmlspecialchars(php_uname('n')); ?></code>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fas fa-redo mr-1"></i>php_uname('r')
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo htmlspecialchars(php_uname('r')); ?></code>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fas fa-tag mr-1"></i>php_uname('v')
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo htmlspecialchars(php_uname('v')); ?></code>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3">
                        <span class="info-label">
                            <i class="fas fa-microchip mr-1"></i>php_uname('m')
                        </span>
                    </div>
                    <div class="col-md-9">
                        <code class="info-value d-block"><?php echo htmlspecialchars(php_uname('m')); ?></code>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        
        <!-- Sin Permiso -->
        <div class="system-card no-permission">
            <div class="p-4 text-center">
                <i class="fas fa-lock lock-icon mb-3 d-block"></i>
                <h5 class="text-muted">
                    <i class="fas fa-exclamation-circle mr-2"></i>No tiene permiso para ver sistema
                </h5>
                <p class="text-muted">
                    Contacte al administrador para solicitar el permiso "VER SISTEMA"
                </p>
                <a href="?module=permisos" class="btn btn-outline-secondary">
                    <i class="fas fa-shield-alt mr-2"></i>Ir a Permisos
                </a>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer class="py-3" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; position: fixed; bottom: 0; width: 100%; z-index: 1000;">
    <div class="container text-center">
        <small>
            <i class="fas fa-server mr-1"></i>Cambios | 
            <i class="fas fa-shield-alt ml-2 mr-1"></i>
            <?php echo date('Y'); ?> Todos los derechos reservados
        </small>
    </div>
</footer>

<!-- Bootstrap 4.6 JS y jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
