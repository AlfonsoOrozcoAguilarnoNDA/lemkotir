<?php
/*
    ver200.php - Control de Dominios y Expiraciones
    
    Copyright (C) 2026 Alfonso Orozco Aguilar (vibecodingmexico.com)

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

    ============================================================================
    CRÉDITOS
    ============================================================================
    Lógica     :        Alfonso Orozco Aguilar
    Integración:        Gemini 3 Fast (dos iteraciones por mejoras)
    Fecha:              15 de marzo de 2026
    Experimento:        https://vibecodingmexico.com/vibecoding-control-de-dominios/

    Stack: PHP 8.x Procedural, Bootstrap 4.6.2, Font Awesome 5.15.4
    Asume config.php con $link (mysqli procedural)
    Bonus :  Le pedí a Gemini una lista de los dominios verificando el estatus (es el numero http),que tratara de detectar
    si es wordpress que versión es y que me diera el porcentaje. Es bastante util cuando tienes que ver de un vistazo 78
    dominios, como tengo actualmente propios o de clientes. Cuando la cadena de farmacias de barrio, alla por los 2000,
    tenía que controlar mas de 400 dominios diariamente. El numero 200 indica OK en http. Se puede integrar despues en
    otro módulo pero de momento cumple una función adicional de control. Licencia LGPL porque lo voy a usar en el
    proyecto que estoy haciendo con Minimax. Tarda unos 15 segundos  para 78 dominios.
*/

include("config.php"); 

date_default_timezone_set('America/Mexico_City');
$hora_inicio = date('H:i:s d-m-Y');

function check_site_status($url) {
    $full_url = "http://" . $url;
    $ch = curl_init($full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8); // Timeout optimizado para 78 sitios
    curl_setopt($ch, CURLOPT_USERAGENT, 'LemkotirStatusCheck/1.1');
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $wp_version = "N/A";
    if ($html) {
        if (preg_match('/<meta name="generator" content="WordPress ([0-9.]+)"/i', $html, $matches)) {
            $wp_version = $matches[1];
        } elseif (strpos($html, '/wp-content/') !== false) {
            $wp_version = "WP Detectado";
        }
    }
    return ['code' => $http_code, 'version' => $wp_version];
}

$sql = "SELECT dominio, expiration FROM dominios2020 WHERE showit = 'YES' ORDER BY dominio ASC";
$result = mysqli_query($link, $sql);

// Contadores para el reporte final
$total_dominios = 0;
$errores = 0;
$i = 1; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Salud Lemkotir</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-size: 0.9rem; }
        .timestamp { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 8px 8px 0 0; }
        .footer-stats { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 0 0 8px 8px; }
        .status-200 { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; background-color: #fff5f5; }
        .wp-badge { background: #21759b; color: white; padding: 1px 6px; border-radius: 3px; font-size: 0.75rem; }
    </style>
</head>
<body>

<div class="container mt-4 mb-5">
    <div class="timestamp">
        <div class="row">
            <div class="col-md-6"><h5><i class="fas fa-server"></i> Monitoreo de Red</h5></div>
            <div class="col-md-6 text-right">Iniciado: <?php echo $hora_inicio; ?></div>
        </div>
    </div>

    <div class="table-responsive shadow-sm">
        <table class="table table-hover table-bordered bg-white mb-0">
            <thead class="thead-light">
                <tr>
                    <th width="50">#</th>
                    <th>Dominio</th>
                    <th>Expiración</th>
                    <th class="text-center">HTTP</th>
                    <th class="text-center">WordPress</th>
                    <th class="text-center">Link</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                while($row = mysqli_fetch_assoc($result)): 
                    $total_dominios++;
                    $res = check_site_status($row['dominio']);
                    $is_error = ($res['code'] != 200);
                    if ($is_error) $errores++;
                ?>
                <tr class="<?php echo $is_error ? 'status-error' : ''; ?>">
                    <td class="text-muted"><?php echo $i++; ?></td>
                    <td><strong><?php echo strtoupper($row['dominio']); ?></strong></td>
                    <td><?php echo $row['expiration']; ?></td>
                    <td class="text-center <?php echo !$is_error ? 'status-200' : 'text-danger'; ?>">
                        <?php echo $res['code'] == 0 ? 'FAIL' : $res['code']; ?>
                    </td>
                    <td class="text-center">
                        <?php if($res['version'] != "N/A"): ?>
                            <span class="wp-badge"><i class="fab fa-wordpress"></i> <?php echo $res['version']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="http://<?php echo $row['dominio']; ?>" target="_blank" class="text-primary">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php 
    // Cálculos finales
    $porcentaje_error = ($total_dominios > 0) ? round(($errores / $total_dominios) * 100, 2) : 0;
    $online = $total_dominios - $errores;
    ?>

    <div class="footer-stats">
        <div class="row text-center">
            <div class="col-md-3">
                <small>TOTAL DOMINIOS</small>
                <h3><?php echo $total_dominios; ?></h3>
            </div>
            <div class="col-md-3">
                <small class="text-success">EN LÍNEA</small>
                <h3><?php echo $online; ?></h3>
            </div>
            <div class="col-md-3">
                <small class="text-warning">CAÍDOS / ERROR</small>
                <h3><?php echo $errores; ?></h3>
            </div>
            <div class="col-md-3">
                <small>TASA DE ERROR</small>
                <h3 class="<?php echo $porcentaje_error > 10 ? 'text-danger' : ''; ?>">
                    <?php echo $porcentaje_error; ?>%
                </h3>
            </div>
        </div>
        <hr style="border-color: rgba(255,255,255,0.1);">
        <div class="text-center small">
            Fin de Reporte: <?php echo date('H:i:s d-m-Y'); ?> | <strong>Vibecoding Mexico - Status de Dominios</strong>
        </div>
    </div>
</div>

</body>
</html>
