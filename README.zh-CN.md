# hejunjie/wechat-bill-parser

[English](./README.md) ｜ 简体中文

> [!WARNING]
> 本项目仅供学习交流使用，禁止用于商业或非法用途。

一个自动解析微信账单的 PHP 库：自动破解 ZIP 压缩包密码，提取账单 Excel 数据。适用于个人账单整理、自动记账、理财工具开发等场景。

**想快速了解本项目？** 已由 [Zread](https://zread.ai/zxc7563598/php-wechat-bill-parser) 完成代码解析。

## 特性

- 🔐 **自动破解 ZIP 密码**：基于 C 语言实现的多进程暴力破解，速度快、资源占用低
- 📦 **无需手动解压**：支持带密码的压缩包，自动解压并读取账单数据
- 📄 **智能数据提取**：解析微信账单 Excel 文件，提取账号昵称与交易明细
- 🧩 **回调机制控制流程**：通过 `onPasswordFound` / `onDataParsed` 回调灵活控制解析行为
- 📬 **可配合邮件监听**：结合邮件自动转发，实现账单全自动收集与解析

## 环境要求

- PHP >= 8.1
- [libzip](https://libzip.org/)
- gcc（用于编译密码破解工具）
- Composer

### 安装系统依赖

**Ubuntu / Debian：**

```bash
sudo apt install libzip-dev gcc
```

**macOS（Homebrew）：**

```bash
brew install libzip
```

> [!NOTE]
> macOS 自带 gcc（实际为 clang），无需额外安装。Windows 用户可通过 WSL 使用。

## 安装

```bash
composer require hejunjie/wechat-bill-parser
```

首次运行时，库会自动编译 C 语言密码破解工具，请确保 gcc 和 libzip 已正确安装。

## 快速开始

```php
use Hejunjie\WechatBillParser\WechatBillParser;
use Hejunjie\WechatBillParser\ParseOptions;

$zipFile = '/path/to/微信支付账单.zip';

$options = new ParseOptions($zipFile);

// 拿到密码后的回调（返回 false 可终止后续流程）
$options->onPasswordFound = function ($password) {
    echo "密码：$password\n";
    return true;
};

// 解析完成后的回调（返回 false 可跳过后续处理）
$options->onDataParsed = function ($data) {
    echo "昵称：" . $data['account'] . PHP_EOL;
    echo "共解析 " . count($data['data']) . " 条记录\n";
    return true;
};

$parser = new WechatBillParser();
$parser->parse($options);
```

只需实现你需要的回调即可。比如只关心密码就只设置 `onPasswordFound`，不设置 `onDataParsed`。

## 输出数据结构

`onDataParsed` 回调收到的 `$data` 数组结构如下：

```php
[
    'real_name' => '',              // 姓名（暂未实现提取）
    'account'   => '18273727771',   // 微信昵称
    'data'      => [
        // 每行是一条账单记录
        ['交易时间', '交易类型', '交易对方', '商品', '收/支', '金额(元)', '支付方式', '当前状态', '交易单号', '商户单号', '备注'],
        // ...
    ],
]
```

## 目录结构

```
├── bin/                    # C 源码（zip_bruteforce.c）
├── src/                    # PHP 源码
│   ├── WechatBillParser.php     # 主解析器
│   ├── ParseOptions.php         # 解析配置（回调定义）
│   ├── ZipPasswordCracker.php   # 密码破解（调用 C 可执行文件）
│   ├── CsvExtractor.php         # Excel 数据提取
│   └── Installer.php            # C 工具编译与环境检测
├── resources/              # 编译产物存放目录
└── vendor/                 # Composer 依赖
```

## 常见问题

<details>
<summary>密码破解失败怎么办？</summary>

微信账单 ZIP 密码通常为 6 位数字，破解工具会遍历常见密码组合。如果破解失败，请确认 ZIP 文件未损坏，且确实是微信导出的账单文件。
</details>

<details>
<summary>支持支付宝账单吗？</summary>

支付宝账单解析见独立仓库：[php-alipay-bill-parser](https://github.com/zxc7563598/php-alipay-bill-parser)。
</details>

## 初衷

我习惯做账单整理和个人收支记录，但微信、支付宝导出的账单格式不统一，且经常是加密压缩包，手动导出、解压、整理非常繁琐。于是我写了这个工具：

- 作为个人账单处理的中间件，省去手动下载解压步骤
- 配合邮件监听脚本，将账单邮件自动转发到指定邮箱，即可自动解析所有账单数据

## 联系方式

有问题或建议，欢迎提交 [GitHub Issue](https://github.com/hejunjie/wechat-bill-parser/issues)。
