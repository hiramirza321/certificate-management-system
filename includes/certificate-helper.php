<?php

declare(strict_types=1);

function generateCertificateId(PDO $pdo): string
{
    $year = date('Y');

    $statement = $pdo->prepare(
        "SELECT certificate_id
         FROM certificates
         WHERE certificate_id LIKE ?
         ORDER BY id DESC
         LIMIT 1"
    );

    $statement->execute([
        "IBRAS-$year-%"
    ]);

    $lastId = $statement->fetchColumn();

    if ($lastId) {

        $parts = explode('-', $lastId);

        $number = (int) end($parts);

        $number++;

    } else {

        $number = 1;
    }

    return sprintf(
        "IBRAS-%s-%06d",
        $year,
        $number
    );
}