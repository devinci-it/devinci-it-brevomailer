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
    /** @var array<string, mixed> */
    private array $fluentConfig = [];

    /*
    |--------------------------------------------------------------------------
    | Legacy Static Methods (Backward Compatibility)
    |--------------------------------------------------------------------------
    */

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
     *
     * @param string $envDirectory The absolute path to the directory containing the .env file.
     * @return MailerInterface
     * @throws RuntimeException
     */
    public static function createFromEnv(string $envDirectory): MailerInterface
    {
        if (!class_exists(Dotenv::class)) {
            throw new RuntimeException('vlucas/phpdotenv is required to use createFromEnv(). Please run: composer require vlucas/phpdotenv');
        }

        $dotenv = Dotenv::createImmutable($envDirectory);
        $dotenv->safeLoad();

        $requiredKeys = [
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME'
        ];

        $missingKeys = [];
        foreach ($requiredKeys as $key) {
            if (empty($_ENV[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            $errorMessage = 'BrevoMailer Critical Error: Missing or empty required .env variables -> ' . implode(', ', $missingKeys);
            error_log($errorMessage);
            throw new RuntimeException($errorMessage);
        }

        $config = [
            'host'       => $_ENV['MAIL_HOST'],
            'port'       => $_ENV['MAIL_PORT'],
            'username'   => $_ENV['MAIL_USERNAME'],
            'password'   => $_ENV['MAIL_PASSWORD'],
            'from_email' => $_ENV['MAIL_FROM_ADDRESS'],
            'from_name'  => $_ENV['MAIL_FROM_NAME'],
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        ];

        return self::createBrevoSmtpFromArray($config);
    }

    /*
    |--------------------------------------------------------------------------
    | Fluent Builder Methods (New API)
    |--------------------------------------------------------------------------
    */

    /**
     * Start the fluent chain.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Loads base configuration from .env for the fluent builder.
     */
    public function loadFromEnv(string $envDirectory): self
    {
        if (!class_exists(Dotenv::class)) {
            throw new RuntimeException('vlucas/phpdotenv is required. Please run: composer require vlucas/phpdotenv');
        }

        $dotenv = Dotenv::createImmutable($envDirectory);
        $dotenv->safeLoad();

        $envMap = [
            'host'       => 'MAIL_HOST',
            'port'       => 'MAIL_PORT',
            'username'   => 'MAIL_USERNAME',
            'password'   => 'MAIL_PASSWORD',
            'from_email' => 'MAIL_FROM_ADDRESS',
            'from_name'  => 'MAIL_FROM_NAME',
            'encryption' => 'MAIL_ENCRYPTION',
            'is_html'    => 'MAIL_HTML_DEFAULT'
        ];

        foreach ($envMap as $configKey => $envKey) {
            if (!empty($_ENV[$envKey])) {
                $this->fluentConfig[$configKey] = $_ENV[$envKey];
            }
        }

        return $this;
    }

    public function host(string $host): self
    {
        $this->fluentConfig['host'] = $host;
        return $this;
    }

    public function port(int|string $port): self
    {
        $this->fluentConfig['port'] = $port;
        return $this;
    }

    public function credentials(string $username, string $password): self
    {
        $this->fluentConfig['username'] = $username;
        $this->fluentConfig['password'] = $password;
        return $this;
    }

    public function defaultFrom(string $email, string $name): self
    {
        $this->fluentConfig['from_email'] = $email;
        $this->fluentConfig['from_name'] = $name;
        return $this;
    }

    public function encryption(string $encryption): self
    {
        $this->fluentConfig['encryption'] = $encryption;
        return $this;
    }

    public function defaultHtml(bool $isHtml): self
    {
        $this->fluentConfig['is_html'] = $isHtml;
        return $this;
    }

    /**
     * Validates the accumulated fluent state and builds the MailerInterface.
     *
     * @throws RuntimeException
     */
    public function build(): MailerInterface
    {
        $requiredKeys = ['host', 'port', 'username', 'password', 'from_email', 'from_name'];
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (empty($this->fluentConfig[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            $errorMessage = 'BrevoMailer Critical Error: Missing fluent configuration variables -> ' . implode(', ', $missingKeys);
            error_log($errorMessage);
            throw new RuntimeException($errorMessage);
        }

        $this->fluentConfig['encryption'] = $this->fluentConfig['encryption'] ?? 'tls';

        // Reuse the static array instantiator to keep logic centralized
        return self::createBrevoSmtpFromArray($this->fluentConfig);
    }
}