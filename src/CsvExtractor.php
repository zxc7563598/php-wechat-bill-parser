<?php

namespace Hejunjie\WechatBillParser;

class CsvExtractor
{

    public function extract(string $zipPath, string $password): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("无法打开ZIP文件: $zipPath");
        }
        // 设置密码
        if (!$zip->setPassword($password)) {
            $zip->close();
            throw new \RuntimeException("ZIP密码设置失败");
        }
        // 获取第一个文件名（假设只有一个CSV文件）
        if ($zip->numFiles < 1) {
            $zip->close();
            throw new \RuntimeException("ZIP中无文件");
        }
        $filename = $zip->getNameIndex(1);
        // 临时目录
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zip_extract_' . uniqid();
        if (!mkdir($tempDir, 0700) && !is_dir($tempDir)) {
            $zip->close();
            throw new \RuntimeException("创建临时目录失败: $tempDir");
        }
        // 解压第一个文件到临时目录
        if (!$zip->extractTo($tempDir, $filename)) {
            $zip->close();
            $this->deleteDir($tempDir);
            throw new \RuntimeException("解压失败");
        }
        $zip->close();
        $csvPath = $tempDir . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($csvPath)) {
            $this->deleteDir($tempDir);
            throw new \RuntimeException("解压文件不存在: $csvPath");
        }
        // 读取CSV内容到数组
        $rows = [];
        $real_name = '';
        $account = '';
        if (($handle = fopen($csvPath, 'r')) !== false) {
            $lineNumber = 0;
            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if ($lineNumber < 18) {
                    switch ($lineNumber) {
                        case 2:
                            $account = $data[0];
                            $account = str_replace('微信昵称：', '', $account);
                            $account = str_replace('[', '', $account);
                            $account = str_replace(']', '', $account);
                            break;
                    }
                    continue;
                }
                $rows[] = $data;
            }
            fclose($handle);
        } else {
            $this->deleteDir($tempDir);
            throw new \RuntimeException("打开CSV文件失败");
        }
        // 删除临时文件和目录
        $this->deleteDir($tempDir);
        return [
            'real_name' => $real_name,
            'account' => $account,
            'data' => $rows
        ];
    }

    public function deleteDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }
}
