# Webkernel QR Code

`webkernel/qrcode` encodes QR codes and produces SVG or SVG data URIs.

```php
use Webkernel\QrCode\Generator;

$svg = (new Generator())->svg('https://example.com', size: 200);
$data_uri = (new Generator())->dataUri('otpauth://totp/Example?...');
```

The encoder is adapted from php-qrcode 5.0.5. Required MIT and Apache 2.0 notices are preserved. Image scanning and alternative image output backends are not included.
