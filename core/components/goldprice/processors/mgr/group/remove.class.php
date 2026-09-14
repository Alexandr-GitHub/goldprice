<?php
class GoldPriceMgrGroupRemoveProcessor extends modObjectRemoveProcessor
{
    public $classKey = 'GoldPriceGroup';
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.group';

    /** @var int */
    private $childId = 0;

    /** @var int */
    private $parentId = 0;

    /** @var string */
    private $title = '';

    public function beforeRemove()
    {
        $this->parentId = (int) $this->object->get('parent_id');
        if ($this->parentId <= 0) {
            return $this->modx->lexicon('goldprice.err_group_remove_root');
        }

        $this->childId = (int) $this->object->get('id');
        $this->title = (string) $this->object->get('title');

        $productTable = $this->modx->getTableName('GoldPriceProduct');
        $sql = "UPDATE {$productTable} SET `group_id` = :parent WHERE `group_id` = :child";
        $stmt = $this->modx->prepare($sql);
        if ($stmt) {
            $stmt->bindValue(':parent', $this->parentId, PDO::PARAM_INT);
            $stmt->bindValue(':child', $this->childId, PDO::PARAM_INT);
            $stmt->execute();
        }

        return parent::beforeRemove();
    }

    public function afterRemove()
    {
        $gp = $this->modx->goldprice;
        if ($gp) {
            $gp->writeLog('group_subgroup_remove', $this->modx->lexicon('goldprice.log_group_subgroup_remove', [
                'title' => $this->title,
            ]), [
                'id' => $this->childId,
                'parent_id' => $this->parentId,
            ]);
            $this->setProperty('_recalc', $gp->recalculatePrices());
        }

        return parent::afterRemove();
    }

    public function cleanup()
    {
        $summary = $this->getProperty('_recalc');
        if (is_array($summary)) {
            $message = isset($summary['message']) ? (string) $summary['message'] : '';

            return $this->success($message, $summary);
        }

        return parent::cleanup();
    }
}

return 'GoldPriceMgrGroupRemoveProcessor';
