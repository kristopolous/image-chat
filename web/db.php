<?php
session_start();

$_pdo = false; 

function get_pdo() {
  global $_pdo;
  if(!$_pdo) {
    $env = parse_ini_file('.env');
    $db_host = '127.0.0.1';
    $db   = 'imgchat';
    $user = 'imgchat';
    $pass = $env['MYSQL_PASSWORD'];
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$db_host;port=3306;dbname=$db;charset=utf8";//$charset";
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" 
    ];
    try {
      $_pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
      throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
  }
  return $_pdo;
}

