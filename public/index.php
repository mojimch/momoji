<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';

session_start();

require dirname(__DIR__) . '/app/helpers/functions.php';
require dirname(__DIR__) . '/app/core/Database.php';
require dirname(__DIR__) . '/app/core/Model.php';
require dirname(__DIR__) . '/app/core/Controller.php';
require dirname(__DIR__) . '/app/core/App.php';
require dirname(__DIR__) . '/app/helpers/AuthMiddleware.php';
require dirname(__DIR__) . '/app/models/UserModel.php';
require dirname(__DIR__) . '/app/controllers/AuthController.php';

$app = new App();

$app->get('/', [AuthController::class, 'home']);
$app->get('/register', [AuthController::class, 'register']);
$app->post('/register', [AuthController::class, 'registerPost']);
$app->get('/login', [AuthController::class, 'login']);
$app->post('/login', [AuthController::class, 'loginPost']);
$app->get('/admin/login', [AuthController::class, 'adminLogin']);
$app->post('/admin/login', [AuthController::class, 'adminLoginPost']);
$app->post('/logout', [AuthController::class, 'logout']);
$app->get('/forgot-password', [AuthController::class, 'forgotPassword']);
$app->post('/forgot-password', [AuthController::class, 'forgotPasswordPost']);
$app->get('/reset-password/{token}', [AuthController::class, 'resetPassword']);
$app->post('/reset-password/{token}', [AuthController::class, 'resetPasswordPost']);
$app->get('/admin/dashboard', [AuthController::class, 'dashboard']);

$app->run();
