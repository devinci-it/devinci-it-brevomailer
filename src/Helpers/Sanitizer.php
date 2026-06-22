<?php

declare(strict_types=1);

namespace DevinciIT\BrevoMailer\Helpers;

class Sanitizer
{
    /**
     * STAGE 1: Strict Plain Text Sanitization
     * Use this for names, emails, standard text areas, and simple form inputs.
     * This destroys ALL HTML tags and converts special characters to safe HTML entities.
     */
    public static function plainText(string $input): string
    {
        $input = trim($input);
        
        // Strip out all HTML tags completely
        $input = strip_tags($input);
        
        // Convert special characters to HTML entities (e.g., < becomes &lt;, ' becomes &#039;)
        // ENT_QUOTES ensures both single and double quotes are encoded.
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * STAGE 2: Safe HTML Sanitization (Requires HTMLPurifier)
     * Use this ONLY if you have a WYSIWYG editor and actually *want* the user to send bold/italic text.
     */
    public static function safeHtml(string $html): string
    {
        // If you don't have HTMLPurifier installed, fallback to strict text to be safe
        if (!class_exists('\HTMLPurifier')) {
            return self::plainText($html);
        }

        $config = \HTMLPurifier_Config::createDefault();
        
        // Configure allowed tags and attributes
        $config->set('HTML.Allowed', 'p,b,strong,i,em,u,a[href],ul,ol,li,br');
        
        // Force links to open in a new tab (good for emails)
        $config->set('HTML.TargetBlank', true);
        
        // Disable external resources just in case
        $config->set('URI.DisableExternalResources', true);

        $purifier = new \HTMLPurifier($config);
        
        return $purifier->purify($html);
    }

    /**
     * STAGE 3: Email Header Sanitization
     * Crucial for preventing Email Header Injection attacks (CC/BCC spamming).
     */
    public static function emailHeader(string $input): string
    {
        // Remove line breaks (CR and LF) which attackers use to inject new mail headers
        return str_replace(["\r", "\n", "%0a", "%0d"], '', trim($input));
    }
}