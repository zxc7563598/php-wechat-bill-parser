<?php

namespace Hejunjie\WechatBillParser;

class WechatBillParser
{
    public function parse(ParseOptions $options): void
    {
        $password = (new ZipPasswordCracker())->crack($options->zipPath);
        if ($options->onPasswordFound) {
            if (!($options->onPasswordFound)($password)) {
                return;
            };
        }
        $data = (new CsvExtractor())->extract($options->zipPath, $password);
        if ($options->onDataParsed) {
            if (!($options->onDataParsed)($data)) {
                return;
            };
        }
    }
}
