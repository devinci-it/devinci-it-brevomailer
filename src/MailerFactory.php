<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer;

use DevinciIT\BrevoMailer\Config\SmtpConfig;
use DevinciIT\BrevoMailer\Transports\BrevoTransport;
use DevinciIT\BrevoMailer\Contracts\MailerInterface;
use Dotenv\Dotenv;
use RuntimeException;

class MailerFactory
{
    /**
     * Instantiates the mailer using an array.
     *
     * @param array{
     * host: string,
     * port: int|string,
     * username: string,
     * password: string,
     * from_email: string,
     * from_name: string,
     * encryption?: string
     * } $config
     */
    public static function createBrevoSmtpFromArray(array $config): MailerInterface
    {
        $smtpConfig = SmtpConfig::fromArray($config);
        return new BrevoTransport($smtpConfig);
    }

    /**
     * Automatically loads configuration from a .env file using vlucas/phpdotenv.
     * Logs an error to the server and throws an exception if critical values are missing.
     *
     * @param string $envDirectory The absolute path to the directory containing the .env file.
     * @return MailerInterface
     * @throws RuntimeException
     */
    public static function createFromEnv(string $envDirectory): MailerInterface
    {
        // Ensure the Dotenv library is installed before proceeding
        if (!class_exists(Dotenv::class)) {
            throw new RuntimeException('vlucas/phpdotenv is required to use createFromEnv(). Please run: composer require vlucas/phpdotenv');
        }

        // Load the .env file safely (won't crash immediately if the file is entirely missing)
        $dotenv = Dotenv::createImmutable($envDirectory);
        $dotenv->safeLoad();

        // Define the critical keys required for the mailer to function
        $requiredKeys = [
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME'
        ];

        // Validate presence of critical values
        $missingKeys = [];
        foreach ($requiredKeys as $key) {
            // Using empty() handles both missing keys and keys that are defined but blank
            if (empty($_ENV[$key])) {
                $missingKeys[] = $key;
            }
        }

        // If any critical values are missing, log the error and halt execution
        if (!empty($missingKeys)) {
            $errorMessage = 'BrevoMailer Critical Error: Missing or empty required .env variables -> ' . implode(', ', $missingKeys);
            
            // Log to standard PHP error log (e.g., Apache/Nginx error.log or a custom php_errors.log)
            error_log($errorMessage);
            
            throw new RuntimeException($errorMessage);
        }

        // Construct the config array and pass it to our core factory method
        $config = [
            'host'       => $_ENV['MAIL_HOST'],
            'port'       => $_ENV['MAIL_PORT'],
            'username'   => $_ENV['MAIL_USERNAME'],
            'password'   => $_ENV['MAIL_PASSWORD'],
            'from_email' => $_ENV['MAIL_FROM_ADDRESS'],
            'from_name'  => $_ENV['MAIL_FROM_NAME'],
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls', // Optional fallback
        ];

        return self::createBrevoSmtpFromArray($config);
    }
}