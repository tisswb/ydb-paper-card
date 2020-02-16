<?php
/**
 * Created by ydb-arrange.
 * User: Tisswb
 * Date: 2020/2/16
 * Time: 17:22
 */

namespace ydb\card\helper;


use ydb\card\PaperCard;
use yii\base\BaseObject;
use yii\db\Query;

/**
 * Class EditAreaHelper
 * @package ydb\card\helper
 *
 * @property string $title
 * @property mixed $container
 */
class EditAreaHelper extends BaseObject
{
    public $id;
    public $item;
    /** @var PaperCard $component */
    public $component;

    public function init()
    {
        parent::init();
        $this->item = (new Query())
            ->select('*')
            ->from($this->component->tableEditArea)
            ->andWhere(['id' => $this->id])
            ->one($this->component->db);
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

    /**
     * @return string
     */
    public function getTitle()
    {
        $titleExt = '';
        $struct = (new Query())
            ->select('*')
            ->from($this->component->tableCardStruct)
            ->andWhere(['id' => $this->item['struct_id']])
            ->one($this->component->db);
        $areas = (new Query())
            ->select('id')
            ->from($this->component->tableEditArea)
            ->andWhere(['struct_id' => $this->item['struct_id']])
            ->column();
        if (count($areas) > 1) {
            $key = array_search($this->id, array_unique($areas));
            $titleExt = ($key === false) ? '' : $key + 1;
        }
        return empty($titleExt) ? $struct['title'] : ($struct['title'] . '-' . $titleExt);
    }
}