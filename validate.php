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
 * Sistema de Login y Dashboard
 * Modelo de IA: Together Chat (MiniMax-M2.5)
 * Compatible con PHP 7.x y 8.x
 * Mas informacion en https://vibecodingmexico.com/laboratorio-2-pantalla-de-login/
 */
// Configuración de caché y UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Configuración de fondo (cambiar a true para usar tapiz)
$USAR_TAPIZ = false; // Cambiar a true si quiere usar imagen de fondo
$TAPIZ_URL = 'ruta/a/tu/imagen.jpg'; // URL de la imagen de fondo
$TAPIZ_URL = 'horses.jpg'; // URL de la imagen de fondo
$TAPIZ_URL = 'cool-gray.jpg'; // URL de la imagen de fondo

// Mensaje informativo (configurable)
$MENSAJE_INFO = "Se les recuerda que hoy la salida a las 14:00 porque van a fumigar";

// Iniciar sesión
session_start();

// Conexión a base de datos (configurar según sea necesario)
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $host = 'localhost';
            $dbname = 'tu_base_datos';
            $username = 'tu_usuario';
            $password = 'tu_password';
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
 $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Obtener módulo
$module = isset($_GET['module']) ? $_GET['module'] : 'login';

// Procesar logout
if ($module === 'logout') {
    session_destroy();
    header('Location: ?module=login');
    exit;
}

// Procesar validación
if ($module === 'validate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $error = '';
    $success = false;
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT id, username, email, password, password_2026, first_name, last_name, Rol, profile_image 
                FROM cat_users 
                WHERE username = :username AND PAPELERA = 'NO'
            ");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user) {               
                // Validar contra password_2026 (nuevo hash)
                /*$uno=$user['password_2026'];
                $dos=password_hash($uno, PASSWORD_DEFAULT);
                die("$password $uno $dos");
                */
  
                /*
                if (!empty($user['password_2026'])) {
                    if (password_verify($password, $user['password_2026'])) {
                        $success = true;
                    }
                }
                // Fallback a password antiguo si password_2026 está vacío
                elseif (!empty($user['password'])) {
                    if (password_verify($password, $user['password']) || $password === $user['password']) {
                        $success = true;
                    }
                }
                */
                // gemini
     
    $success = false;

    // 1. Intentar con el hash moderno (2026)
    if (!empty($user['password_2026'])) {
        if (password_verify($password, $user['password_2026'])) {
            $success = true;
        }
    }

    // 2. Si no ha tenido éxito, intentar con el legacy (solo si success sigue siendo false)
    if (!$success && !empty($user['password'])) {
        // Soporta tanto hash como texto plano (para esa importación que mencionaste)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $success = true;
        }
    }
                
                if ($success) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['Rol'] = $user['Rol'];
                    $_SESSION['profile_image'] = $user['profile_image'];
                    $_SESSION['logged_in'] = true;
                    
                    // Actualizar último login
                    $updateStmt = $pdo->prepare("UPDATE cat_users SET last_login_at = NOW() WHERE id = :id");
                    $updateStmt->execute([':id' => $user['id']]);
                    
                    header('Location: ?module=dashboard');
                    exit;
                } else {
                    $error = 'Contraseña incorrecta';
                }
            } else {
                $error = 'Usuario no encontrado o está en papelera';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema: ' . $e->getMessage();
        }
    }
    
    $module = 'login';
}

// Verificar si está logueado para dashboard
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if ($module === 'dashboard' && !$isLoggedIn) {
    header('Location: ?module=login');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Sistema de Login</title>
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
      /* Ajuste para que el tapiz respete la imagen de fondo */
.login-background {
    min-height: 100vh;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    /* Prioridad al tapiz si está activo, sino el gradiente metálico */
    background: <?php echo ($USAR_TAPIZ && !empty($TAPIZ_URL)) 
        ? "url('$TAPIZ_URL') no-repeat center center fixed" 
        : "linear-gradient(135deg, #1a2a6c 0%, #2c3e50 50%, #4a69bd 100%)"; ?>;
    background-size: cover;
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
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .login-logo i {
            font-size: 45px;
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
        
        .alert-message {
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        /* Dashboard Styles */
        .navbar-custom {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .navbar-custom .nav-link {
            color: white !important;
            transition: all 0.3s ease;
        }
        
        .navbar-custom .nav-link:hover {
            color: #3498db !important;
        }
        
        .footer-custom {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            position: fixed;
            bottom: 0;
            width: 100%;
            z-index: 1000;
        }
        
        .main-content {
            padding-top: 80px;
            padding-bottom: 60px;
            min-height: calc(100vh);
        }
        
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .dropdown-item {
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background: #3498db;
            color: white;
        }
        
        .card-custom {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            .login-card {
                margin: 20px;
                padding: 20px;
            }
            
            .login-logo {
                width: 80px;
                height: 80px;
            }
            
            .login-logo i {
                font-size: 35px;
            }
        }
    </style>
</head>
<body>

<?php if ($module === 'login'): ?>
<!-- PANTALLA DE LOGIN -->
<div class="login-background d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="login-card p-4">
                    <!-- Logo o Icono -->
                    <div class="login-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    
                    <h4 class="text-center text-dark mb-4 font-weight-bold">Sistema de Acceso</h4>
                    
                    <!-- Espacio para mensajes -->
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-message mb-3">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($MENSAJE_INFO)): ?>
                    <div class="alert alert-info alert-message mb-3">
                        <i class="fas fa-info-circle mr-2"></i><?php echo htmlspecialchars($MENSAJE_INFO); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Formulario de Login -->
                    <form method="POST" action="?module=validate">
                        <div class="form-group mb-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                </div>
                                <input type="text" 
                                       class="form-control" 
                                       name="username" 
                                       placeholder="Usuario" 
                                       required
                                       autocomplete="username">
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" 
                                       class="form-control" 
                                       name="password" 
                                       placeholder="Contraseña" 
                                       required
                                       autocomplete="current-password">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login btn-block text-white">
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($module === 'dashboard'): ?>
<!-- PANTALLA DASHBOARD -->
<!-- Navbar Fija -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <a class="navbar-brand text-white font-weight-bold" href="#">
        <i class="fas fa-shield-alt mr-2"></i>Sistema
    </a>
    
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon">
            <i class="fas fa-bars text-white"></i>
        </span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="https://www.google.com" target="_blank">
                    <i class="fas fa-globe mr-1"></i> Google
                </a>
            </li>
            
            <!-- Dropdown con 3 opciones -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                    <i class="fas fa-cog mr-1"></i> Opciones
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-user mr-2"></i>Perfil
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-cogs mr-2"></i>Configuración
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-chart-bar mr-2"></i>Reportes
                    </a>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="?module=logout">
                    <i class="fas fa-sign-out-alt mr-1"></i>Salir
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Contenido Principal -->
<div class="main-content">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="text-white mb-4">
                    <i class="fas fa-tachometer-alt mr-2"></i>Bienvenido, <?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['username']); ?>
                </h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Usuarios</h5>
                        <p class="card-text text-muted">Gestión de usuarios del sistema</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-pie fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Reportes</h5>
                        <p class="card-text text-muted">Ver estadísticas y reportes</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <i class="fas fa-cog fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Configuración</h5>
                        <p class="card-text text-muted">Ajustes del sistema</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información del usuario -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-user-circle mr-2"></i>Información del Usuario
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user mr-2"></i>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                                <p><strong><i class="fas fa-envelope mr-2"></i>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-id-card mr-2"></i>Rol:</strong> <?php echo htmlspecialchars($_SESSION['Rol'] ?? 'N/A'); ?></p>
                                <p><strong><i class="fas fa-clock mr-2"></i>Último acceso:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer Fijo -->
<footer class="footer-custom py-3">
    <div class="container text-center">
        <small>
            <i class="fas fa-copyright mr-1"></i> 
            <?php echo date('Y'); ?> - Sistema de Gestión | 
            <i class="fas fa-shield-alt ml-2 mr-1"></i>Todos los derechos reservados
        </small>
    </div>
</footer>

<?php endif; ?>

<!-- Bootstrap 4.6 JS y jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
