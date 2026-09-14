<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Product;

/**
 * Stages validated form data for OnDocFormSave. Abort ⇒ pending null ⇒ no DB write.
 */
final class ProductFormPending
{
    /**
     * @param array $input
     * @param int[] $allowedGroupIds
     * @param array<int,int> $parentById
     * @return array{ok:bool,errors:string[],pending:?array}
     */
    public static function fromPost(array $input, array $allowedGroupIds, array $parentById = [])
    {
        $result = ProductDataValidator::validate($input, $allowedGroupIds, $parentById);
        if (!$result['ok']) {
            return [
                'ok' => false,
                'errors' => $result['errors'],
                'pending' => null,
            ];
        }

        return [
            'ok' => true,
            'errors' => [],
            'pending' => $result['data'],
        ];
    }
}
