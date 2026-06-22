<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer\Config;

/**
 * Class SmtpConfig
 * * An immutable Value Object that holds the necessary configuration 
 * for establishing an SMTP connection.
 */
class SmtpConfig
{
    /**
     * @param string $host The SMTP server hostname (e.g., smtp-relay.brevo.com).
     * @param int $port The SMTP port (usually 587 or 465).
     * @param string $username The SMTP authentication username.
     * @param string $password The SMTP authentication password/API key.
     * @param string $defaultFromEmail The email address used if no sender override is provided.
     * @param string $defaultFromName The display name for the default sender.
     * @param string $encryption The encryption type ('tls' or 'ssl'). Defaults to 'tls'.
     */
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $username,
        public readonly string $password,
        public readonly string $defaultFromEmail,
        public readonly string $defaultFromName,
        public readonly string $encryption = 'tls'
    ) {}

    /**
     * Factory method to instantiate SmtpConfig from an associative array.
     * * @param array{
     * host: string,
     * port: int|string,
     * username: string,
     * password: string,
     * from_email: string,
     * from_name: string,
     * encryption?: string
     * } $config
     * @return self
     */
    public static function fromArray(array $config): self
    {
        return new self(
            host:             $config['host'],
            port:             (int) $config['port'],
            username:         $config['username'],
            password:         $config['password'],
            defaultFromEmail: $config['from_email'],
            defaultFromName:  $config['from_name'],
            encryption:       $config['encryption'] ?? 'tls'
        );
    }

    /**
     * Debugging helper to verify if the object contains essential data.
     * * @return bool Returns true if host, username, and password are set.
     */
    public function isConfigured(): bool
    {
        return !empty($this->host) && !empty($this->username) && !empty($this->password);
    }

    /**
     * Returns an array representation of the object state for logging/debugging.
     * Excludes password for security.
     * * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'host'       => $this->host,
            'port'       => $this->port,
            'username'   => $this->username,
            'from_email' => $this->defaultFromEmail,
            'encryption' => $this->encryption,
            'is_valid'   => $this->isConfigured()
        ];
    }
}