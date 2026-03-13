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
 * Configuración de Base de Datos - Proyecto Lemkotir
 * Laboratorio 1-2
 */
/**
 * Revisabom.php - Auditoría de Scripts PHP
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Compatible con PHP 7.x y 8.x
 */

// Configuración de caché y UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Iniciar sesión
session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ?module=login');
    exit;
}

// Configuración de fondo (mismas variables del sistema principal)
$USAR_TAPIZ = false;
$TAPIZ_URL = 'ruta/a/tu/imagen.jpg';

/**
 * Función para detectar BOM UTF-8 en un archivo
 * @param string $ruta_archivo Ruta del archivo a analizar
 * @return bool True si tiene BOM, false si no
 */
function tieneBOM($ruta_archivo) {
    $handle = fopen($ruta_archivo, 'rb');
    if ($handle) {
        $bom = fread($handle, 3);
        fclose($handle);
        return $bom === "\xEF\xBB\xBF";
    }
    return false;
}

/**
 * Función para contar líneas de un archivo
 * @param string $ruta_archivo Ruta del archivo
 * @return int Número de líneas
 */
function contarLineas($ruta_archivo) {
    $lineas = 0;
    $handle = fopen($ruta_archivo, 'r');
    if ($handle) {
        while (!feof($handle)) {
            $linea = fgets($handle);
            $lineas++;
        }
        fclose($handle);
    }
    return $lineas;
}

/**
 * Función para buscar licencia GPL en un archivo
 * @param string $ruta_archivo Ruta del archivo
 * @return bool True si encuentra GPL, false si no
 */
function tieneLicenciaGPL($ruta_archivo) {
    $contenido = file_get_contents($ruta_archivo);
    if ($contenido === false) {
        return false;
    }
    
    // Buscar cadenas comunes de licencia GPL
    $patrones = [
        'GPL',
        'General Public License',
        'GNU General Public License',
        'GPLv3',
        'GPLv2',
        'GNU GPL',
        'LICENSE',
        'GNU Affero General Public License'
    ];
    
    foreach ($patrones as $patron) {
        if (stripos($contenido, $patron) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Función para obtener el tamaño formateado del archivo
 * @param string $ruta_archivo Ruta del archivo
 * @return string Tamaño formateado
 */
function obtenerTamano($ruta_archivo) {
    $bytes = filesize($ruta_archivo);
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

// Escaneo de archivos
$archivos_encontrados = [];
$ruta_actual = __DIR__; // Directorio actual

if (is_dir($ruta_actual)) {
    $files = scandir($ruta_actual);
    
    foreach ($files as $file) {
        // Solo archivos .php
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $ruta_completa = $ruta_actual . DIRECTORY_SEPARATOR . $file;
            
            if (is_file($ruta_completa)) {
                $archivos_encontrados[] = [
                    'nombre' => $file,
                    'ruta' => $ruta_completa,
                    'tamano' => obtenerTamano($ruta_completa),
                    'tiene_bom' => tieneBOM($ruta_completa),
                    'lineas' => contarLineas($ruta_completa),
                    'tiene_gpl' => tieneLicenciaGPL($ruta_completa),
                    'fecha_modificacion' => date('Y-m-d H:i:s', filemtime($ruta_completa))
                ];
            }
        }
    }
}

// Ordenar alfabéticamente
sort($archivos_encontrados);

// Contadores para estadísticas
$total_archivos = count($archivos_encontrados);
$archivos_con_bom = 0;
$archivos_sin_gpl = 0;
$archivos_ok = 0;

foreach ($archivos_encontrados as $archivo) {
    if ($archivo['tiene_bom']) {
        $archivos_con_bom++;
    }
    if (!$archivo['tiene_gpl']) {
        $archivos_sin_gpl++;
    }
    if (!$archivo['tiene_bom'] && $archivo['tiene_gpl']) {
        $archivos_ok++;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Auditoría de Scripts - Revisabom</title>
    <!-- Bootstrap 4.6 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 5.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --gradient-start: #1a2a6c;
            --gradient-mid: #b21f1f;
            --gradient-end: #fdbb2d;
            --metal-blue: #2c3e50;
            --metal-light: #34495e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 50%, #4a69bd 100%);
        }
        
        .main-container {
            padding-top: 80px;
            padding-bottom: 60px;
            min-height: 100vh;
        }
        
        .audit-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .audit-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
        }
        
        .audit-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .table-responsive {
            border-radius: 0 0 10px 10px;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #495057;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge-bom {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-no-bom {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-gpl {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-no-gpl {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-status-ok {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-status-error {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .stat-ok .stat-number { color: #28a745; }
        .stat-bom .stat-number { color: #dc3545; }
        .stat-nogpl .stat-number { color: #ffc107; }
        .stat-total .stat-number { color: #3498db; }
        
        .btn-volver {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            transform: translateY(-2px);
        }
        
        .file-name {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding-top: 70px;
                padding-bottom: 40px;
            }
            
            .stat-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; // Asumiendo que existe el navbar?>

<!-- Navbar simple integrada -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, #2c3e50, #34495e);">
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
        <!-- Título y botón volver -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h3 class="text-white font-weight-bold">
                    <i class="fas fa-search mr-2"></i>Auditoría de Scripts
                </h3>
                <a href="?module=dashboard" class="btn btn-volver">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card stat-total">
                    <div class="stat-number"><?php echo $total_archivos; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-file-code mr-1"></i>Total Archivos
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-ok">
                    <div class="stat-number"><?php echo $archivos_ok; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-check-circle mr-1"></i>Sin BOM + GPL
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-bom">
                    <div class="stat-number"><?php echo $archivos_con_bom; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-bomb mr-1"></i>Con BOM
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-nogpl">
                    <div class="stat-number"><?php echo $archivos_sin_gpl; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Sin GPL
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de resultados -->
        <div class="row">
            <div class="col-12">
                <div class="audit-card">
                    <div class="audit-header d-flex justify-content-between align-items-center">
                        <h4>
                            <i class="fas fa-list mr-2"></i>Resultados del Análisis
                        </h4>
                        <small>
                            <i class="fas fa-folder mr-1"></i>
                            <?php echo htmlspecialchars($ruta_actual); ?>
                        </small>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Archivo</th>
                                    <th class="text-center">Tamaño</th>
                                    <th class="text-center">Líneas</th>
                                    <th class="text-center">UTF-8 BOM</th>
                                    <th class="text-center">Licencia GPL</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($archivos_encontrados)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                                        <p>No se encontraron archivos PHP en el directorio</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($archivos_encontrados as $index => $archivo): ?>
                                    <?php 
                                        $estado_ok = !$archivo['tiene_bom'] && $archivo['tiene_gpl'];
                                        $estado_clase = $estado_ok ? 'ok' : 'error';
                                        $estado_icono = $estado_ok ? 'check-circle' : 'exclamation-circle';
                                    ?>
                                    <tr>
                                        <td class="text-center text-muted"><?php echo $index + 1; ?></td>
                                        <td>
                                            <span class="file-name">
                                                <i class="fab fa-php text-primary mr-2"></i>
                                                <?php echo htmlspecialchars($archivo['nombre']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">
                                                <?php echo htmlspecialchars($archivo['tamano']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php echo number_format($archivo['lineas']); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($archivo['tiene_bom']): ?>
                                            <span class="badge-bom">
                                                <i class="fas fa-radiation mr-1"></i>CON BOM
                                            </span>
                                            <?php else: ?>
                                            <span class="badge-no-bom">
                                                <i class="fas fa-check mr-1"></i>Sin BOM
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($archivo['tiene_gpl']): ?>
                                            <span class="badge-gpl">
                                                <i class="fas fa-scale-balanced mr-1"></i>Sí
                                            </span>
                                            <?php else: ?>
                                            <span class="badge-no-gpl">
                                                <i class="fas fa-times mr-1"></i>No
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge-status-<?php echo $estado_clase; ?>">
                                                <i class="fas fa-<?php echo $estado_icono; ?> mr-1"></i>
                                                <?php echo $estado_ok ? 'OK' : 'Revisar'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Leyenda -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card" style="background: rgba(255,255,255,0.9); border-radius: 10px;">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>Leyenda
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <span class="badge badge-no-bom mr-2">Sin BOM</span>
                                    = Archivo UTF-8 sin marca de orden de bytes
                                </p>
                                <p class="mb-1">
                                    <span class="badge badge-bom mr-2">CON BOM</span>
                                    = Archivo con BOM (puede causar errores de salida)
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <span class="badge badge-gpl mr-2">GPL</span>
                                    = Contiene licencia GPL o similar
                                </p>
                                <p class="mb-1">
                                    <span class="badge badge-no-gpl mr-2">Sin GPL</span>
                                    = No se detectó licencia
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-3" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; position: fixed; bottom: 0; width: 100%; z-index: 1000;">
    <div class="container text-center">
        <small>
            <i class="fas fa-search mr-1"></i> 
            Revisabom - Auditoría de Scripts | 
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
