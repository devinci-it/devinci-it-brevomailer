<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer\Transports;

use DevinciIT\BrevoMailer\Config\SmtpConfig;
use DevinciIT\BrevoMailer\Contracts\MailerInterface;
use DevinciIT\BrevoMailer\DTO\Attachment;
use DevinciIT\BrevoMailer\DTO\EmailMessage;
use DevinciIT\BrevoMailer\DTO\EmailSender;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class BrevoTransport implements MailerInterface
{
    private PHPMailer $mail;

    public function __construct(private readonly SmtpConfig $config)
    {
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->Host       = $this->config->host;
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $this->config->username;
        $this->mail->Password   = $this->config->password;
        $this->mail->SMTPSecure = $this->config->encryption;
        $this->mail->Port       = $this->config->port;

        $this->mail->isHTML($this->config->defaultIsHtml);
    }

    /**
     * Dispatches the email via PHPMailer.
     */
    public function send(EmailMessage $message, ?EmailSender $overrideSender = null): bool
    {
        try {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
            $this->mail->clearCustomHeaders();

            // Sender Override Logic
            if ($overrideSender !== null) {
                $fromEmail = $overrideSender->email ?? $this->config->defaultFromEmail;
                $fromName  = $overrideSender->name ?? $this->config->defaultFromName;
                $this->mail->setFrom($fromEmail, $fromName);
            } else {
                $this->mail->setFrom($this->config->defaultFromEmail, $this->config->defaultFromName);
            }

            $this->mail->addAddress($message->to);
            $this->mail->Subject = $message->subject;

            // Smart Body Assignment
            $html = $message->htmlBody ?? '';
            $text = $message->textBody ?? '';

            if (!empty($html)) {
                $this->mail->isHTML(true);
                $this->mail->Body = $html;
                $this->mail->AltBody = !empty($text) ? $text : strip_tags($html);
            } else {
                $this->mail->isHTML(false);
                $this->mail->Body = $text;
            }

            // Attachments — supports typed Attachment DTOs (raw bytes) and,
            // for back-compat, plain string file paths.
            foreach ($message->attachments as $attachment) {
                if ($attachment instanceof Attachment) {
                    $this->mail->addStringAttachment(
                        $attachment->content,
                        $attachment->filename,
                        PHPMailer::ENCODING_BASE64,
                        $attachment->contentType,
                    );
                } elseif (is_string($attachment) && file_exists($attachment)) {
                    $this->mail->addAttachment($attachment);
                }
            }

            $this->mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log("PHPMailer Transport Error: {$this->mail->ErrorInfo}");
            throw new \RuntimeException("Mail transport failed: " . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            error_log("BrevoTransport Critical Error: " . $e->getMessage());
            throw new \RuntimeException("Unexpected mailer error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Sends a batch of messages efficiently by keeping the SMTP connection alive.
     *
     * @param EmailMessage[] $messages
     * @return int Number of successfully sent emails.
     */
    public function sendBulk(array $messages): int
    {
        $successCount = 0;

        // Optimize bulk sending by preventing PHPMailer from dropping the connection after each email
        $this->mail->SMTPKeepAlive = true;

        foreach ($messages as $message) {
            if (!$this->validate($message)) {
                continue;
            }

            try {
                if ($this->send($message)) {
                    $successCount++;
                }
            } catch (\Throwable $e) {
                error_log("Bulk send failure for {$message->to}: " . $e->getMessage());
            }
        }

        // Close the connection explicitly once the queue is finished
        $this->mail->smtpClose();

        return $successCount;
    }

    /**
     * Pre-flight validation to ensure the DTO has the minimum required data.
     */
    public function validate(EmailMessage $message): bool
    {
        if (empty($message->to) || !filter_var($message->to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (empty($message->subject)) {
            return false;
        }

        if (empty($message->htmlBody) && empty($message->textBody)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the transport status by attempting a silent connection to the SMTP server.
     */
    public function isHealthy(): bool
    {
        try {
            $isConnected = $this->mail->smtpConnect();
            if ($isConnected) {
                $this->mail->smtpClose();
            }
            return $isConnected;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
