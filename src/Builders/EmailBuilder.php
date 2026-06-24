<?php

namespace DevinciIT\BrevoMailer\Builders;

use DevinciIT\BrevoMailer\DTO\EmailMessage;

class EmailBuilder
{
    private string $to = '';
    private string $subject = '';
    private string $htmlBody = '';
    private array $attachments = [];

    public function to(string $email): self
    {
        $this->to = $email;
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function view(string $path, array $data = []): self
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Template not found: {$path}");
        }
        ob_start();
        extract($data);
        include $path;
        $this->htmlBody = (string) ob_get_clean();
        return $this;
    }

    public function attach(string $filePath): self
    {
        $this->attachments[] = $filePath;
        return $this;
    }

    public function build(): EmailMessage
    {
        return new EmailMessage(
            to: $this->to,
            subject: $this->subject,
            htmlBody: $this->htmlBody,
            textBody: strip_tags($this->htmlBody),
            attachments: $this->attachments
        );
    }

    public function preview(): void
    {
        // 1. Ensure we have content
        if (empty($this->htmlBody)) {
            throw new \RuntimeException("Cannot preview: HTML body is empty.");
        }

        // 2. Output and exit
        header('Content-Type: text/html; charset=utf-8');
        echo $this->htmlBody;
        exit;
    }
}
