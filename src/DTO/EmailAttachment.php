<?php

namespace DevinciIT\BrevoMailer\DTO;

final class EmailAttachment
{
    public function __construct(
        public readonly string $filename,
        public readonly string $content,        // raw bytes
        public readonly string $contentType = 'application/octet-stream',
    ) {}

    public static function fromPath(string $path, ?string $filename = null): self
    {
        return new self(
            $filename ?? basename($path),
            (string) file_get_contents($path),
            (function_exists('mime_content_type') ? mime_content_type($path) : '') ?: 'application/octet-stream',
        );
    }
}
