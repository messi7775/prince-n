<?php
declare(strict_types=1);

function money(mixed $amount): string
{
    return number_format((int)($amount ?? 0), 0, '.', ',') . ' ر.ي';
}

function int_num(mixed $value): string
{
    return number_format((int)($value ?? 0), 0, '.', ',');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function ar_date(?string $datetime): string
{
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if ($ts === false) return $datetime;
    return date('Y/m/d', $ts);
}
