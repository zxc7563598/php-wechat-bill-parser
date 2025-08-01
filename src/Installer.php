<?php

namespace Hejunjie\WechatBillParser;

/**
 * 破解账单
 * @package Hejunjie\WechatBillParser
 */
class Installer
{
    public static function compileCExecutable(): void
    {
        self::checkLibzip();
        $source = __DIR__ . '/../bin/zip_bruteforce.c';
        $output = __DIR__ . '/../resources/zip_bruteforce';
        if (!file_exists($output)) {
            $cmd = "gcc " . escapeshellarg($source) . " -o " . escapeshellarg($output) . " $(pkg-config --cflags --libs libzip) -L/usr/local/lib -lpthread 2>&1";
            exec($cmd, $out, $code);
        }
    }

    public static function checkLibzip(): void
    {
        $os = PHP_OS_FAMILY;
        $hasLibzip = false;
        $errorHint = '';
        switch ($os) {
            case 'Darwin': // macOS
                exec('pkg-config --exists libzip', $output, $code);
                $hasLibzip = $code === 0;
                if (!$hasLibzip) {
                    $errorHint = "macOS: 请先安装 libzip，使用命令：brew install libzip";
                }
                break;
            case 'Linux':
                exec('ldconfig -p | grep libzip', $output1, $code1);
                exec('pkg-config --exists libzip', $output2, $code2);
                $hasLibzip = $code1 === 0 || $code2 === 0;
                if (!$hasLibzip) {
                    $errorHint = "Linux: 请先安装 libzip，使用命令：sudo apt install libzip-dev";
                }
                break;
            case 'Windows':
                // 尝试查看是否有 libzip.dll（或通过 where 查找编译器环境）
                exec('where libzip.dll', $output, $code);
                $hasLibzip = $code === 0 && !empty($output);
                if (!$hasLibzip) {
                    $errorHint = "Windows: 需要预先安装 libzip，并配置到 PATH。建议使用 vcpkg 安装：\n" .
                        "  vcpkg install libzip\n" .
                        "  并设置编译环境变量（如 CL.exe 的路径、INCLUDE、LIB）";
                }
                break;
            default:
                $errorHint = "未知系统：当前无法检测 libzip 是否已安装，请手动确认是否具备 libzip 开发环境。";
        }
        if (!$hasLibzip) {
            throw new \RuntimeException($errorHint);
        }
    }
}
