<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer\DTO;
use DevinciIT\BrevoMailer\Helpers\Sanitizer;

class EmailMessage
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $htmlBody,
        public readonly ?string $textBody = null,
        public readonly array $attachments = []
    ) {}
}