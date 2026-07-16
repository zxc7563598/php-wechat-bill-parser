# hejunjie/wechat-bill-parser

English ｜ [简体中文](./README.zh-CN.md)

> [!WARNING]
> This project is for learning and communication purposes only. Commercial or illegal use is strictly prohibited.

A PHP library for automated WeChat bill parsing: cracks ZIP archive passwords and extracts bill data from Excel files. Ideal for personal finance tracking, automated bookkeeping, and financial tool development.

**Want a quick overview?** The codebase has been parsed by [Zread](https://zread.ai/zxc7563598/php-wechat-bill-parser).

## Features

- 🔐 **Automatic ZIP Password Cracking**: Multi-process brute-force tool written in C, delivering fast results with minimal resource usage
- 📦 **No Manual Extraction Needed**: Handles password-protected archives — automatically decompresses and reads bill data
- 📄 **Intelligent Data Extraction**: Parses WeChat bill Excel files to extract account nicknames and transaction details
- 🧩 **Callback-Driven Flow Control**: Use `onPasswordFound` / `onDataParsed` callbacks to flexibly control the parsing process
- 📬 **Email Monitoring Ready**: Combine with email auto-forwarding for fully automated bill collection and parsing

## Requirements

- PHP >= 8.1
- [libzip](https://libzip.org/)
- gcc (for compiling the password cracker)
- Composer

### Install System Dependencies

**Ubuntu / Debian:**

```bash
sudo apt install libzip-dev gcc
```

**macOS (Homebrew):**

```bash
brew install libzip
```

> [!NOTE]
> macOS ships with gcc (actually clang), no extra install needed. Windows users can use this library via WSL.

## Installation

```bash
composer require hejunjie/wechat-bill-parser
```

On first run, the library automatically compiles the C-based password cracker. Make sure gcc and libzip are properly installed.

## Quick Start

```php
use Hejunjie\WechatBillParser\WechatBillParser;
use Hejunjie\WechatBillParser\ParseOptions;

$zipFile = '/path/to/wechat_bill.zip';

$options = new ParseOptions($zipFile);

// Called when the password is found (return false to stop further processing)
$options->onPasswordFound = function ($password) {
    echo "Password: $password\n";
    return true;
};

// Called when data parsing is complete (return false to skip further processing)
$options->onDataParsed = function ($data) {
    echo "Nickname: " . $data['account'] . PHP_EOL;
    echo count($data['data']) . " records parsed\n";
    return true;
};

$parser = new WechatBillParser();
$parser->parse($options);
```

Only implement the callbacks you need. For example, if you only care about the password, just set `onPasswordFound` and leave `onDataParsed` unset.

## Output Structure

The `$data` array passed to the `onDataParsed` callback has the following structure:

```php
[
    'real_name' => '',              // Real name (extraction not yet implemented)
    'account'   => '18273727771',   // WeChat nickname
    'data'      => [
        // Each row is a transaction record
        ['Transaction Time', 'Transaction Type', 'Counterparty', 'Product', 'Income/Expense', 'Amount (CNY)', 'Payment Method', 'Current Status', 'Transaction ID', 'Merchant Order ID', 'Remarks'],
        // ...
    ],
]
```

## Project Structure

```
├── bin/                    # C source code (zip_bruteforce.c)
├── src/                    # PHP source code
│   ├── WechatBillParser.php     # Main parser
│   ├── ParseOptions.php         # Parse options (callback definitions)
│   ├── ZipPasswordCracker.php   # Password cracker (invokes the C executable)
│   ├── CsvExtractor.php         # Excel data extraction
│   └── Installer.php            # C tool compilation and environment detection
├── resources/              # Compiled binary output
└── vendor/                 # Composer dependencies
```

## FAQ

<details>
<summary>What if password cracking fails?</summary>

WeChat bill ZIP passwords are typically 6-digit numbers. The cracker iterates through common password patterns. If cracking fails, make sure the ZIP file is not corrupted and is indeed a WeChat-exported bill file.
</details>

<details>
<summary>Does it support Alipay bills?</summary>

Alipay bill parsing is available in a separate repository: [php-alipay-bill-parser](https://github.com/zxc7563598/php-alipay-bill-parser).
</details>

## Motivation

I keep track of my personal finances, but the bill formats exported by WeChat and Alipay are inconsistent and often arrive as encrypted ZIP archives. Manually exporting, extracting, and organizing these bills every time was tedious, so I built this tool to:

- Serve as middleware for personal bill processing, eliminating manual download and extraction steps
- Work with email monitoring scripts — forward bill emails to a designated address and parse everything automatically

## Contact

Questions or suggestions? Feel free to open a [GitHub Issue](https://github.com/hejunjie/wechat-bill-parser/issues).
