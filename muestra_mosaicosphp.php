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
 * muestra_mosaicosphp.php - Mosaicos de Auditoría Dinámicos
 * 
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Fecha: 13 de marzo de 2026
 * Co-programador en el experimento vibecodingmexico.com
 * 
 * Objetivo: Generar rejilla de mosaicos para auditar archivos PHP
 */

// Configuración de caché y UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ?module=login');
    exit;
}

global $link;

// Configuración
$USAR_TAPIZ = false;
$TAPIZ_URL = 'ruta/a/tu/imagen.jpg';
$HORAS_MODIFICACION = 72; // Horas para aviso de modificación reciente

/**
 * Contar líneas de un archivo de forma eficiente
 */
function contarLineasArchivo($ruta) {
    if (!file_exists($ruta)) return 0;
    
    $lineas = 0;
    $handle = fopen($ruta, 'r');
    if ($handle) {
        while (!feof($handle)) {
            fgets($handle);
            $lineas++;
        }
        fclose($handle);
    }
    return $lineas;
}

/**
 * Verificar si el archivo fue modificado en las últimas N horas
 */
function esModificacionReciente($ruta, $horas = 72) {
    if (!file_exists($ruta)) return false;
    
    $tiempoArchivo = filemtime($ruta);
    $limite = time() - ($horas * 3600);
    
    return $tiempoArchivo > $limite;
}

/**
 * Función principal para generar mosaicos
 */
function muestra_mosaicos_php($directorio) {
    global $HORAS_MODIFICACION;
    
    // Array de excepciones (archivos especiales)
    $excepciones = ['index.php', 'config.php'];
    
    // Colores para alternar
    $colores = ['primary', 'secondary', 'success', 'warning', 'danger'];
    $indiceColor = 0;
    
    $html = '';
    $rutaReal = realpath($directorio);
    
    if (!$rutaReal || !is_dir($rutaReal)) {
        return '<div class="alert alert-danger">Directorio no encontrado: ' . htmlspecialchars($directorio) . '</div>';
    }
    
    // Escanear directorio
    $archivos = scandir($rutaReal);
    $archivosPHP = [];
    
    foreach ($archivos as $archivo) {
        $rutaCompleta = $rutaReal . DIRECTORY_SEPARATOR . $archivo;
        
        if (is_file($rutaCompleta) && pathinfo($archivo, PATHINFO_EXTENSION) === 'php') {
            $archivosPHP[] = [
                'nombre' => $archivo,
                'ruta' => $rutaCompleta,
                'esExcepcion' => in_array($archivo, $excepciones),
                'lineas' => contarLineasArchivo($rutaCompleta),
                'reciente' => esModificacionReciente($rutaCompleta, $HORAS_MODIFICACION),
                'fecha' => date('d/m/Y H:i', filemtime($rutaCompleta))
            ];
        }
    }
    
    // Ordenar alfabéticamente
    sort($archivosPHP);
    
    // MOSAICO 1: Directorio actual (blanco, sin enlace)
    $nombreDirectorio = basename($rutaReal);
    $html .= '
    <div class="col-6 col-md-3 col-lg-2 mb-3">
        <div class="mosaico directorio h-100 d-flex flex-column align-items-center justify-content-center p-3 rounded-lg shadow-sm text-center" 
             style="background: white; min-height: 140px;">
            <i class="fas fa-folder fa-2x text-warning mb-2"></i>
            <span class="text-dark font-weight-bold small">' . htmlspecialchars($nombreDirectorio) . '</span>
            <span class="text-muted small">Directorio</span>
        </div>
    </div>';
    
    // MOSAICOS: Archivos PHP
    foreach ($archivosPHP as $archivo) {
        // Determinar color y estilo
        if ($archivo['esExcepcion']) {
            $claseColor = 'bg-dark';
            $icono = 'fa-database';
            $textColor="text-warning";
            $iconocolor= ' style="color: white"';  // puesto amano
            $badge = '';
        } else {
            $color = $colores[$indiceColor % count($colores)];
            $claseColor = 'bg-' . $color;
            $textColor="text-dark";
            $icono = 'fa-file-code';
            $iconocolor= "";   // puesto amano
            $indiceColor++;
            
            // Badge de líneas
            $badge = "<span class='badge badge-light text-dark'>" . number_format($archivo['lineas']) . ' líneas</span>';
        }
        
         // Badge de modificación reciente
        $badgeReciente = '';
        if ($archivo['reciente']) {
            $badgeReciente = '<span class="badge badge-warning text-dark"><i class="fas fa-clock mr-1"></i>Reciente</span>';
        }
        
        
        $html .= "
        <div class='col-6 col-md-3 col-lg-2 mb-3'>
            <a href='". htmlspecialchars($archivo['nombre']) . "' target='_blank' class='mosaico-link text-decoration-none'>
                <div class='mosaico h-100 d-flex flex-column align-items-center justify-content-center p-3 rounded-lg shadow-sm text-center  $claseColor text-white'  style='min-height: 140px; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: pointer;'>
                    <i class='fas " . $icono ." fa-2x mb-2' $iconocolor></i>
                    <span class='font-weight-bold small mb-1' style='word-break: break-all;'>" . htmlspecialchars($archivo['nombre']) . '</span>
                    ' . $badge . '
                    ' . $badgeReciente . '
                    <small class="opacity-75 mt-1">' . $archivo['fecha'] . '</small>
                </div>
            </a>
        </div>';
    }
    
    if (empty($archivosPHP)) {
        $html .= '
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-folder-open mr-2"></i>No se encontraron archivos PHP en este directorio
            </div>
        </div>';
    }
    
    return $html;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Mosaicos PHP - Auditoría</title>
    <!-- Bootstrap 4.6 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 5.0 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
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
        
        .mosaico {
            transition: all 0.3s ease;
        }
        
        .mosaico-link:hover .mosaico {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
        }
        
        .section-title {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        
        .info-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding-top: 70px;
                padding-bottom: 50px;
            }
            
            .mosaico {
                min-height: 120px !important;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, #2c3e50, #34495e);">
    <div class="container">
        <a class="navbar-brand font-weight-bold" href="?module=dashboard">
            <i class="fas fa-th-large mr-2"></i>Mosaicos PHP
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
        <!-- Título y info -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="text-white font-weight-bold">
                    <i class="fas fa-th-large mr-2"></i>Auditoría de Archivos PHP
                </h3>
                <a href="?module=dashboard" class="btn btn-volver">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
        
        <!-- Aviso de modificación reciente -->
        <div class="info-box">
            <h6 class="mb-3">
                <i class="fas fa-info-circle mr-2 text-info"></i>Información de Auditoría
            </h6>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong>Excepciones (fondo negro):</strong> index.php, config.php
                    </p>
                    <p class="mb-1">
                        <strong>Archivos recientes:</strong> Modificados en las últimas <?php echo $HORAS_MODIFICACION; ?> horas
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong>Colores:</strong> primary, secondary, success, warning, danger
                    </p>
                    <p class="mb-0">
                        <strong>Click:</strong> Abre el archivo en nueva ventana
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Sección: Directorio actual "." -->
        <div class="section-title">
            <h5 class="mb-0">
                <i class="fas fa-folder-open mr-2"></i>Directorio Actual
            </h5>
        </div>
        
        <div class="row">
            <?php echo muestra_mosaicos_php('.'); ?>
        </div>
        
        <!-- Sección: Directorio padre ".." -->
        <div class="section-title mt-4">
            <h5 class="mb-0">
                <i class="fas fa-level-up-alt mr-2"></i>Directorio Superior
            </h5>
        </div>
        
        <div class="row">
            <?php echo muestra_mosaicos_php('..'); ?>
        </div>
        
        <!-- Leyenda de colores -->
        <div class="info-box mt-4">
            <h6 class="mb-3">
                <i class="fas fa-palette mr-2"></i>Leyenda de Colores
            </h6>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-primary p-2">Primary</span>
                <span class="badge bg-secondary p-2">Secondary</span>
                <span class="badge bg-success p-2">Success</span>
                <span class="badge bg-warning p-2 text-dark">Warning</span>
                <span class="badge bg-danger p-2">Danger</span>
                <span class="badge bg-dark p-2">Excepción (index.php, config.php)</span>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-3" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; position: fixed; bottom: 0; width: 100%; z-index: 1000;">
    <div class="container text-center">
        <small>
            <i class="fas fa-th-large mr-1"></i>Mosaicos PHP | 
            <i class="fas fa-shield-alt ml-2 mr-1"></i>
            <?php echo date('Y'); ?> Todos los derechos reservados
        </small>
    </div>
</footer>

<!-- Bootstrap 4.6 JS y jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
