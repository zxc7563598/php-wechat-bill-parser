<?php

declare(strict_types=1);

namespace Hejunjie\WechatBillParser;

class ParseOptions
{
    public ?\Closure $onPasswordFound = null;

    public ?\Closure $onDataParsed = null;

    public function __construct(
        public string $zipPath
    ) {}
}
