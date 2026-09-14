<?php

use GoldPrice\Mgr\GroupTrash;

class GoldPriceMgrGroupRestoreProcessor extends modProcessor
{
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.group';

    public function process()
    {
        $id = (int) $this->getProperty('id', 0);
        if ($id <= 0) {
            return $this->failure($this->modx->lexicon('invalid_data'));
        }

        /** @var GoldPriceGroup|null $object */
        $object = $this->modx->getObject('GoldPriceGroup', $id);
        if (!$object) {
            return $this->failure($this->modx->lexicon('object_not_found'));
        }
        if (!GroupTrash::isDeleted($object->toArray())) {
            return $this->failure($this->modx->lexicon('goldprice.err_group_not_deleted'));
        }

        $parentId = (int) $object->get('parent_id');
        if ($parentId > 0) {
            /** @var GoldPriceGroup|null $parent */
            $parent = $this->modx->getObject('GoldPriceGroup', $parentId);
            if (!$parent || GroupTrash::isDeleted($parent->toArray())) {
                return $this->failure($this->modx->lexicon('goldprice.err_group_parent_deleted'));
            }
        }

        $allById = [];
        foreach ($this->modx->getCollection('GoldPriceGroup') as $group) {
            $allById[(int) $group->get('id')] = $group->toArray();
        }

        $restoreIds = GroupTrash::planRestore($object->toArray(), $allById);
        foreach ($restoreIds as $groupId) {
            /** @var GoldPriceGroup|null $row */
            $row = $this->modx->getObject('GoldPriceGroup', $groupId);
            if ($row) {
                $row->set('deleted_at', null);
                $row->save();
            }
        }

        $title = (string) $object->get('title');
        $gp = $this->modx->goldprice;
        $summary = null;
        if ($gp) {
            $gp->writeLog('group_restore', $this->modx->lexicon('goldprice.log_group_restore', [
                'title' => $title,
            ]), [
                'id' => $id,
                'restore_ids' => $restoreIds,
            ]);
            $summary = $gp->recalculatePrices();
        }

        if (is_array($summary)) {
            $message = isset($summary['message']) ? (string) $summary['message'] : '';

            return $this->success($message, $summary);
        }

        return $this->success();
    }
}

return 'GoldPriceMgrGroupRestoreProcessor';
