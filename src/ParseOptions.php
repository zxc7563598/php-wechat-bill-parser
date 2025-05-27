<?php

namespace Hejunjie\WechatBillParser;

class ParseOptions
{
    /** @var callable|null */
    public $onPasswordFound = null;

    /** @var callable|null */
    public $onDataParsed = null;

    public string $zipPath;

    public function __construct(string $zipPath)
    {
        $this->zipPath = $zipPath;
    }
}
