<?php

declare(strict_types=1);
use Pest\Concerns\Testable;

if (method_exists(Testable::class, 'preset')) {
    arch('php')->preset()->php();

    arch('security')->preset()->security();
} else {
    arch('it does not use insecure functions')
        ->expect([
            'md5',
            'sha1',
            'uniqid',
            'rand',
            'mt_rand',
            'tempnam',
            'str_shuffle',
            'shuffle',
            'array_rand',
            'eval',
            'exec',
            'shell_exec',
            'system',
            'passthru',
            'create_function',
            'unserialize',
            'extract',
            'mb_parse_str',
            'dl',
            'assert',
        ])
        ->not->toBeUsed();
}

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('InteractSms\InteractSms')
    ->toUseStrictTypes();
