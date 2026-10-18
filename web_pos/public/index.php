<?php
session_start();

define('APP_PATH', dirname(__DIR__) . '/app');

require_once "../config/config.php";
require_once "../app/Core/Database.php";
require_once "../app/Core/Controller.php";
require_once "../app/Core/App.php";

$app = new App();