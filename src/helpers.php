<?php

declare(strict_types=1);

use DevinciIT\BrevoMailer\MailerFactory;
use DevinciIT\BrevoMailer\Contracts\MailerInterface;
use DevinciIT\BrevoMailer\DTO\EmailMessage;
use Dotenv\Dotenv;

if (!function_exists('mailer')) {
    /**
     * Globally accessible Mailer instance (Singleton).
     * Uses the fluent builder for configuration.
     */
    function mailer(): MailerInterface
    {
        static $instance = null;

        if ($instance === null) {
            // Standardizing the base path: assume project root is one level up from this file
            $basePath = dirname(__DIR__);

            // Ensure env is loaded before building
            load_env($basePath);

            // Use the fluent factory pattern
            $instance = MailerFactory::create()
                ->loadFromEnv($basePath)
                ->build();
        }

        return $instance;
    }
}

if (!function_exists('load_env')) {
    function load_env(string $basePath): void
    {
        if (class_exists(Dotenv::class)) {
            Dotenv::createImmutable($basePath)->safeLoad();
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => preg_replace('/\A([\'"])(.*)\1\z/', '$2', $value)
        };
    }
}

// Backwards compatibility / Helper aliases
if (!function_exists('mailer_validate')) {
    function mailer_validate(EmailMessage $message): bool
    {
        return mailer()->validate($message);
    }
}

if (!function_exists('mailer_is_healthy')) {
    function mailer_is_healthy(): bool
    {
        return mailer()->isHealthy();
    }
}
