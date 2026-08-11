<?php
/**
 * Database Gateway — Margaux Collections Ordering System
 *
 * Works on both:
 *   - XAMPP (localhost)   → uses the hardcoded defaults below
 *   - Railway (cloud)     → automatically uses Railway's MySQL
 *                            environment variables when present
 *
 * You don't need to edit anything here for Railway — once you add a
 * MySQL database in your Railway project, Railway injects MYSQLHOST,
 * MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, and MYSQLPORT automatically,
 * and this file picks them up on its own.
 */
function getDB(): mysqli {
    $host = getenv('MYSQLHOST')     ?: 'localhost';
    $user = getenv('MYSQLUSER')     ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
    $name = getenv('MYSQLDATABASE') ?: 'Margaux_Collection';
    $port = getenv('MYSQLPORT')     ?: 3306;

    $db = new mysqli($host, $user, $pass, $name, (int)$port);
    if ($db->connect_error) {
        http_response_code(500);
        die('Database connection failed. Please check your configuration.');
    }
    $db->set_charset('utf8mb4');
    return $db;
}