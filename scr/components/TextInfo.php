<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 19:01
 */

namespace ydb\card\components;

use ydb\card\CardImage;
// use common\models\instance\CardContainer;
use ydb\card\helper\EditAreaHelper;
use yii\base\BaseObject;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class TextInfo
 * @package common\card\components
 */
class TextInfo extends BaseObject
{
    /**
     * @param CardImage $card
     * @return bool
     */
    public static function draw(&$card)
    {
        $cardConfig = $card->cardConfig;
        $textInfo = static::createArray($card);
        $areas = ArrayHelper::getValue($textInfo, ['items.area']);
        if (empty($areas)) {
            return false;
        }
        foreach ($areas as $area) {
            $image = $card->capture($area['attributes']['areaId'], $cardConfig['showChildScore']);
            $card->composeImage($image, $area['attributes']['x1'], $area['attributes']['y1']);

            // $draw = new ImagickDraw();
            // $draw->setFillColor($card->colorTransparent);
            // $draw->setStrokeWidth(1);
            // $draw->setStrokeColor($card->colorRed);
            // $draw->rectangle(
            //     $area['attributes']['x1'],
            //     $area['attributes']['y1'],
            //     $area['attributes']['x2'],
            //     $area['attributes']['y2']
            // );
            // $card->im->drawImage($draw);
        }
        return true;
    }

    /**
     * @param CardImage $card
     * @param bool $showContent
     * @return array
     */
    public static function createArray(&$card, $showContent = true)
    {
        $res = [];
        $containerIds = (new Query())
            ->select(['id'])
            ->from($card->component->tableContainer)
            ->andWhere(['card_page_id' => $card->cardPage['id']])
            ->column($card->component->db);
        $editAreaIds = (new Query())
            ->select('id')
            ->from($card->component->tableEditArea)
            ->andWhere(['card_container_id' => $containerIds])
            ->orderBy(['parent_id' => SORT_ASC, 'id' => SORT_ASC])
            ->all($card->component->db);
        foreach ($editAreaIds as $index => $editAreaId) {
            $area = new EditAreaHelper(
                [
                    'id' => $editAreaId,
                    'component' => $card->component,
                ]
            );
            $res['items']['area'][] = [
                'attributes' => [
                    'name' => $area->getTitle(),
                    'outId' => (int)($card->pageNum . str_pad($index, 3, 0, STR_PAD_LEFT)),
                    // 'fullScore' => $struct->score ?? 0,
                    'struct_id' => $area->item['struct_id'],
                    'pageIndex' => $card->pageNum,
                    'x1' => 2 * $area->realLtX(),
                    'y1' => 2 * $area->realLtY(),
                    'x2' => 2 * $area->realRbX(),
                    'y2' => 2 * $area->realRbY(),
                    'content' => $showContent ? $area->item['content'] : '',
                    'areaId' => $editAreaId,
                ],
            ];
        }
        return $res;
    }
}
