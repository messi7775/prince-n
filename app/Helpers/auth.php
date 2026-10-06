<?php
declare(strict_types=1);

/** Auth helper — guard for protected views/controllers. */
function require_admin(): void
{
    if (!Session::isAuthenticated()) {
        Response::redirect('/login');
    }
}

function is_admin(): bool
{
    return Session::isAuthenticated();
}
