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

    public function realLtX()
    {
    }

    public function realLtY()
    {
    }

    public function realRbX()
    {
    }

    public function realRbY()
    {
    }
}