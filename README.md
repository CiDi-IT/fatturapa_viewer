# FatturaPA Viewer for Roundcube

A Roundcube webmail plugin that allows you to seamlessly view and download Italian Electronic Invoices (`.xml`, `.p7m`) and related SdI notifications (Delivery Receipts, Rejection Notices, Delivery Impossibility Notices) directly inside your webmail interface.

## Features
- Automatically detects FatturaPA files and SdI notifications in email attachments.
- Hides the native Roundcube attachments list when SdI documents are present.
- Displays clean, color-coded interactive buttons:
  - 🔵 **Blue**: Electronic Invoices (Fattura Elettronica) with rendered HTML view (via XSLT).
  - 🟢 **Green**: Delivery Receipts (Ricevuta di Consegna).
  - 🟠 **Orange**: Delivery Impossibility Notices (Recapito Impossibile).
  - 🔴 **Red**: Rejection Notices (Notifica di Scarto).
- Direct download buttons with original file preservation.
- Built-in support for `.p7m` digital signature extraction using OpenSSL.

## Requirements
- Roundcube Webmail (>= 1.4)
- PHP >= 8.0 with `openssl` and `xsl` extensions enabled.
- Official `FatturaOrdinaria.xsl` stylesheet placed inside the `stylesheets/` folder.

---

## Installation

### Method 1: Manual / Git Installation
1. Clone this repository into your Roundcube `plugins/` directory:
   ```bash
   cd /path/to/roundcube/plugins/
   git clone https://github.com/CiDi-IT/fatturapa_viewer.git fatturapa_viewer
   ```

2. Make sure the official `FatturaOrdinaria.xsl` file exists in:
   ```text
   plugins/fatturapa_viewer/stylesheets/FatturaOrdinaria.xsl
   ```

### Method 2: Composer Installation
Run the following command in your Roundcube root directory:
```bash
composer require cidi-it/fatturapa_viewer
```

---

## Configuration

Enable the plugin in your Roundcube configuration file (`config/config.inc.php`):

```php
$config['plugins'] = array(
    // ... your other plugins
    'fatturapa_viewer',
);
```

---

## Support & Coffee ☕

If this plugin helps you save time and streamline your email workflow, consider supporting its development with a donation:

[![PayPal](https://img.shields.io/badge/PayPal-00457C?style=for-the-badge&logo=paypal&logoColor=white)](https://paypal.me/DinoCasadei)

---

## License

Licensed under the [GNU General Public License v3.0 or later](LICENSE).