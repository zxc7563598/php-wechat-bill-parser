<?php

namespace Hejunjie\WechatBillParser;

class ZipPasswordCracker
{
    public function crack(string $zipPath): string
    {
        if (!file_exists($zipPath)) {
            throw new \RuntimeException("文件不存在");
        }
        $binary = __DIR__ . '/../resources/zip_bruteforce';
        if (!file_exists($binary)) {
            Installer::compileCExecutable();
        }
        if (!file_exists($binary)) {
            throw new \RuntimeException("找不到 zip_bruteforce 可执行文件，也无法自动编译");
        }
        $cmd = escapeshellcmd($binary) . ' ' . escapeshellarg($zipPath);
        exec($cmd, $output, $code);
        $password = '';
        if ($code !== 0 || empty($output[0])) {
            throw new \RuntimeException("密码破解失败，返回码：$code");
        } else {
            $password = trim($output[0]);
        }
        return (string)$password;
    }
}
