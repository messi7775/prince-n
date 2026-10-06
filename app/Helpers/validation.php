<?php
declare(strict_types=1);

/** Validation helpers — used by controllers before touching models. */
function validate_email(?string $email): bool
{
    return $email !== null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_required(mixed $value): bool
{
    return $value !== null && (is_string($value) ? trim($value) !== '' : true);
}
