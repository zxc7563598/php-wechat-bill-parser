# hejunjie/wechat-bill-parser

<div align="center">
  <a href="./README.md">English</a>｜<a href="./README.zh-CN.md">简体中文</a>
  <hr width="50%"/>
</div>

⚠️ This project is for learning and communication purposes only. Commercial or illegal use is strictly prohibited.

A high-performance, automated Wechat bill parser that supports automatic password cracking of compressed files and intelligent extraction of bill data. Ideal for scenarios such as bill analysis, automated bookkeeping, and personal finance tool development.

---

## ✨ Features

- 🔐 **Automatic Password Cracking for Compressed Files**: Utilizes a native C-based multithreaded brute-force tool for extremely fast response and minimal resource usage.

- 📦 **No Manual Extraction Required**: Supports password-protected archives, automatically decompresses and reads bill data without manual intervention.

- 📄 **Intelligent Data Extraction**: Parses Wechat bill CSV files to quickly extract account information, user names, and transaction details.

- 🧩 **Highly Customizable**: Offers flexible control over the parsing process via callback functions—for example, to retrieve only the password without generating HTML.

- 📬 **Compatible with Email Monitoring Scripts**: Can be integrated with email listeners to enable fully automated bill collection and parsing.

---

## 🛠 System Requirements

This library depends on the C library [libzip](https://libzip.org/). Please install the dependency first:

- Ubuntu / Debian：

  ```bash
  sudo apt install libzip-dev
  ```

- macOS (using Homebrew)：

  ```bash
  brew install libzip
  ```

- Windows users can use this via WSL, or use the precompiled `zip_bruteforce.exe`.

---

## 📦 Installation

Install this library via Composer:

```bash
composer require hejunjie/wechat-bill-parser
```

---

## 🚀 Usage

```php
use Hejunjie\WechatBillParser\WechatBillParser;
use Hejunjie\WechatBillParser\ParseOptions;

$zipFile = '/path/to/微信支付账单.zip';

$options = new ParseOptions($zipFile);
$options->onPasswordFound = function ($password) {
    echo "password:$password\n";
    return true; // Returning false will terminate the subsequent parsing process.
};
$options->onDataParsed = function ($data) {
    echo "name " . $data['real_name'] . PHP_EOL;
    echo "account " . $data['account'] . PHP_EOL;
    echo "A total of " . count($data['data']) . " records have been parsed.\n";
    return true; // Returning false will skip the HTML generation step (under development).
};

// tips: Future versions may support directly generating a bill report as an HTML file.

$parser = new WechatBillParser();
$parser->parse($options);
```

You can also choose to retrieve only the password or only the bill data—simply implement the corresponding callback functions as needed.

---

## 🧠 Purpose & Motivation

I usually keep track of my bills and personal income and expenses, but the bill formats exported from WeChat and Alipay are inconsistent and often come as encrypted compressed files. Exporting, extracting, and organizing these bills every time is extremely tedious. So, I developed this tool:

- Acts as middleware for personal bill processing;

- Eliminates the need for manual downloading and extraction by automatically cracking compressed files and extracting data;

- Can be combined with email monitoring scripts to enable automated transaction collection;

- Simply forward all bill emails to a designated mailbox, and you can parse all bill data with one click—freeing your hands completely.

---

## 🧾 Output Structure Description

The `$data` passed into the `onDataParsed` callback is an array with the following structure:

```php
[
  'real_name' => '张三', // name
  'account' => '18273727771', // WeChat Nickname
  'data' => [
      // Each line of bill record
      ["Transaction Time", "Transaction Type", "Counterparty", "Product", "Income/Expense", "Amount (CNY)", "Payment Method", "Current Status", "Transaction ID", "Merchant Order ID", "Remarks"]
      ...
  ]
]
```

---

## 📮 Contact

If you have any questions, suggestions, or cooperation interests, feel free to reach out to me via GitHub Issues.
