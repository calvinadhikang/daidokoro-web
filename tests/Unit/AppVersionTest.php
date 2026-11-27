<?php

namespace Tests\Unit;

use App\Support\AppVersion;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    private string $versionPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->versionPath = base_path('VERSION');
    }

    public function test_configured_version_wins_over_the_version_file(): void
    {
        config(['app.version' => '1.4.2']);

        $this->withVersionFile('abc1234', function () {
            $this->assertSame('1.4.2', AppVersion::current());
        });
    }

    public function test_version_file_is_used_when_config_is_empty(): void
    {
        config(['app.version' => null]);

        $this->withVersionFile("abc1234\n", function () {
            $this->assertSame('abc1234', AppVersion::current());
        });
    }

    public function test_git_short_hash_is_used_when_nothing_else_is_set(): void
    {
        config(['app.version' => null]);

        $expected = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse --short=7 HEAD'));

        $this->assertNotSame('', $expected);
        $this->assertSame($expected, AppVersion::current());
    }

    private function withVersionFile(string $contents, callable $callback): void
    {
        $existed = is_file($this->versionPath);
        $previous = $existed ? file_get_contents($this->versionPath) : null;

        file_put_contents($this->versionPath, $contents);

        try {
            $callback();
        } finally {
            if ($existed) {
                file_put_contents($this->versionPath, $previous);
            } elseif (is_file($this->versionPath)) {
                unlink($this->versionPath);
            }
        }
    }
}
