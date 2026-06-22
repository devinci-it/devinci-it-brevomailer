<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer\Transports;

use DevinciIT\BrevoMailer\Contracts\MailerInterface;
use DevinciIT\BrevoMailer\Config\SmtpConfig;
use DevinciIT\BrevoMailer\DTO\EmailMessage;
use DevinciIT\BrevoMailer\DTO\EmailSender;
use DevinciIT\BrevoMailer\Helpers\Sanitizer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RuntimeException;

/**
 * BrevoTransport
 * * Handles secure SMTP communication via PHPMailer, implementing the MailerInterface.
 * Integrates automatic, late-stage payload sanitization to prevent XSS and 
 * Email Header Injection attacks prior to network transmission.
 */
class BrevoTransport implements MailerInterface
{
    /**
     * Initializes the transport with the provided environment configuration.
     */
    public function __construct(private readonly SmtpConfig $config) {}

    /**
     * Main Orchestrator: Single entry point for sending emails.
     *
     * @param EmailMessage $message The email payload.
     * @param EmailSender|null $overrideSender Optional sender override.
     * @return bool True on successful handoff to the SMTP relay.
     * @throws RuntimeException If the SMTP transport fails or rejects the connection.
     */
    public function send(EmailMessage $message, ?EmailSender $overrideSender = null): bool
    {
        $mail = new PHPMailer(true);
        
        try {
            $this->configureServer($mail);
            $this->setSender($mail, $overrideSender);
            $this->setContent($mail, $message);
            $this->addAttachments($mail, $message->attachments);

            return $mail->send();
            
        } catch (Exception $e) {
            throw new RuntimeException("BrevoMailer Transport failed: {$mail->ErrorInfo}");
        }
    }

    /**
     * Bulk sender: Optimized for processing multiple messages sequentially.
     *
     * @param EmailMessage[] $messages Array of email payloads.
     * @return int Number of successfully sent emails.
     */
    public function sendBulk(array $messages): int
    {
        $sentCount = 0;
        foreach ($messages as $message) {
            if ($this->send($message)) {
                $sentCount++;
            }
        }
        return $sentCount;
    }

    /**
     * Pre-flight validation: Ensures the DTO contains essential data before initiating SMTP.
     *
     * @param EmailMessage $message The email payload to check.
     * @return bool True if the message has a valid recipient and subject.
     */
    public function validate(EmailMessage $message): bool
    {
        return !empty($message->to) && 
               !empty($message->subject) && 
               (filter_var($message->to, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Health check: Verifies SMTP connectivity without transmitting a payload.
     * Useful for application status dashboards.
     *
     * @return bool True if the server is reachable and credentials are valid.
     */
    public function isHealthy(): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureServer($mail);
            return (bool)$mail->smtpConnect();
        } catch (\Exception $e) {
            return false;
        }
    }

    // --- Private Helper Methods (Compose Method Pattern) ---

    /**
     * Configures the PHPMailer instance with the SMTP credentials and encryption protocols.
     */
    private function configureServer(PHPMailer $mail): void
    {
        $mail->isSMTP();
        $mail->Host       = $this->config->host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->config->username;
        $mail->Password   = $this->config->password;
        $mail->Port       = $this->config->port;
        $mail->CharSet    = 'UTF-8';
        
        $mail->SMTPSecure = strtolower($this->config->encryption) === 'ssl' 
                            ? PHPMailer::ENCRYPTION_SMTPS 
                            : PHPMailer::ENCRYPTION_STARTTLS;
    }

    /**
     * Applies the sender details, using the override if provided, otherwise falling back to config defaults.
     */
    private function setSender(PHPMailer $mail, ?EmailSender $override): void
    {
        $senderEmail = $override ? $override->email : $this->config->defaultFromEmail;
        $senderName  = $override ? $override->name  : $this->config->defaultFromName;

        // Note: We do not strictly sanitize from emails here as they originate from your controlled .env file
        $mail->setFrom($senderEmail, $senderName);
    }

    /**
     * Applies the recipient, subject, and body content to the mailer.
     * STRICT SANITIZATION is enforced here before data touches the PHPMailer object.
     */
    private function setContent(PHPMailer $mail, EmailMessage $message): void
    {
        // Prevent Header Injection attacks
        $mail->addAddress(Sanitizer::emailHeader($message->to));
        
        // Strip all tags from the subject line
        $mail->Subject = Sanitizer::plainText($message->subject);
        
        if (!empty($message->htmlBody)) {
            $mail->isHTML(true);
            
            // Purify HTML content to prevent XSS
            $mail->Body = Sanitizer::safeHtml($message->htmlBody);
            
            // Sanitize explicit textBody if provided, otherwise strip tags from the purified HTML
            $mail->AltBody = $message->textBody 
                             ? Sanitizer::plainText($message->textBody) 
                             : strip_tags($mail->Body);
        } else {
            $mail->isHTML(false);
            
            // Fallback to strict plain text if no HTML is present
            $mail->Body = $message->textBody ? Sanitizer::plainText($message->textBody) : '';
        }
    }

    /**
     * Iterates through the provided file paths and attaches them if they exist on the disk.
     * Skips missing files to prevent execution halts.
     */
    private function addAttachments(PHPMailer $mail, array $attachments): void
    {
        foreach ($attachments as $path) {
            if (file_exists($path)) {
                $mail->addAttachment($path);
            } else {
                error_log("BrevoMailer Warning: Attachment skipped - File not found at {$path}");
            }
        }
    }
}