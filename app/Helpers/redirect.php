<?php
declare(strict_types=1);

function redirect(string $path): void
{
    Response::redirect($path);
}
