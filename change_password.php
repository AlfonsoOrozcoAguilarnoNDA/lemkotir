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
 * Cambio de Password
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Compatible con PHP 7.x y 8.x
 */

// Configuración de caché y UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: validate.php?module=login');
    exit;
}
include_once 'config.php';
$module = isset($_GET['module']) ? $_GET['module'] : 'changepass';

// Configuración de fondo (mismas variables del sistema principal)
$USAR_TAPIZ = false;
$TAPIZ_URL = 'ruta/a/tu/imagen.jpg';

// Mensajes
$mensaje = '';
$tipoMensaje = ''; // 'success' o 'danger'

// Procesar cambio de password
if ($module === 'changepass' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibir variables POST
    $password_actual = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
    $password_nuevo = isset($_POST['password_nuevo']) ? $_POST['password_nuevo'] : '';
    $password_confirmar = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';
    
    // Validaciones
    if (empty($password_actual) || empty($password_nuevo) || empty($password_confirmar)) {
        $mensaje = 'Todos los campos son requeridos';
        $tipoMensaje = 'danger';
    } elseif (strlen($password_nuevo) < 6) {
        $mensaje = 'El nuevo password debe tener al menos 6 caracteres';
        $tipoMensaje = 'danger';
    } elseif ($password_nuevo !== $password_confirmar) {
        $mensaje = 'Los passwords nuevos no coinciden';
        $tipoMensaje = 'danger';
    } else {
        // Buscar usuario en la base de datos
        $user_id = $_SESSION['user_id'];
        
        global $link; // Usar la conexión global existente
        
        // Consulta segura con prepared statement
        $sql = "SELECT password, password_2026 FROM cat_users WHERE id = ? AND PAPELERA = 'NO'";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $validado = false;
            
            // Validar contra password_2026 primero (nuevo hash)
            if (!empty($row['password_2026'])) {
                if (password_verify($password_actual, $row['password_2026'])) {
                    $validado = true;
                }
            }
            // Fallback a password legacy si password_2026 está vacío
            elseif (!empty($row['password'])) {
                if (password_verify($password_actual, $row['password']) || $password_actual === $row['password']) {
                    $validado = true;
                }
            }
            
            if ($validado) {
                // Generar nuevo hash BCrypt
                $nuevo_hash = password_hash($password_nuevo, PASSWORD_BCRYPT);
                
                // Actualizar: nuevo hash en password_2026, marcar password como null
                $sql_update = "UPDATE cat_users SET password = null, password_2026 = ? WHERE id = ?";
                $stmt_update = mysqli_prepare($link, $sql_update);
                mysqli_stmt_bind_param($stmt_update, 'si', $nuevo_hash, $user_id);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    $mensaje = 'Password cambiado exitosamente';
                    $tipoMensaje = 'success';
                    
                    // Limpiar variables de password
                    $password_actual = '';
                    $password_nuevo = '';
                    $password_confirmar = '';
                } else {
                    $mensaje = 'Error al actualizar el password';
                    $tipoMensaje = 'danger';
                }
            } else {
                $mensaje = 'Password actual incorrecto';
                $tipoMensaje = 'danger';
            }
        } else {
            $mensaje = 'Usuario no encontrado';
            $tipoMensaje = 'danger';
        }
    }
}

// Si viene de logout, destruir sesión
if ($module === 'logout') {
    session_destroy();
    header('Location: validate.php?module=login');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Cambiar Password - Sistema</title>
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
        }
        
        /* Gradiente metálico azul/gris profesional */
        .login-background {
            <?php if ($USAR_TAPIZ && !empty($TAPIZ_URL)): ?>
            background: url('<?php echo $TAPIZ_URL; ?>') no-repeat center center;
            background-size: cover;
            background-position: center;
            position: relative;
            <?php else: ?>
            background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 50%, #4a69bd 100%);
            min-height: 100vh;
            <?php endif; ?>
        }
        
        <?php if ($USAR_TAPIZ): ?>
        .login-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(26, 42, 108, 0.9) 0%, rgba(44, 62, 80, 0.85) 100%);
        }
        <?php endif; ?>
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .login-logo i {
            font-size: 35px;
            color: white;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px 12px 45px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .input-group-text {
            background: transparent;
            border: none;
            color: #6c757d;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
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
        
        .alert-message {
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .login-card {
                margin: 20px;
                padding: 20px;
            }
            
            .login-logo {
                width: 70px;
                height: 70px;
            }
            
            .login-logo i {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>

<div class="login-background d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="login-card p-4">
                    <!-- Logo o Icono -->
                    <div class="login-logo">
                        <i class="fas fa-key"></i>
                    </div>
                    
                    <h4 class="text-center text-dark mb-4 font-weight-bold">Cambiar Password</h4>
                    
                    <p class="text-center text-muted mb-4">
                        <i class="fas fa-user-circle mr-1"></i>
                        Usuario: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    </p>
                    
                    <!-- Espacio para mensajes -->
                    <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipoMensaje; ?> alert-message mb-3">
                        <i class="fas <?php echo ($tipoMensaje === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Formulario de Cambio de Password -->
                    <form method="POST" action="?module=changepass">
                        <div class="form-group mb-3">
                            <label for="password_actual" class="small font-weight-bold text-dark">Password Actual</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_actual"
                                       name="password_actual" 
                                       placeholder="Ingrese su password actual" 
                                       required
                                       autocomplete="current-password">
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="password_nuevo" class="small font-weight-bold text-dark">Nuevo Password</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_nuevo"
                                       name="password_nuevo" 
                                       placeholder="Ingrese nuevo password" 
                                       required
                                       autocomplete="new-password">
                            </div>
                            <p class="password-requirements">
                                <i class="fas fa-info-circle mr-1"></i>Mínimo 6 caracteres
                            </p>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="password_confirmar" class="small font-weight-bold text-dark">Confirmar Nuevo Password</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmar"
                                       name="password_confirmar" 
                                       placeholder="Confirme nuevo password" 
                                       required
                                       autocomplete="new-password">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login btn-block text-white">
                            <i class="fas fa-save mr-2"></i>Cambiar Password
                        </button>
                        
                        <a href="?module=dashboard" class="btn btn-volver btn-block mt-3 text-center">
                            <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 4.6 JS y jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
