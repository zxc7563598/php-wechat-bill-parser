# hejunjie/wechat-bill-parser

<div align="center">
  <a href="./README.md">English</a>｜<a href="./README.zh-CN.md">简体中文</a>
  <hr width="50%"/>
</div>

一个高性能、自动化的微信账单解析器，支持**压缩包密码自动破解**与**账单数据智能提取**，适用于账单分析、账单自动化入账、个人理财工具开发等场景。

---

## ✨ 特点

* 🔐 **自动破解压缩包密码**：使用原生 C 语言实现的多线程暴力破解工具，响应速度极快，资源占用极低。
* 📦 **无需手动解压**：支持带密码压缩包，自动解压并读取账单数据，无需人工干预。
* 📄 **智能数据提取**：解析微信账单 CSV 文件，快速提取账号信息、姓名与明细数据。
* 🧩 **高度可定制**：支持通过回调函数灵活控制解析流程，比如只获取密码、不生成 HTML。
* 📬 **适配邮件监听脚本**：结合邮件监听可实现完全自动化的账单收集与解析。

---

## 🛠 系统依赖

本库依赖 C 语言库 [libzip](https://libzip.org/)，请先安装依赖：

* Ubuntu / Debian：

  ```bash
  sudo apt install libzip-dev
  ```

* macOS（使用 Homebrew）：

  ```bash
  brew install libzip
  ```
* Windows 用户可通过 WSL 使用，或使用预编译的 `zip_bruteforce.exe`​。

---

## 📦 安装方式

使用 Composer 安装本库：

```bash
composer require hejunjie/wechat-bill-parser
```

---

## 🚀 使用方式

```php
use Hejunjie\WechatBillParser\WechatBillParser;
use Hejunjie\WechatBillParser\ParseOptions;

$zipFile = '/path/to/微信支付账单.zip';

$options = new ParseOptions($zipFile);
$options->onPasswordFound = function ($password) {
    echo "密码是：$password\n";
    return true; // 返回 false 会终止后续解析流程
};
$options->onDataParsed = function ($data) {
    echo "姓名 " . $data['real_name'] . PHP_EOL;
    echo "账号 " . $data['account'] . PHP_EOL;
    echo "共解析出 " . count($data['data']) . " 行记录\n";
    return true; // 返回 false 会跳过 HTML 生成步骤（开发中）
};

// tips: 后期考虑支持直接生成账单报告html文件

$parser = new WechatBillParser();
$parser->parse($options);
```

你也可以只获取密码或只获取账单数据，只需根据需要实现相应回调函数。

---

## 🧠 用途 & 初衷

平时我有做账单整理和个人收支记录的习惯，但微信、支付宝导出的账单格式不统一，且常常是加密压缩包，每次导出、解压、整理都极其繁琐。于是我开发了这个工具：

* 可作为**个人账单处理的中间件**；
* 省去了下载、解压的过程，自动破解压缩包数据提取信息
* 可结合**邮件监听脚本**，实现自动化流水收集；
* 只需将所有账单邮件自动转发至指定邮箱，就能**一键解析所有账单数据**，解放双手。

---

## 🧾 输出结构说明

​`onDataParsed`​ 回调中传入的 `$data`​ 是如下结构的数组：

```php
[
  'real_name' => '张三', // 姓名
  'account' => '18273727771', // 微信昵称
  'data' => [
      // 每一行账单记录	
      ['交易时间', '交易类型', '交易对方', '商品', '收/支', '金额(元)', '支付方式', '当前状态', '交易单号', '商户单号', '备注'],
      ...
  ]
]
```

---

## 📮 联系方式

如有问题、建议或合作意向，欢迎通过 GitHub Issue 与我联系。
