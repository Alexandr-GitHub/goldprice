<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    private const EXPECTED_TABLES = [
        'goldprice_group',
        'goldprice_product',
        'goldprice_quote',
        'goldprice_price',
        'goldprice_state',
        'goldprice_log',
        'goldprice_request',
        'goldprice_recipient',
    ];

    private function loadSchema(): \SimpleXMLElement
    {
        $schemaPath = dirname(__DIR__, 2) . '/model/schema/goldprice.mysql.schema.xml';
        $this->assertFileExists($schemaPath);

        $xml = simplexml_load_file($schemaPath);
        $this->assertNotFalse($xml);

        return $xml;
    }

    public function testSchemaDefinesEightTables(): void
    {
        $xml = $this->loadSchema();

        $tables = [];
        foreach ($xml->object as $object) {
            $tables[] = (string) $object['table'];
        }

        sort($tables);
        $expected = self::EXPECTED_TABLES;
        sort($expected);

        $this->assertCount(8, $tables);
        $this->assertSame($expected, $tables);
    }

    /**
     * xPDO ignores the "scale" attribute: DECIMAL scale must be part of "precision"
     * as "P,S", otherwise the column silently becomes DECIMAL(P,0) and drops kopecks.
     */
    public function testDecimalFieldsKeepFractionalPart(): void
    {
        $decimals = 0;
        foreach ($this->loadSchema()->object as $object) {
            foreach ($object->field as $field) {
                if ((string) $field['dbtype'] !== 'decimal') {
                    continue;
                }

                ++$decimals;
                $name = (string) $object['table'] . '.' . (string) $field['key'];
                $this->assertNull($field['scale'], "Field {$name} must not use the scale attribute");
                $this->assertMatchesRegularExpression(
                    '/^\d+,[1-9]\d*$/',
                    (string) $field['precision'],
                    "Field {$name} must declare precision as \"P,S\" with a non-zero scale"
                );
            }
        }

        $this->assertGreaterThan(0, $decimals);
    }
}
