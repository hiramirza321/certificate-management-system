<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/auth.php';

function jsonResponse(
    bool $success,
    string $message,
    int $statusCode = 200
): never {
    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message,
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(
        false,
        'Invalid request method.',
        405
    );
}

$templateId = filter_var(
    $_POST['template_id'] ?? null,
    FILTER_VALIDATE_INT
);

$studentNameX = filter_var(
    $_POST['student_name_x'] ?? null,
    FILTER_VALIDATE_INT
);

$studentNameY = filter_var(
    $_POST['student_name_y'] ?? null,
    FILTER_VALIDATE_INT
);

$certificateIdX = filter_var(
    $_POST['certificate_id_x'] ?? null,
    FILTER_VALIDATE_INT
);

$certificateIdY = filter_var(
    $_POST['certificate_id_y'] ?? null,
    FILTER_VALIDATE_INT
);

$verificationLinkX = filter_var(
    $_POST['verification_link_x'] ?? null,
    FILTER_VALIDATE_INT
);

$verificationLinkY = filter_var(
    $_POST['verification_link_y'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $templateId === false ||
    $templateId === null ||
    $templateId < 1
) {
    jsonResponse(
        false,
        'Valid template ID is required.',
        422
    );
}

$coordinates = [
    'Student Name X' => $studentNameX,
    'Student Name Y' => $studentNameY,
    'Certificate ID X' => $certificateIdX,
    'Certificate ID Y' => $certificateIdY,
    'Verification Link X' => $verificationLinkX,
    'Verification Link Y' => $verificationLinkY,
];

foreach ($coordinates as $label => $value) {
    if (
        $value === false ||
        $value === null ||
        $value < 0 ||
        $value > 5000
    ) {
        jsonResponse(
            false,
            $label . ' position is invalid.',
            422
        );
    }
}

try {
    $checkStatement = $pdo->prepare(
        'SELECT id
         FROM certificate_templates
         WHERE id = :id
         LIMIT 1'
    );

    $checkStatement->execute([
        'id' => $templateId,
    ]);

    if (!$checkStatement->fetchColumn()) {
        jsonResponse(
            false,
            'Certificate template not found.',
            404
        );
    }

    $updateStatement = $pdo->prepare(
        'UPDATE certificate_templates
         SET
            student_name_x = :student_name_x,
            student_name_y = :student_name_y,
            certificate_id_x = :certificate_id_x,
            certificate_id_y = :certificate_id_y,
            verification_link_x = :verification_link_x,
            verification_link_y = :verification_link_y
         WHERE id = :id'
    );

    $updateStatement->execute([
        'student_name_x' => $studentNameX,
        'student_name_y' => $studentNameY,
        'certificate_id_x' => $certificateIdX,
        'certificate_id_y' => $certificateIdY,
        'verification_link_x' => $verificationLinkX,
        'verification_link_y' => $verificationLinkY,
        'id' => $templateId,
    ]);

    jsonResponse(
        true,
        'Template positions saved successfully.'
    );
} catch (Throwable $exception) {
    jsonResponse(
        false,
        'Template positions could not be saved.',
        500
    );
}