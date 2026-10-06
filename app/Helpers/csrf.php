<?php
declare(strict_types=1);

/** CSRF helpers — token generation and hidden field rendering. */
function csrf_token(): string
{
    return (string)(Session::get('_csrf', ''));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
