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
 * permisos.php - Laboratorio 4: Permisos Granulares y Seguridad Dinámica
 * 
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Fecha: 13 de marzo de 2026
 * Co-programador en el experimento vibecodingmexico.com
 * 
 * Objetivo: Gestión de permisos granulares con validación en tiempo real
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

global $link; // mi notaes que no es necesario, se llamariadento de cadafuncion pero lo dejo.

// Configuración
$USAR_TAPIZ = false;
$TAPIZ_URL = 'ruta/a/tu/imagen.jpg';
$mensaje = '';
$tipoMensaje = '';

/**
 * Función robusta de validación de permisos
 * Lee directamente de la base de datos en cada llamada
 */
function tiene_permiso($nombre_derecho) {
    global $link;
    
    // Sanitización crítica del parámetro
    $nombre_derecho = strtoupper(trim($nombre_derecho));
    $nombre_derecho = preg_replace('/\s+/', ' ', $nombre_derecho); // colapsar espacios múltiples
    
    // Validar que no venga vacío
    if (empty($nombre_derecho)) {
        return false; // } termina validación 空
    }
    
    // Auto-inserción: Si el derecho NO existe, crearlo
    $sql_check = "SELECT NUM_DERECHO FROM ch_derechos WHERE DESCRIPCION = ?";
    $stmt = mysqli_prepare($link, $sql_check);
    mysqli_stmt_bind_param($stmt, 's', $nombre_derecho);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        // Insertar derecho automáticamente con descripción genérica
        $descripcion = "Permiso generado automáticamente: " . $nombre_derecho;
        $sql_insert = "INSERT INTO ch_derechos (DESCRIPCION) VALUES (?)";
        $stmt_insert = mysqli_prepare($link, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, 's', $nombre_derecho);
        mysqli_stmt_execute($stmt_insert);
        // } termina auto-inserción
    }
    
    // Obtener el NUM_DERECHO del derecho
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
    
    // Validación en caliente: Consultar permisos del usuario
    $sql_permiso = "SELECT IDENTIDAD FROM ch_derechosusuarios 
                    WHERE NUM_DERECHO = ? AND idUsuario = ?";
    $stmt_permiso = mysqli_prepare($link, $sql_permiso);
    mysqli_stmt_bind_param($stmt_permiso, 'ii', $num_derecho, $user_id);
    mysqli_stmt_execute($stmt_permiso);
    $result_permiso = mysqli_stmt_get_result($stmt_permiso);
    
    $tiene = mysqli_num_rows($result_permiso) > 0;
    
    return $tiene; // } termina validación permiso
} // } termina tiene_permiso

/**
 * Obtener lista de usuarios para el dropdown
 */
function obtener_usuarios($link) {
    $sql = "SELECT id, username, first_name, last_name, Rol, PAPELERA 
            FROM cat_users 
            WHERE PAPELERA = 'NO' 
            ORDER BY username ASC";
    $result = mysqli_query($link, $sql);
    
    $usuarios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $usuarios[] = $row;
    }
    return $usuarios;
}

/**
 * Obtener todos los derechos del sistema
 */
function obtener_derechos($link) {
    $sql = "SELECT NUM_DERECHO, DESCRIPCION FROM ch_derechos ORDER BY DESCRIPCION ASC";
    $result = mysqli_query($link, $sql);
    
    $derechos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $derechos[] = $row;
    }
    return $derechos;
}

/**
 * Verificar si un usuario específico tiene un permiso
 */
function usuario_tiene_permiso($link, $id_usuario, $num_derecho) {
    $sql = "SELECT IDENTIDAD FROM ch_derechosusuarios 
            WHERE NUM_DERECHO = ? AND idUsuario = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $num_derecho, $id_usuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0; // } termina verificación
}

/**
 * Asignar permiso a usuario
 */
function asignar_permiso($link, $id_usuario, $num_derecho) {
    // Verificar que no exista para evitar duplicados
    if (!usuario_tiene_permiso($link, $id_usuario, $num_derecho)) {
        $sql = "INSERT INTO ch_derechosusuarios (NUM_DERECHO, idUsuario) VALUES (?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $num_derecho, $id_usuario);
        return mysqli_stmt_execute($stmt);
    }
    return true; // } termina asignación
}

/**
 * Revocar permiso de usuario
 */
function revocar_permiso($link, $id_usuario, $num_derecho) {
    $sql = "DELETE FROM ch_derechosusuarios WHERE NUM_DERECHO = ? AND idUsuario = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $num_derecho, $id_usuario);
    return mysqli_stmt_execute($stmt); // } termina revocación
}

/**
 * Clonar permisos de un usuario a otro (reemplazo, no suma)
 */
function clonar_permisos($link, $usuario_origen, $usuario_destino) {
    // 1. Borrar todos los permisos del destino primero
    $sql_delete = "DELETE FROM ch_derechosusuarios WHERE idUsuario = ?";
    $stmt_delete = mysqli_prepare($link, $sql_delete);
    mysqli_stmt_bind_param($stmt_delete, 'i', $usuario_destino);
    mysqli_stmt_execute($stmt_delete);
    
    // 2. Obtener permisos del origen
    $sql_origen = "SELECT NUM_DERECHO FROM ch_derechosusuarios WHERE idUsuario = ?";
    $stmt_origen = mysqli_prepare($link, $sql_origen);
    mysqli_stmt_bind_param($stmt_origen, 'i', $usuario_origen);
    mysqli_stmt_execute($stmt_origen);
    $result_origen = mysqli_stmt_get_result($stmt_origen);
    
    // 3. Copiar permisos al destino
    while ($row = mysqli_fetch_assoc($result_origen)) {
        $sql_copy = "INSERT INTO ch_derechosusuarios (NUM_DERECHO, idUsuario) VALUES (?, ?)";
        $stmt_copy = mysqli_prepare($link, $sql_copy);
        mysqli_stmt_bind_param($stmt_copy, 'ii', $row['NUM_DERECHO'], $usuario_destino);
        mysqli_stmt_execute($stmt_copy);
    }
    
    return true; // } termina clonación
}

/**
 * Suspender usuario (poner PAPELERA = 'SI')
 */
function suspender_usuario($link, $id_usuario) {
    $sql = "UPDATE cat_users SET PAPELERA = 'SI' WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
    return mysqli_stmt_execute($stmt); // } termina suspensión
}

// ============================================
// PROCESAMIENTO DE ACCIONES
// ============================================

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Obtener usuario seleccionado
$usuario_seleccionado = isset($_POST['usuario_seleccionado']) ? intval($_POST['usuario_seleccionado']) : 0;

// Procesar cambio de permisos
if ($action === 'cambiar_permiso' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = intval($_POST['usuario_id']);
    $num_derecho = intval($_POST['num_derecho']);
    $tiene_permiso = $_POST['tiene_permiso'] === '1';
    
    if ($tiene_permiso) {
        // Revocar permiso
        if (revocar_permiso($link, $usuario_id, $num_derecho)) {
            $mensaje = 'Permiso revokeado correctamente';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'Error al revocar permiso';
            $tipoMensaje = 'danger';
        }
    } else {
        // Asignar permiso
        if (asignar_permiso($link, $usuario_id, $num_derecho)) {
            $mensaje = 'Permiso asignado correctamente';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'Error al asignar permiso';
            $tipoMensaje = 'danger';
        }
    }
} // termina action cambiar_permiso

// Procesar clonación de permisos
if ($action === 'clonar_permisos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $origen = intval($_POST['usuario_origen']);
    $destino = intval($_POST['usuario_destino']);
    
    if ($origen && $destino && $origen !== $destino) {
        if (clonar_permisos($link, $origen, $destino)) {
            $mensaje = 'Permisos clonados correctamente (se reemplazaron)';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'Error al clonar permisos';
            $tipoMensaje = 'danger';
        }
    } else {
        $mensaje = 'Seleccione usuarios diferentes';
        $tipoMensaje = 'warning';
    }
} // termina action clonar

// Procesar suspensión de usuario
if ($action === 'suspender' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_suspender = intval($_POST['usuario_suspender']);
    $mi_user_id = $_SESSION['user_id'];
    
    if ($usuario_suspender === $mi_user_id) {
        $mensaje = 'No puede suspenderse a usted mismo';
        $tipoMensaje = 'danger';
    } else {
        if (suspender_usuario($link, $usuario_suspender)) {
            $mensaje = 'Usuario suspendu correctamente';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'Error al suspender usuario';
            $tipoMensaje = 'danger';
        }
    }
} // termina action suspender

// Obtener datos
$usuarios = obtener_usuarios($link);
$todos_derechos = obtener_derechos($link);

// Si hay usuario seleccionado, obtener sus permisos
$permisos_usuario = [];
if ($usuario_seleccionado > 0) {
    $sql_permisos = "SELECT d.NUM_DERECHO FROM ch_derechosusuarios du 
                     JOIN ch_derechos d ON du.NUM_DERECHO = d.NUM_DERECHO 
                     WHERE du.idUsuario = ?";
    $stmt_permisos = mysqli_prepare($link, $sql_permisos);
    mysqli_stmt_bind_param($stmt_permisos, 'i', $usuario_seleccionado);
    mysqli_stmt_execute($stmt_permisos);
    $result_permisos = mysqli_stmt_get_result($stmt_permisos);
    while ($row = mysqli_fetch_assoc($result_permisos)) {
        $permisos_usuario[] = $row['NUM_DERECHO'];
    }
} // termina obtener permisos usuario

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Permisos - Sistema</title>
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
        
        .permisos-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .permisos-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
        }
        
        .permiso-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .permiso-item:hover {
            background: #f8f9fa;
        }
        
        .btn-permiso {
            width: 120px;
            transition: all 0.3s ease;
        }
        
        .btn-permiso.btn-success:hover {
            background: #dc3545 !important;
            border-color: #dc3545 !important;
        }
        
        .btn-permiso.btn-danger:hover {
            background: #28a745 !important;
            border-color: #28a745 !important;
        }
        
        .clone-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 2px solid #dee2e6;
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
        
        .btn-suspender {
            background: #dc3545;
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-suspender:hover:not(:disabled) {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-suspender:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .section-title {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            <i class="fas fa-shield-alt mr-2"></i>Permisos
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
                    <i class="fas fa-shield-alt mr-2"></i>Gestión de Permisos
                </h3>
                <a href="?module=dashboard" class="btn btn-volver">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
        
        <!-- Mensajes -->
        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas <?php echo ($tipoMensaje === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Selector de Usuario y Suspensión -->
        <div class="permisos-card mb-4">
            <div class="permisos-header">
                <h4 class="mb-0">
                    <i class="fas fa-users mr-2"></i>Seleccionar Usuario
                </h4>
            </div>
            <div class="p-4">
                <form method="POST" action="permisos.php" class="mb-0">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <label for="usuario_seleccionado" class="font-weight-bold">
                                <i class="fas fa-user mr-2"></i>Usuario
                            </label>
                            <select class="form-control" id="usuario_seleccionado" name="usuario_seleccionado" onchange="this.form.submit()">
                                <option value="0">-- Seleccionar Usuario --</option>
                                <?php foreach ($usuarios as $usr): ?>
                                <option value="<?php echo $usr['id']; ?>" <?php echo ($usuario_seleccionado == $usr['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usr['username'] . ' - ' . $usr['first_name'] . ' ' . $usr['last_name'] . ' (' . $usr['Rol'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($usuario_seleccionado > 0): 
                            $mi_user_id = $_SESSION['user_id'];
                            $puede_suspender = ($usuario_seleccionado != $mi_user_id);
                        ?>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-suspender text-white btn-block <?php echo !$puede_suspender ? 'disabled' : ''; ?>"
                                    <?php echo !$puede_suspender ? 'disabled' : ''; ?>
                                    data-toggle="modal" data-target="#suspenderModal"
                                    title="<?php echo !$puede_suspender ? 'No puede suspenderse a usted mismo' : 'Suspender usuario'; ?>">
                                <i class="fas fa-ban mr-2"></i>Suspender Usuario
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Cuadrícula de Permisos (Solo si hay usuario seleccionado) -->
        <?php if ($usuario_seleccionado > 0): ?>
        <div class="permisos-card mb-4">
            <div class="permisos-header">
                <h4 class="mb-0">
                    <i class="fas fa-traffic-light mr-2"></i>Semáforo de Permisos
                </h4>
            </div>
            <div class="p-0">
                <?php foreach ($todos_derechos as $derecho): 
                    $tiene = in_array($derecho['NUM_DERECHO'], $permisos_usuario);
                ?>
                <div class="permiso-item d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <i class="fas fa-key text-info mr-2"></i>
                        <span class="font-weight-bold"><?php echo htmlspecialchars($derecho['DESCRIPCION']); ?></span>
                    </div>
                    <form method="POST" action="permisos.php?action=cambiar_permiso" class="d-inline">
                        <input type="hidden" name="usuario_id" value="<?php echo $usuario_seleccionado; ?>">
                        <input type="hidden" name="num_derecho" value="<?php echo $derecho['NUM_DERECHO']; ?>">
                        <input type="hidden" name="tiene_permiso" value="<?php echo $tiene ? '1' : '0'; ?>">
                        <button type="submit" class="btn btn-permiso btn-<?php echo $tiene ? 'success' : 'danger'; ?> text-white">
                            <i class="fas <?php echo $tiene ? 'fa-times' : 'fa-plus'; ?> mr-1"></i>
                            <?php echo $tiene ? 'Revocar' : 'Asignar'; ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($todos_derechos)): ?>
                <div class="text-center text-muted p-4">
                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                    <p>No hay derechos definidos en el sistema</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Clonación de Permisos -->
        <div class="permisos-card">
            <div class="permisos-header">
                <h4 class="mb-0">
                    <i class="fas fa-copy mr-2"></i>Clonar Permisos
                </h4>
            </div>
            <div class="p-4">
                <div class="clone-card">
                    <p class="text-muted mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Copia los permisos de un usuario a otro. Los permisos del destino serán <strong>reemplazados</strong> (no se suman).
                    </p>
                    <form method="POST" action="permisos.php?action=clonar_permisos">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-user-clock mr-1"></i>Usuario Origen (Copiar de)
                                    </label>
                                    <select class="form-control" name="usuario_origen" required>
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($usuarios as $usr): ?>
                                        <option value="<?php echo $usr['id']; ?>">
                                            <?php echo htmlspecialchars($usr['username'] . ' - ' . $usr['first_name'] . ' ' . $usr['last_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 text-center d-none d-md-block">
                                <i class="fas fa-arrow-right fa-2x text-muted mt-4"></i>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-user-plus mr-1"></i>Usuario Destino (Copiar a)
                                    </label>
                                    <select class="form-control" name="usuario_destino" required>
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($usuarios as $usr): ?>
                                        <option value="<?php echo $usr['id']; ?>">
                                            <?php echo htmlspecialchars($usr['username'] . ' - ' . $usr['first_name'] . ' ' . $usr['last_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn text-white btn-block" style="background: linear-gradient(135deg, #2c3e50, #3498db);">
                            <i class="fas fa-copy mr-2"></i>Clonar Permisos
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Suspensión -->
<div class="modal fade" id="suspenderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #dc3545; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirmar Suspensión
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de suspender a este usuario?</p>
                <p class="text-muted">El usuario no podrá iniciar sesión hasta que se reactive.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <form method="POST" action="permisos.php?action=suspender">
                    <input type="hidden" name="usuario_suspender" value="<?php echo $usuario_seleccionado; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban mr-1"></i>Suspender
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-3" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; position: fixed; bottom: 0; width: 100%; z-index: 1000;">
    <div class="container text-center">
        <small>
            <i class="fas fa-shield-alt mr-1"></i>Permisos | 
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
