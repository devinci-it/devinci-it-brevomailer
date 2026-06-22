
# `devinci-it/brevomailer`

### 1. `SmtpConfig` (Value Object)
* **Purpose:** Immutable container for SMTP connection details.
* **Why:** Decouples your transport layer from raw configuration arrays.
* **Usage:** Created via `SmtpConfig::fromArray()` in the Factory.

### 2. `EmailMessage` (Data Transfer Object)
* **Purpose:** Encapsulates email payload data.
* **Properties:**
    * `to` (string), `subject` (string), `htmlBody` (string), `textBody` (string|null), `attachments` (array).
* **Design Note:** Fields are `readonly` to ensure the integrity of the message state once created.

### 3. `EmailSender` (Value Object)
* **Purpose:** Defines the identity of the sender.
* **Usage:** Optional parameter in `send()` to override the global SMTP identity for specific communications (e.g., "System Alert" vs "Support Team").

### 4. `MailerInterface` (Contract)
* **Purpose:** Ensures any transport class (Brevo, AWS SES, Mailgun) follows the same blueprint.
* **Methods:** `send(EmailMessage $message, ?EmailSender $overrideSender = null): bool`.

### 5. `BrevoTransport` (Core Logic)
* **Purpose:** Implements the mail delivery mechanics using `PHPMailer`.
* **Logic Extraction:** Orchestrates the process via private helper methods:
    * `configureServer()`: Handles SMTP/TLS settings.
    * `setSender()`: Logic for choosing between default or override identity.
    * `setContent()`: Handles HTML/Text body rendering.
    * `addAttachments()`: Validates file existence before attaching.

### 6. `MailerFactory` (Entry Point)
* **Purpose:** The single point of instantiation.
* **Key Methods:**
    * `createBrevoSmtpFromArray(array $config)`: Core constructor.
    * `createFromEnv(string $envDirectory)`: Auto-loads `.env`, validates critical keys, logs errors, and triggers an exception if the environment is incomplete.

### 7. `helpers.php` (Global Utilities)
* **`load_env(string $path)`**: Global convenience for bootstrapping.
* **`env(string $key, $default)`**: Safe retrieval of environment values with type-casting (converts "true" to boolean, "null" to null, etc.).
* **`mailer()`**: Returns the singleton instance of the mailer, ensuring the connection is only opened once per request.
