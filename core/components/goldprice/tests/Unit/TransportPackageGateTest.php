<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Gates the shipping transport zip: resolver path, vehicle order, migration code.
 * Catches the 1.1.0–1.1.2 packaging bugs without a live MODX install.
 */
final class TransportPackageGateTest extends TestCase
{
    private string $zipPath;
    private string $unpackDir;
    private string $pkgName;

    protected function setUp(): void
    {
        $repo = dirname(__DIR__, 5); // tests/Unit → repo root
        $candidates = glob($repo . '/goldprice-*-pl.transport.zip') ?: [];
        rsort($candidates);
        $this->assertNotEmpty($candidates, 'No goldprice-*-pl.transport.zip in repo root');
        $this->zipPath = $candidates[0];
        $this->pkgName = basename($this->zipPath, '.transport.zip');
        $this->unpackDir = sys_get_temp_dir() . '/goldprice-pkg-gate-' . getmypid();
        $this->rmTree($this->unpackDir);
        mkdir($this->unpackDir, 0755, true);
        $cmd = 'unzip -q ' . escapeshellarg($this->zipPath) . ' -d ' . escapeshellarg($this->unpackDir);
        exec($cmd, $out, $code);
        $this->assertSame(0, $code, 'unzip failed: ' . $this->zipPath);
    }

    protected function tearDown(): void
    {
        $this->rmTree($this->unpackDir);
    }

    public function testPackageFolderMatchesZipName(): void
    {
        $this->assertDirectoryExists($this->unpackDir . '/' . $this->pkgName);
    }

    public function testCategoryResolverPathMatchesPackageFolder(): void
    {
        $catSig = '2dfceb2c9077da0728bf95a73b366ae1';
        $vehicle = include $this->unpackDir . '/' . $this->pkgName . '/modCategory/' . $catSig . '.vehicle';
        $this->assertIsArray($vehicle['resolve'] ?? null);
        $body = json_decode($vehicle['resolve'][0]['body'], true);
        $this->assertIsArray($body);
        $expected = $this->pkgName . '/modCategory/' . $catSig . '.resolve.tables.resolver';
        $this->assertSame($expected, $body['source']);
        $this->assertFileExists($this->unpackDir . '/' . $this->pkgName . '/' . str_replace($this->pkgName . '/', '', $body['source']));
        // Absolute from unpack root:
        $this->assertFileExists($this->unpackDir . '/' . $body['source']);
    }

    public function testNoStalePackageFolderInVehicleSources(): void
    {
        $stale = ['goldprice-1.0.1-pl', 'goldprice-1.1.0-pl', 'goldprice-1.1.1-pl', 'goldprice-1.1.2-pl'];
        $stale = array_values(array_filter($stale, fn ($n) => $n !== $this->pkgName));
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->unpackDir . '/' . $this->pkgName)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || substr($file->getFilename(), -8) !== '.vehicle') {
                continue;
            }
            $raw = file_get_contents($file->getPathname());
            foreach ($stale as $name) {
                $this->assertStringNotContainsString(
                    $name,
                    $raw,
                    'Stale package path in ' . $file->getFilename()
                );
            }
        }
    }

    public function testTablesResolverRunsBeforeAssetsVehicle(): void
    {
        $manifest = include $this->unpackDir . '/' . $this->pkgName . '/manifest.php';
        $vehicles = $manifest['manifest-vehicles'];
        $categoryIdx = null;
        $assetsIdx = null;
        foreach ($vehicles as $i => $v) {
            $file = (string) ($v['filename'] ?? '');
            if (strpos($file, 'modCategory/') === 0) {
                $categoryIdx = $i;
            }
            // assets payload guid from historical builds
            if (strpos($file, 'd5b3f54c3fcafd31f24f2a7c3bf8af81') !== false) {
                $assetsIdx = $i;
            }
        }
        $this->assertNotNull($categoryIdx, 'modCategory vehicle missing');
        $this->assertNotNull($assetsIdx, 'assets file vehicle missing');
        $this->assertLessThan(
            $assetsIdx,
            $categoryIdx,
            'Category+resolver must run before assets preserve (Beget hang)'
        );
    }

    public function testResolverContainsParentIdMigration(): void
    {
        $resolver = $this->unpackDir . '/' . $this->pkgName
            . '/modCategory/2dfceb2c9077da0728bf95a73b366ae1.resolve.tables.resolver';
        $src = file_get_contents($resolver);
        $this->assertStringContainsString('parent_id', $src);
        $this->assertStringContainsString('goldpriceEnsureColumn', $src);
        $this->assertStringContainsString('ACTION_UPGRADE', $src);
    }

    public function testManifestHasChangelogAttribute(): void
    {
        $manifest = include $this->unpackDir . '/' . $this->pkgName . '/manifest.php';
        $this->assertNotEmpty($manifest['manifest-attributes']['changelog'] ?? '');
        $this->assertStringContainsString('parent_id', $manifest['manifest-attributes']['changelog']);
    }

    private function rmTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
