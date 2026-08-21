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
     * @return array{ok:bool,errors:string[],pending:?array}
     */
    public static function fromPost(array $input, array $allowedGroupIds)
    {
        $result = ProductDataValidator::validate($input, $allowedGroupIds);
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
