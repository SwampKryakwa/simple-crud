<?php namespace DB;
require_once 'conf.php';
class DBConnection
{
    static \PDO $pdo;
}
DBConnection::$pdo = new \PDO("mysql:host=localhost;dbname=" . DBCredentials::DBNAME, DBCredentials::LOGIN, DBCredentials::PASSWORD);
?>
