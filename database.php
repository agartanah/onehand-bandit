<?php

$dbPassword = getenv("DB_PASSWORD");

if ($dbPassword === false) {
    throw new RuntimeException("DB_PASSWORD environment variable is not set");
}

$pdo = new PDO(
    sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        getenv("DB_HOST") ?: "localhost",
        getenv("DB_PORT") ?: "5432",
        getenv("DB_NAME") ?: "onehand_bandit"
    ),
    getenv("DB_USER") ?: "postgres",
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);