<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Gates the shipping transport zip against Beget install failures.
 */
final class TransportPackageGateTest extends TestCase
{
    private string $zipPath;
    private string $unpackDir;
    private string $pkgName;

    protected function setUp(): void
    {
        $repo = dirname(__DIR__, 5);
        $candidates = glob($repo . '/goldprice-*-pl.transport.zip') ?: [];
        rsort($candidates);
        $this->assertNotEmpty($candidates, 'No goldprice-*-pl.transport.zip in repo root');
        $this->zipPath = $candidates[0];
        $this->pkgName = basename($this->zipPath, '.transport.zip');
        $this->unpackDir = sys_get_temp_dir() . '/goldprice-pkg-gate-' . getmypid();
        $this->rmTree($this->unpackDir);
        mkdir($this->unpackDir, 0755, true);
        exec('unzip -q ' . escapeshellarg($this->zipPath) . ' -d ' . escapeshellarg($this->unpackDir), $out, $code);
        $this->assertSame(0, $code, 'unzip failed');
    }

    protected function tearDown(): void
    {
        $this->rmTree($this->unpackDir);
    }

    public function testNamespaceMigrateRunsFirstWithValidResolverPath(): void
    {
        $manifest = include $this->unpackDir . '/' . $this->pkgName . '/manifest.php';
        $first = $manifest['manifest-vehicles'][0];
        $this->assertSame('modNamespace', $first['class']);
        $nsSig = '4071b8cc8762511dfe14fcc9b4fcaef1';
        $vehicle = include $this->unpackDir . '/' . $this->pkgName . '/modNamespace/' . $nsSig . '.vehicle';
        $this->assertIsArray($vehicle['resolve'] ?? null);
        $body = json_decode($vehicle['resolve'][0]['body'], true);
        $this->assertSame(
            $this->pkgName . '/modNamespace/' . $nsSig . '.resolve.migrate.resolver',
            $body['source']
        );
        $this->assertFileExists($this->unpackDir . '/' . $body['source']);
        $src = file_get_contents($this->unpackDir . '/' . $body['source']);
        $this->assertStringContainsString('parent_id', $src);
        $this->assertStringContainsString('goldpriceMigrateRawColumns', $src);
    }

    public function testFileVehiclesSkipPreserveZip(): void
    {
        $dir = $this->unpackDir . '/' . $this->pkgName . '/xPDOFileVehicle';
        foreach (glob($dir . '/*.vehicle') as $file) {
            $v = include $file;
            $this->assertSame(
                1,
                (int) ($v['preexisting_mode'] ?? -1),
                'preexisting_mode=REMOVE required in ' . basename($file)
            );
        }
    }

    public function testCategoryResolverDefinesMigrateBeforeCall(): void
    {
        $resolver = $this->unpackDir . '/' . $this->pkgName
            . '/modCategory/2dfceb2c9077da0728bf95a73b366ae1.resolve.tables.resolver';
        $src = file_get_contents($resolver);
        $fn = strpos($src, 'function goldpriceMigrateRawColumns');
        $call = strpos($src, 'goldpriceMigrateRawColumns($modx)');
        $this->assertNotFalse($fn);
        $this->assertNotFalse($call);
        $this->assertLessThan($call, $fn, 'Fatal on Beget: call before function definition');
    }

    public function testNoStalePackageFolderInVehicles(): void
    {
        $stale = [
            'goldprice-1.0.1-pl',
            'goldprice-1.1.0-pl',
            'goldprice-1.1.1-pl',
            'goldprice-1.1.2-pl',
            'goldprice-1.1.3-pl',
        ];
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
                $this->assertStringNotContainsString($name, $raw, $file->getFilename());
            }
        }
    }

    public function testTablesResolverBeforeAssets(): void
    {
        $manifest = include $this->unpackDir . '/' . $this->pkgName . '/manifest.php';
        $categoryIdx = $assetsIdx = null;
        foreach ($manifest['manifest-vehicles'] as $i => $v) {
            $file = (string) ($v['filename'] ?? '');
            if (strpos($file, 'modCategory/') === 0) {
                $categoryIdx = $i;
            }
            if (strpos($file, 'd5b3f54c3fcafd31f24f2a7c3bf8af81') !== false) {
                $assetsIdx = $i;
            }
        }
        $this->assertNotNull($categoryIdx);
        $this->assertNotNull($assetsIdx);
        $this->assertLessThan($assetsIdx, $categoryIdx);
    }

    public function testManifestHasChangelog(): void
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
