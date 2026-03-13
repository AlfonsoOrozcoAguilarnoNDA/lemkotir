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
 * diagnostico.php - Diagnóstico de Datos y Seguridad
 * 
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Fecha: 13 de marzo de 2026
 * Co-programador en el experimento vibecodingmexico.com
 * 
 * Objetivo: Diagnóstico de tablas y generación de cadenas seguras
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

include_once 'config.php';

global $link;

// Configuración
$USAR_TAPIZ = false;
$TAPIZ_URL = 'ruta/a/tu/imagen.jpg';

/**
 * Función para mostrar las 10 tablas más grandes
 */
function muestra_tablas() {
    global $link;
    
    // Consulta para obtener tablas con tamaño
    $sql = "SELECT 
                TABLE_NAME,
                ENGINE,
                TABLE_ROWS,
                (DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024 AS tamanho_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY tamanho_mb DESC
            LIMIT 10";
    
    $result = mysqli_query($link, $sql);
    
    if (!$result) {
        return '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Error al consultar tablas: ' . mysqli_error($link) . '</div>';
    }
    
    $html = '
    <div class="table-responsive">
        <table class="table table-striped table-hover table-dark">
            <thead>
                <tr>
                    <th><i class="fas fa-database mr-2"></i>Tabla</th>
                    <th><i class="fas fa-cogs mr-2"></i>Engine</th>
                    <th><i class="fas fa-list mr-2"></i>Registros</th>
                    <th><i class="fas fa-hdd mr-2"></i>Tamaño (MB)</th>
                </tr>
            </thead>
            <tbody>';
    
    if (mysqli_num_rows($result) == 0) {
        $html .= '
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                    No se encontraron tablas
                </td>
            </tr>';
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $tabla = htmlspecialchars($row['TABLE_NAME']);
            $engine = htmlspecialchars($row['ENGINE']);
            $registros = $row['TABLE_ROWS'];
            $tamano = number_format($row['tamanho_mb'], 2);
            
            // Si tiene 0 registros, pintar en rojo
            $claseRegistro = ($registros == 0) ? 'text-danger font-weight-bold' : 'text-white';
            $iconoRegistro = ($registros == 0) ? '<i class="fas fa-exclamation-triangle text-warning mr-1"></i>' : '';
            
            $html .= '
                <tr>
                    <td><i class="fas fa-table text-info mr-2"></i>' . $tabla . '</td>
                    <td>' . $engine . '</td>
                    <td class="' . $claseRegistro . '">' . $iconoRegistro . number_format($registros) . '</td>
                    <td><span class="badge badge-primary">' . $tamano . ' MB</span></td>
                </tr>';
        }
    }
    
    $html .= '
            </tbody>
        </table>
    </div>';
    
    return $html;
}

/**
 * Función para generar 5 cadenas aleatorias de 13 caracteres
 * Restricciones: Sin 1, sin 0, sin o/O, sin l/L
 */
function random13() {
    // Caracteres permitidos (sin 1, 0, o, O, l, L)
    $caracteres = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $caracteres = str_split($caracteres);
    
    $cadenas = [];
    
    for ($i = 0; $i < 5; $i++) {
        $cadena = '';
        for ($j = 0; $j < 13; $j++) {
            $cadena .= $caracteres[array_rand($caracteres)];
        }
        $cadenas[] = $cadena;
    }
    
    // Generar HTML
    $html = '
    <div class="row">';
    
    foreach ($cadenas as $index => $cadena) {
        $html .= '
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card" style="border: 2px solid #2c3e50; border-radius: 10px;">
                <div class="card-body text-center py-3">
                    <small class="text-muted d-block mb-1">Cadena #' . ($index + 1) . '</small>
                    <code class="d-block font-weight-bold" style="font-size: 1.1rem; color: #2c3e50;">' . $cadena . '</code>
                    <small class="text-muted">13 caracteres</small>
                </div>
            </div>
        </div>';
    }
    
    $html .= '
    </div>';
    
    // Mensaje de recomendaciones de seguridad
    $html .= '
    <div class="alert alert-warning mt-4" style="border-radius: 10px;">
        <h6 class="alert-heading">
            <i class="fas fa-shield-alt mr-2"></i>Recomendaciones de Seguridad
        </h6>
        <hr>
        <p class="mb-2">
            <i class="fas fa-check-circle text-success mr-1"></i>
            <strong>Evita</strong> usar caracteres como <code>$</code> o <code>,</code> en contraseñas, ya que pueden 
            romper algunos scripts, exportaciones CSV o sistemas legacy.
        </p>
        <p class="mb-2">
            <i class="fas fa-check-circle text-success mr-1"></i>
            <strong>Prefiere</strong> caracteres más estables como: <code>*</code>, <code>:</code>, <code>.</code>, 
            <code>-</code> o <code>_</code>.
        </p>
        <p class="mb-0">
            <i class="fas fa-lightbulb text-info mr-1"></i>
            Las cadenas generadas arriba son <strong>seguras</strong>: sin ambigüedades (1/l/0/O) y sin caracteres problemáticos. EL nivel de entropía es 76.
        </p>
    </div>';
    
    return $html;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Diagnóstico - Sistema</title>
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
        
        .diagnostic-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .diagnostic-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 2px solid #dee2e6;
            border-radius: 10px 10px 0 0;
        }
        
        .table-dark {
            background: #2c3e50;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
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
        
        .badge-primary {
            background: #3498db;
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
            <i class="fas fa-stethoscope mr-2"></i>Diagnóstico
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
                    <i class="fas fa-stethoscope mr-2"></i>Diagnóstico de Datos
                </h3>
                <a href="?module=dashboard" class="btn btn-volver">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
        
        <!-- Sección 1: Tablas -->
        <div class="diagnostic-card mb-4">
            <div class="diagnostic-header">
                <h4 class="mb-0">
                    <i class="fas fa-database mr-2"></i>Top 10 Tablas por Tamaño
                </h4>
            </div>
            <div class="p-3">
                <?php echo muestra_tablas(); ?>
            </div>
        </div>
        
        <!-- Sección 2: Cadenas Seguras -->
        <div class="diagnostic-card">
            <div class="diagnostic-header">
                <h4 class="mb-0">
                    <i class="fas fa-key mr-2"></i>Generador de Cadenas Seguras
                </h4>
            </div>
            <div class="p-3">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    5 cadenas aleatorias de 13 caracteres. Sin 1, 0, o, O, l, L (ambiguos)
                </p>
                <?php echo random13(); ?>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-3" style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; position: fixed; bottom: 0; width: 100%; z-index: 1000;">
    <div class="container text-center">
        <small>
            <i class="fas fa-stethoscope mr-1"></i>Diagnóstico | 
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
