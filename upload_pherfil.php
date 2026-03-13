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
 * upload_pherfil.php - Laboratorio 3: Perfil, Seguridad y Coherencia Visual
 * * Modelo de IA: Gemini 1.5 Pro (Refactoring)
 * Fecha: 13 de marzo de 2026
 * Co-programador en el experimento vibecodingmexico.com
 */

// Configuración de caché y UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();
include_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ?module=login');
    exit;
}

// Variable global de conexión (como se indicó en el prompt)
global $link;

// Configuración de fondo
$USAR_TAPIZ = false;
$TAPIZ_URL = 'ruta/a/tu/imagen.jpg';

// Mensajes
$mensaje = '';
$tipoMensaje = '';

// Obtener módulo
$module = isset($_GET['module']) ? $_GET['module'] : 'perfil';

/**
 * Función para verificar y añadir el campo mesdia si no existe
 * @param mysqli $link Conexión a la base de datos
 * @return bool True si se añadió o ya existe
 */
function checkAndAddField($link) {
    try {
        // Verificar si la columna existe
        $result = mysqli_query($link, "SHOW COLUMNS FROM cat_users LIKE 'mesdia'");
        
        if (mysqli_num_rows($result) == 0) {
            // La columna no existe, añadirla
            $sql = "ALTER TABLE cat_users ADD mesdia VARCHAR(5) NULL DEFAULT '00:00' AFTER whatsapp";
            if (mysqli_query($link, $sql)) {
                return true;
            }
            return false;
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Convertir blob de imagen a base64 para mostrar
 * @param blob $blob Datos de la imagen
 * @param string $mime Tipo MIME de la imagen
 * @return string Imagen en formato data URI
 */
function blobToBase64($blob, $mime) {
    if (empty($blob)) {
        return null;
    }
    return 'data:' . $mime . ';base64,' . base64_encode($blob);
}

/**
 * Obtener imagen de perfil del usuario
 * @param int $user_id ID del usuario
 * @return string URL o data URI de la imagen
 */
function obtenerImagenPerfil($link, $user_id) {
    $sql = "SELECT profile_blob, profile_mime_type, profile_image FROM cat_users WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Si tiene profile_blob, mostrarlo
        if (!empty($row['profile_blob'])) {
            $mime = $row['profile_mime_type'] ?? 'image/png';
            return blobToBase64($row['profile_blob'], $mime);
        }
        
        // Si no tiene blob, usar profile_image
        if (!empty($row['profile_image'])) {
            return 'assets/images/' . $row['profile_image'];
        }
    }
    
    // Default
    return 'assets/images/_defaultUser.png';
}

/**
 * Obtener datos del usuario
 * @param int $user_id ID del usuario
 * @return array Datos del usuario
 */
function obtenerDatosUsuario($link, $user_id) {
    $sql = "SELECT username, email, first_name, last_name, Rol, mesdia, last_login_at, profile_image, profile_blob, profile_mime_type 
            FROM cat_users WHERE id = ? AND PAPELERA = 'NO'";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Verificar si es navidad (mes 12, día 25)
 * @param string $mesdia Campo de mes:día
 * @return bool
 */
function esNavidad($mesdia) {
    if (empty($mesdia)) return false;
    $partes = explode(':', $mesdia);
    return (count($partes) == 2 && $partes[0] == '12' && $partes[1] == '25');
}

/**
 * Verificar si es cumpleaños hoy
 * @param string $mesdia Campo de mes:día
 * @return bool
 */
function esCumpleanos($mesdia) {
    if (empty($mesdia)) return false;
    $partes = explode(':', $mesdia);
    $mes_actual = date('m');
    $dia_actual = date('d');
    return (count($partes) == 2 && $partes[0] == $mes_actual && $partes[1] == $dia_actual);
}

// Verificar y añadir campo mesdia
checkAndAddField($link);

// Obtener datos del usuario logueado
$user_id = $_SESSION['user_id'];
$userData = obtenerDatosUsuario($link, $user_id);

// Procesar upload de imagen
if ($module === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_upload']) && $_FILES['profile_upload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_upload'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmpName = $file['tmp_name'];
        $fileType = $file['type'];
        
        // Validar tipo (solo PNG)
        $allowedTypes = ['image/png'];
        if (!in_array($fileType, $allowedTypes)) {
            $mensaje = 'Solo se permiten archivos PNG';
            $tipoMensaje = 'danger';
        } elseif ($fileSize > 800 * 1024) { // 800KB
            $mensaje = 'El archivo no puede superar los 800KB';
            $tipoMensaje = 'danger';
        } else {
            // Leer contenido del archivo
            $imageData = file_get_contents($fileTmpName);
            
            // Actualizar base de datos
            $sql = "UPDATE cat_users SET profile_blob = ?, profile_mime_type = 'image/png', profile_image = NULL WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'si', $imageData, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $mensaje = 'Imagen de perfil actualizada correctamente';
                $tipoMensaje = 'success';
                // Refrescar datos
                $userData = obtenerDatosUsuario($link, $user_id);
            } else {
                $mensaje = 'Error al actualizar la imagen';
                $mensaje = 'Error: ' . mysqli_error($link);
                $tipoMensaje = 'danger';
            }
        }
    } else {
        $mensaje = 'No se recibió ningún archivo';
        $tipoMensaje = 'danger';
    }
}

// Procesar actualización de mesdia (cumpleaños)
if ($module === 'update_birthday' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mes = isset($_POST['mes']) ? $_POST['mes'] : '00';
    $dia = isset($_POST['dia']) ? $_POST['dia'] : '00';
    $mesdia = sprintf('%02d:%02d', $mes, $dia);
    
    $sql = "UPDATE cat_users SET mesdia = ? WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $mesdia, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $mensaje = 'Fecha de cumpleaños actualizada';
        $tipoMensaje = 'success';
        $userData['mesdia'] = $mesdia;
    } else {
        $mensaje = 'Error al actualizar la fecha';
        $tipoMensaje = 'danger';
    }
}

// Determinar mensaje especial
$mensajeEspecial = '';
if (esNavidad($userData['mesdia'] ?? '')) {
    $mensajeEspecial = '🎄¡Feliz Navidad!🎄';
} elseif (esCumpleanos($userData['mesdia'] ?? '')) {
    $mensajeEspecial = '🎂¡Feliz Cumpleaños!🎂';
}

// Obtener imagen de perfil
$imagenPerfil = obtenerImagenPerfil($link, $user_id);

// Parsear mesdia para inputs
$mesdia_actual = $userData['mesdia'] ?? '00:00';
$partes = explode(':', $mesdia_actual);
$mes_actual = $partes[0] ?? '00';
$dia_actual = $partes[1] ?? '00';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Mi Perfil - Sistema</title>
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
            padding-top: 70px;
            padding-bottom: 60px;
            min-height: 100vh;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
        }
        
        .profile-img-container {
            width: 150px;
            height: 150px;
            margin: -75px auto 20px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .profile-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .special-message {
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-upload {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-upload:hover {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        /* Widget Lateral */
        .profile-widget {
            position: fixed;
            left: 0;
            top: 70px;
            width: 280px;
            height: calc(100vh - 70px);
            background: white;
            box-shadow: 5px 0 20px rgba(0, 0, 0, 0.2);
            z-index: 999;
            transition: all 0.3s ease;
            border-radius: 0 15px 15px 0;
            overflow-y: auto;
        }
        
        .profile-widget.collapsed {
            transform: translateX(-280px);
        }
        
        .widget-toggle {
            position: fixed;
            left: 285px;
            top: 80px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            border: none;
            border-radius: 0 50% 50% 0;
            padding: 10px 8px;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .widget-toggle.collapsed {
            left: 0;
        }
        
        .widget-content {
            padding: 20px;
        }
        
        .widget-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            border: 3px solid #3498db;
        }
        
        .widget-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 0.95rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .btn-volver {
            background: transparent;
            border: 2px solid #6c757d;
            color: #6c757d;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            background: #6c757d;
            color: white;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
        }
        
        @media (max-width: 768px) {
            .profile-widget {
                width: 100%;
                left: 0;
                transform: translateX(-100%);
            }
            
            .profile-widget.show {
                transform: translateX(0);
            }
            
            .widget-toggle {
                left: 0;
            }
            
            .widget-toggle.show {
                left: calc(100% - 40px);
            }
        }
    </style>
</head>
<body>

<!-- Widget de Perfil Lateral -->
<button class="widget-toggle" id="widgetToggle" onclick="toggleWidget()">
    <i class="fas fa-chevron-left"></i>
</button>

<div class="profile-widget collapsed" id="profileWidget">
    <div class="widget-content text-center">
        <div class="widget-img">
            <img src="<?php echo htmlspecialchars($imagenPerfil); ?>" alt="Perfil" id="widgetImg">
        </div>
        
        <h5 class="mb-1"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h5>
        <p class="text-muted mb-3">@<?php echo htmlspecialchars($userData['username']); ?></p>
        
        <div class="info-item text-left">
            <div class="info-label">
                <i class="fas fa-user-tag mr-1"></i>Usuario
            </div>
            <div class="info-value"><?php echo htmlspecialchars($userData['username']); ?></div>
        </div>
        
        <div class="info-item text-left">
            <div class="info-label">
                <i class="fas fa-briefcase mr-1"></i>Rol
            </div>
            <div class="info-value"><?php echo htmlspecialchars($userData['Rol'] ?? 'N/A'); ?></div>
        </div>
        
        <div class="info-item text-left">
            <div class="info-label">
                <i class="fas fa-clock mr-1"></i>Último Acceso
            </div>
            <div class="info-value">
                <?php 
                if (!empty($userData['last_login_at'])) {
                    echo date('d/m/Y H:i', strtotime($userData['last_login_at']));
                } else {
                    echo 'Nunca';
                }
                ?>
            </div>
        </div>
        
        <div class="info-item text-left">
            <div class="info-label">
                <i class="fas fa-birthday-cake mr-1"></i>Cumpleaños
            </div>
            <div class="info-value">
                <?php 
                if (!empty($userData['mesdia']) && $userData['mesdia'] !== '00:00') {
                    $partes = explode(':', $userData['mesdia']);
                    $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    echo $partes[1] . ' de ' . $meses[(int)$partes[0]];
                } else {
                    echo 'No configurado';
                }
                ?>
            </div>
        </div>
        
        <hr>
        
        <a href="?module=dashboard" class="btn btn-volver btn-block btn-sm">
            <i class="fas fa-home mr-1"></i>Dashboard
        </a>
        
        <a href="?module=logout" class="btn btn-volver btn-block btn-sm mt-2">
            <i class="fas fa-sign-out-alt mr-1"></i>Salir
        </a>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, #2c3e50, #34495e); z-index: 1001;">
    <div class="container">
        <a class="navbar-brand font-weight-bold" href="?module=dashboard">
            <i class="fas fa-shield-alt mr-2"></i>Sistema
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="?module=perfil">
                        <i class="fas fa-user mr-1"></i>Mi Perfil
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

<div class="main-container" style="margin-left: 0;">
    <div class="container">
        <div class="row justify-content-end">
            <div class="col-lg-8 col-md-10">
                <!-- Mensaje especial (Navidad/Cumpleaños) -->
                <?php if (!empty($mensajeEspecial)): ?>
                <div class="special-message mb-4">
                    <?php echo $mensajeEspecial; ?>
                </div>
                <?php endif; ?>
                
                <!-- Card de Perfil -->
                <div class="profile-card">
                    <div class="profile-header">
                        <h4 class="mb-0">
                            <i class="fas fa-user-circle mr-2"></i>Mi Perfil
                        </h4>
                    </div>
                    
                    <div class="text-center">
                        <div class="profile-img-container">
                            <img src="<?php echo htmlspecialchars($imagenPerfil); ?>" alt="Foto de perfil" id="profileImg">
                        </div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h5>
                        <p class="text-muted">@<?php echo htmlspecialchars($userData['username']); ?></p>
                    </div>
                    
                    <div class="px-4 pb-4">
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
                        
                        <!-- Formulario de Imagen -->
                        <div class="card mb-4" style="border: 1px solid #e0e0e0; border-radius: 10px;">
                            <div class="card-header" style="background: #f8f9fa; border-radius: 10px 10px 0 0;">
                                <h6 class="mb-0">
                                    <i class="fas fa-camera mr-2"></i>Actualizar Foto de Perfil
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="?module=upload" enctype="multipart/form-data" id="uploadForm">
                                    <div class="form-group mb-3">
                                        <label for="profile_upload" class="small text-muted">
                                            <i class="fas fa-info-circle mr-1"></i>Solo archivos PNG, máximo 800KB
                                        </label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="profile_upload" name="profile_upload" accept=".png" required>
                                            <label class="custom-file-label" for="profile_upload">Seleccionar archivo PNG</label>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-upload text-white btn-block" data-toggle="modal" data-target="#confirmModal">
                                        <i class="fas fa-cloud-upload-alt mr-2"></i>Subir Imagen
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Formulario de Cumpleaños -->
                        <div class="card" style="border: 1px solid #e0e0e0; border-radius: 10px;">
                            <div class="card-header" style="background: #f8f9fa; border-radius: 10px 10px 0 0;">
                                <h6 class="mb-0">
                                    <i class="fas fa-birthday-cake mr-2"></i>Fecha de Cumpleaños
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="?module=update_birthday">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label for="mes" class="small font-weight-bold">Mes</label>
                                                <select class="form-control" id="mes" name="mes">
                                                    <option value="00">Seleccionar</option>
                                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                                    <option value="<?php echo $m; ?>" <?php echo ($mes_actual == sprintf('%02d', $m)) ? 'selected' : ''; ?>>
                                                        <?php 
                                                        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                                        echo $meses[$m];
                                                        ?>
                                                    </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label for="dia" class="small font-weight-bold">Día</label>
                                                <select class="form-control" id="dia" name="dia">
                                                    <option value="00">Seleccionar</option>
                                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                                    <option value="<?php echo $d; ?>" <?php echo ($dia_actual == sprintf('%02d', $d)) ? 'selected' : ''; ?>>
                                                        <?php echo sprintf('%02d', $d); ?>
                                                    </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-save text-white btn-block">
                                        <i class="fas fa-save mr-2"></i>Guardar Cumpleaños
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botón volver -->
                <div class="text-center mt-4">
                    <a href="?module=dashboard" class="btn btn-volver">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white;">
                <h5 class="modal-title" id="confirmModalLabel">
                    <i class="fas fa-question-circle mr-2"></i>Confirmar Actualización
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                    ¿Está seguro de actualizar su imagen de perfil?
                </p>
                <p class="text-muted small mt-2">
                    La imagen debe ser formato PNG y no exceder 800KB.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadForm').submit();" style="background: linear-gradient(135deg, #2c3e50, #3498db); border: none;">
                    <i class="fas fa-check mr-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-3" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; position: fixed; bottom: 0; width: 100%; z-index: 1000;">
    <div class="container text-center">
        <small>
            <i class="fas fa-user mr-1"></i>Mi Perfil | 
            <i class="fas fa-shield-alt ml-2 mr-1"></i>
            <?php echo date('Y'); ?> Todos los derechos reservados
        </small>
    </div>
</footer>

<!-- Bootstrap 4.6 JS y jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script para el widget -->
<script>
    // Mostrar nombre del archivo seleccionado
    $('#profile_upload').change(function(e) {
        var fileName = e.target.files[0].name;
        $(this).next('.custom-file-label').html(fileName);
    });
    
    // Toggle widget de perfil
    function toggleWidget() {
        var widget = $('#profileWidget');
        var toggle = $('#widgetToggle');
        
        if (widget.hasClass('collapsed')) {
            widget.removeClass('collapsed');
            toggle.removeClass('collapsed');
            toggle.html('<i class="fas fa-chevron-left"></i>');
        } else {
            widget.addClass('collapsed');
            toggle.addClass('collapsed');
            toggle.html('<i class="fas fa-chevron-right"></i>');
        }
    }
    
    // Mobile toggle
    $(window).resize(function() {
        if ($(window).width() <= 768) {
            $('#profileWidget').addClass('collapsed');
            $('#widgetToggle').addClass('collapsed');
        }
    });
</script>

</body>
</html>
