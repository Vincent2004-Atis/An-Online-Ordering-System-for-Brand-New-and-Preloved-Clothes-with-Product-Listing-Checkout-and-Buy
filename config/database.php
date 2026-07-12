<?php
/**
 * Database Gateway — Marguax Collection Ordering System
 * Works on both XAMPP (localhost) and InfinityFree (cloud)
 * For InfinityFree: update the values below with your DB credentials
 */
function getDB(): mysqli {
    $host = 'localhost';                        // InfinityFree: change to your DB host
    $user = 'root';                             // InfinityFree: change to your DB username
    $pass = '';                                 // InfinityFree: change to your DB password
    $name = 'Marguax_Collection';       // InfinityFree: change to your DB name

    $db = new mysqli($host, $user, $pass, $name);
    if ($db->connect_error) {
        http_response_code(500);
        die('Database connection failed. Please check your configuration.');
    }
    $db->set_charset('utf8mb4');
    return $db;
}
