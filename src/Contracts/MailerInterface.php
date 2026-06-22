<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer\Contracts;

use DevinciIT\BrevoMailer\DTO\EmailMessage;
use DevinciIT\BrevoMailer\DTO\EmailSender;

interface MailerInterface
{
    /**
     * Sends an email message.
     */
    public function send(EmailMessage $message, ?EmailSender $overrideSender = null): bool;

    /**
     * Sends a batch of messages.
     * @param EmailMessage[] $messages
     * @return int Number of successfully sent emails.
     */
    public function sendBulk(array $messages): int;

    /**
     * Pre-flight validation.
     */
    public function validate(EmailMessage $message): bool;

    /**
     * Returns the transport status (Health Check).
     */
    public function isHealthy(): bool;
}