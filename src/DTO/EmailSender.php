<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer\DTO;

class EmailSender
{
    public function __construct(
        public readonly string $email,
        public readonly string $name
    ) {}
}