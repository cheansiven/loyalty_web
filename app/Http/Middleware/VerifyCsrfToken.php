<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
       "webhook",
        "v1/*",
        "pass",
        "api/v1/push",
        'webhook-delete-card',
        'webhook-create-voucher',
        'webhook-transaction'
    ];
}
