<?php

require __DIR__ . '/vendor/autoload.php';

$conn = connect();

clean_data($conn);



function connect()
{

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // Corregir la ruta
    $dotenv->load();
  

    $servername = env('DB_HOST');
    $username = env('DB_USERNAME');
    $password = env('DB_PASSWORD');
    $dbname = env('DB_DATABASE');

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}



function clean_data($conn)
{

    $sql = "UPDATE tbl_dv_insp
    SET ACTIVADO = 0
    WHERE ACTIVADO = 1 AND GESTIONADO = 1;";

    if ($conn->query($sql) === TRUE) {
        echo "Record updated successfully";
    } else {
        echo "Error uptating record: " . $conn->error;
    }
    $conn->close();
}
