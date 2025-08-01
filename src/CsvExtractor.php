<?php

namespace Hejunjie\WechatBillParser;

use PhpOffice\PhpSpreadsheet\IOFactory;

class CsvExtractor
{

    public function extract(string $zipPath, string $password): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("无法打开ZIP文件: $zipPath");
        }
        if (!$zip->setPassword($password)) {
            $zip->close();
            throw new \RuntimeException("ZIP密码设置失败");
        }
        if ($zip->numFiles < 1) {
            $zip->close();
            throw new \RuntimeException("ZIP中无文件");
        }
        $filename = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/\.xlsx?$/i', $name)) {
                $filename = $name;
                break;
            }
        }
        if (!$filename) {
            $zip->close();
            throw new \RuntimeException("ZIP中没有找到 Excel 文件");
        }
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zip_extract_' . uniqid();
        if (!mkdir($tempDir, 0700) && !is_dir($tempDir)) {
            $zip->close();
            throw new \RuntimeException("创建临时目录失败: $tempDir");
        }
        if (!$zip->extractTo($tempDir, $filename)) {
            $zip->close();
            $this->deleteDir($tempDir);
            throw new \RuntimeException("解压失败");
        }
        $zip->close();
        $excelPath = $tempDir . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($excelPath)) {
            $this->deleteDir($tempDir);
            throw new \RuntimeException("解压文件不存在: $excelPath");
        }
        $spreadsheet = IOFactory::load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $account = '';
        $real_name = '';
        $lineNumber = 0;
        foreach ($sheet->getRowIterator() as $row) {
            $lineNumber++;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = trim((string) $cell->getValue());
            }
            if ($lineNumber < 18) {
                if ($lineNumber === 2) {
                    $account = str_replace(['微信昵称：', '[', ']'], '', $data[0]);
                }
                continue;
            }
            $rows[] = $data;
        }
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
