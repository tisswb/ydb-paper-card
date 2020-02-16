<?php
/**
 * Created by ydb-arrange.
 * User: Tisswb
 * Date: 2020/2/16
 * Time: 15:07
 */

namespace ydb\card\helper;


use yii\base\BaseObject;
use yii\db\Query;

/**
 * Class ScoreBoxHelper
 * @package ydb\card\helper
 *
 * @property array $structId
 * @property array $container
 * @property string $structName
 */
class ScoreBoxHelper extends BaseObject
{
    public $id;
    public $component;
    public $item;

    /**
     * @throws \Exception
     */
    public function init()
    {
        parent::init();
        if (isset($this->id) && isset($this->component)) {
            $this->item = (new Query())
                ->select('*')
                ->from($this->component->tableScoreBox)
                ->andWhere(['id' => $this->id])
                ->one($this->component->db);
        } else {
            throw new \Exception('score box init error');
        }
    }

    /**
     * @return array
     */
    public function getStructId()
    {
        return (new Query())
            ->select(['struct_id'])
            ->from(['score_box_id' => $this->item['id']])
            ->column($this->component->db);
    }

    /**
     * @return string
     */
    public function getStructName()
    {
        $structIds = $this->getStructId();
        $names = (new Query())
            ->select(['title'])
            ->from($this->component->tableCardStruct)
            ->andWhere(['id' => $structIds])
            ->orderBy('id')
            ->column($this->component->db);
        $parentId = (new Query())
            ->select(['parent_id'])
            ->from($this->component->tableCardStruct)
            ->andWhere(['id' => $structIds])
            ->distinct()
            ->scalar($this->component->db);
        $parentName = (new Query())
            ->select(['title'])
            ->from($this->component->tableCardStruct)
            ->andWhere(['id' => $parentId])
            ->scalar($this->component->db) ?? $this->id;
        if (count($names) >= 2) {
            $first = array_shift($names);
            $last = array_pop($names);
            $resName = $first . '-' . $last;
        } else {
            $resName = implode('-', $names);
        }
        return $parentName . '-' . $resName;
    }

    /**
     * @return array
     */
    public function getContainer()
    {
        return (new Query())
            ->select('*')
            ->from($this->component->tableContainer)
            ->andWhere(['id' => $this->item['card_container_id']])
            ->all($this->component->db);
    }

    /**
     * @return int
     */
    public function realLtX()
    {
        $container = $this->getContainer();
        return (int)$container['lt_pos_x'] + (int)$this->item['lt_pos_x'];
    }

    /**
     * @return int
     */
    public function realLtY()
    {
        $container = $this->getContainer();
        return (int)$container['lt_pos_y'] + (int)$this->item['lt_pos_y'];
    }

    /**
     * @return int
     */
    public function realRbX()
    {
        $container = $this->getContainer();
        return (int)$container['lt_pos_x'] + (int)$this->item['rb_pos_x'];
    }

    /**
     * @return int
     */
    public function realRbY()
    {
        $container = $this->getContainer();
        return (int)$container['lt_pos_y'] + (int)$this->item['rb_pos_y'];
    }
}