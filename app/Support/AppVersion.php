<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;

class AppVersion
{
    public static function current(): string
    {
        $configured = config('app.version');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return self::fromVersionFile() ?? self::gitShortHash() ?? 'dev';
    }

    private static function fromVersionFile(): ?string
    {
        $path = base_path('VERSION');

        if (! is_file($path)) {
            return null;
        }

        $contents = trim((string) file_get_contents($path));

        if ($contents === '') {
            return null;
        }

        $line = preg_split("/\r\n|\n|\r/", $contents)[0] ?? '';

        return $line !== '' ? $line : null;
    }

    private static function gitShortHash(): ?string
    {
        $result = Process::path(base_path())->run(['git', 'rev-parse', '--short=7', 'HEAD']);

        if (! $result->successful()) {
            return null;
        }

        $hash = trim($result->output());

        return $hash !== '' ? $hash : null;
    }
}
