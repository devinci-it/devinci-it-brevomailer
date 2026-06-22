### Wiki: `SmtpConfig.php`

**File Path:** `src/Config/SmtpConfig.php`

---

#### **Overview**

The `SmtpConfig` class is an **immutable Value Object** responsible for holding the authentication and connection parameters required by the `BrevoTransport`. By using an object instead of a raw array or global variables, we ensure that your mailer configuration is type-safe and cannot be accidentally modified once initialized.

#### **Class Properties**

All properties are defined as `public readonly`, guaranteeing that they are initialized once upon creation and cannot be changed thereafter.

* `host` (string): The SMTP relay host (e.g., `smtp-relay.brevo.com`).

* `port` (int): The network port for SMTP traffic (e.g., `587`).

* `username` & `password` (string): Credentials for SMTP authentication.

* `defaultFromEmail` & `defaultFromName` (string): The fallback "From" identity used when no override is specified during the `send()` process.

* `encryption` (string): The encryption protocol, defaulting to `tls`.

#### **Factory & Debugging Methods**

* **`static fromArray(array $config)`**:
A factory method that maps a standard configuration array (typically retrieved from your `.env` file) into the `SmtpConfig` object. It handles type casting for the port and provides a default value for the encryption protocol.

* **`isConfigured()`**:
A helper method used for health checking. It returns `true` only if the host, username, and password fields contain values. Use this during application bootstrap to fail early if your configuration is incomplete.
* **`toDebugArray()`**:

A debugging utility that returns a clean representation of the object's state.
> **Security Note:** This method is intentionally designed to **exclude the password** to prevent sensitive credentials from appearing in error logs or debug dumps.



---

#### **Usage Example**

```php
use DevinciIT\BrevoMailer\Config\SmtpConfig;

// Initialize with data
$config = SmtpConfig::fromArray([
    'host'       => 'smtp-relay.brevo.com',
    'port'       => 587,
    'username'   => 'user@example.com',
    'password'   => 'api-key-123',
    'from_email' => 'no-reply@vdetorres.com',
    'from_name'  => 'Vincent De Torres'
]);

// Health check
if (!$config->isConfigured()) {
    throw new \RuntimeException("Mailer configuration is missing required fields.");
}

// Log state for debugging
error_log(json_encode($config->toDebugArray()));

```

---

#### **Developer Notes**

* **Immutability:** Once an `SmtpConfig` object is instantiated, it is "locked." If you need to change the SMTP port, you must create a new instance of the class.

* **Validation:** While `fromArray` handles basic mapping, validation of the specific SMTP host or email format should be performed by the calling application before passing data to the Factory.

