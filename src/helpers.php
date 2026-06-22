<?php

declare(strict_types=1);

use Dotenv\Dotenv;

use DevinciIT\BrevoMailer\MailerFactory;
use DevinciIT\BrevoMailer\Contracts\MailerInterface;
use DevinciIT\BrevoMailer\DTO\EmailMessage;
if (!function_exists('mailer')) {
    /**
     * Globally accessible Mailer instance (Singleton).
     * * @param string|null $envPath Optional: Path to the .env file. Defaults to project root.
     */
    function mailer(?string $envPath = null): MailerInterface
    {
        static $instance = null;

        if ($instance === null) {
            // If no path is provided, default to the folder above 'src' (Project Root)
            $path = $envPath ?? dirname(__DIR__);
            
            // Use the factory method we already built!
            $instance = MailerFactory::createFromEnv($path);
        }

        return $instance;
    }
}

if (!function_exists('load_env')) {
    /**
     * Automatically loads the .env file from a given base path.
     *
     * @param string $basePath The directory containing the .env file.
     * @throws RuntimeException If vlucas/phpdotenv is not installed.
     */
    function load_env(string $basePath): void
    {
        if (!class_exists(Dotenv::class)) {
            throw new \RuntimeException('vlucas/phpdotenv is required. Run: composer require vlucas/phpdotenv');
        }

        // Create immutable instance and safely load (won't crash if file is missing)
        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->safeLoad();
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable with an optional default fallback.
     * Also intelligently parses boolean and null strings.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        // Check $_ENV, then $_SERVER, then getenv()
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        // Handle string representations of booleans and nulls
        switch (strtolower((string) $value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        // Strip surrounding quotes if present (e.g., "my_password")
        if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
            return $matches[2];
        }

        return $value;
    }
}


if (!function_exists('mailer_validate')) {
    /**
     * Global helper to validate a message without manually calling the object
     */
    function mailer_validate(EmailMessage $message): bool 
    {
        return mailer()->validate($message);
    }
}

if (!function_exists('mailer_is_healthy')) {
    /**
     * Global helper to check mailer health
     */
    function mailer_is_healthy(): bool 
    {
        return mailer()->isHealthy();
    }
}