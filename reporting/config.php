<?php
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'perpustakaan';

$koneksi_database = new mysqli($hostname, $username, $password, $database);

if ($koneksi_database->connect_error){
    die("koneksi gagal: " . $koneksi_database->connect_error);
}
?>