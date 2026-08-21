<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MapFilesTest extends TestCase
{
  private string $schemaPath;
  private string $mapDir;

  public function setUp(): void
  {
    $componentRoot = dirname(__DIR__, 2);
    $this->schemaPath = $componentRoot . '/model/schema/goldprice.mysql.schema.xml';
    $this->mapDir = $componentRoot . '/model/goldprice/mysql';
  }

  /**
   * @return array<string, array{table: string, fields: string[]}>
   */
  private function schemaObjects(): array
  {
    $xml = simplexml_load_file($this->schemaPath);
    $this->assertNotFalse($xml);

    $objects = [];
    foreach ($xml->object as $object) {
      $class = (string) $object['class'];
      $table = (string) $object['table'];
      $fields = [];
      foreach ($object->field as $field) {
        $fields[] = (string) $field['key'];
      }
      $objects[$class] = ['table' => $table, 'fields' => $fields];
    }

    return $objects;
  }

  /**
   * @return array<string, mixed>
   */
  private function loadMapMeta(string $className, string $mapFile): array
  {
    $xpdo = new \stdClass();
    $xpdo->map = [];
    $xpdo_meta_map = [];

    include $mapFile;

    if (isset($xpdo->map[$className])) {
      return $xpdo->map[$className];
    }

    if (isset($xpdo_meta_map[$className])) {
      return $xpdo_meta_map[$className];
    }

    return [];
  }

  public function testEachSchemaObjectHasMatchingMapFile(): void
  {
    foreach ($this->schemaObjects() as $class => $schema) {
      $mapFile = $this->mapDir . '/' . strtolower($class) . '.map.inc.php';
      $this->assertFileExists($mapFile, "Missing map file for {$class}");

      $meta = $this->loadMapMeta($class, $mapFile);
      $this->assertArrayHasKey('table', $meta, "Map for {$class} has no table key");
      $this->assertSame($schema['table'], $meta['table'], "Table mismatch for {$class}");

      $this->assertArrayHasKey('fields', $meta, "Map for {$class} has no fields key");
      $mapFields = array_keys($meta['fields']);
      sort($mapFields);
      $schemaFields = $schema['fields'];
      sort($schemaFields);

      $this->assertSame($schemaFields, $mapFields, "Field list mismatch for {$class}");
    }
  }
}
