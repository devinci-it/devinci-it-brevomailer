# DevinciIT/BrevoMailer

A modern, modular, and strictly object-oriented SMTP library for PHP 8.1+. Designed to provide a clean, dry, and decoupled interface for sending emails via Brevo (or any SMTP provider) while maintaining environment agnosticism.

## Features
- **Strictly OOP:** Decoupled architecture using Interfaces and Value Objects.
- **Compose Method Pattern:** Small, readable, single-responsibility methods.
- **Environment Agnostic:** No hardcoded `.env` logic within the core service.
- **Sender Overrides:** Easily swap "From" identity on a per-email basis.
- **Global Helper:** Optional global `mailer()` helper for easy access.

## Installation
```bash
composer require devinci-it/brevo-mailer phpmailer/phpmailer vlucas/phpdotenv
```

## Quick Start
```php
use DevinciIT\BrevoMailer\MailerFactory;
use DevinciIT\BrevoMailer\DTO\EmailMessage;

// 1. Initialize
$mailer = MailerFactory::createFromEnv(__DIR__);

// 2. Send
$mailer->send(new EmailMessage(
    to: 'client@example.com',
    subject: 'Hello World',
    htmlBody: '<h1>Test Email</h1>'
));

```