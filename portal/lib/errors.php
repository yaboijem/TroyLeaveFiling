<?php
declare(strict_types=1);

/**
 * The single exception type the API turns into a JSON error response.
 */
final class PortalApiException extends RuntimeException
{
    public readonly string $errorCode;
    public readonly int $status;
    public readonly array $details;
    public readonly ?array $session;

    public function __construct(
        string $message,
        string $errorCode,
        int $status = 400,
        array $details = [],
        ?array $session = null
    ) {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->status = $status;
        $this->details = $details;
        $this->session = $session;
    }
}
