<?php

declare(strict_types=1);

namespace Entrolytics\Exception;

use Exception;

/**
 * Base exception for Entrolytics SDK.
 */
class EntrolyticsException extends Exception
{
    protected ?int $statusCode;

    public function __construct(string $message = '', ?int $statusCode = null, ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}

/**
 * Thrown when API key is invalid or missing.
 */
class AuthenticationException extends EntrolyticsException
{
    public function __construct(string $message = 'Invalid or missing API key')
    {
        parent::__construct($message, 401);
    }
}

/**
 * Thrown when request data is invalid.
 */
class ValidationException extends EntrolyticsException
{
    public function __construct(string $message = 'Invalid request data')
    {
        parent::__construct($message, 400);
    }
}

/**
 * Thrown when rate limit is exceeded.
 */
class RateLimitException extends EntrolyticsException
{
    protected ?int $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', ?int $retryAfter = null)
    {
        parent::__construct($message, 429);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}

/**
 * Thrown when network request fails.
 */
class NetworkException extends EntrolyticsException
{
    public function __construct(string $message = 'Network request failed', ?\Throwable $previous = null)
    {
        parent::__construct($message, null, $previous);
    }
}
