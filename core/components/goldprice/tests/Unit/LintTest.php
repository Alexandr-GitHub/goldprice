<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Build scripts, resolvers and lexicons are never autoloaded by the test suite,
 * so a syntax error in them would otherwise surface only on the server.
 */
final class LintTest extends TestCase
{
    public function testEveryComponentFileParses(): void
    {
        $root = dirname(__DIR__, 2);
        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            ),
            '#\.php$#'
        );

        $checked = 0;
        foreach ($files as $file) {
            $path = $file->getPathname();
            if (strpos($path, $root . '/vendor/') === 0) {
                continue;
            }

            $output = [];
            $status = 0;
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1', $output, $status);
            $this->assertSame(0, $status, substr($path, strlen($root) + 1) . ': ' . implode("\n", $output));
            ++$checked;
        }

        $this->assertGreaterThan(20, $checked);
    }
}
