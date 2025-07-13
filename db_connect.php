<?php
// db_connect.php

$servername = "localhost";
$username = "root"; // Ganti dengan username database Anda
$password = "";     // Ganti dengan password database Anda
$dbname = "db_sampah";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}
?>