<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 19:01
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use common\models\instance\CardContainer;
use yii\base\BaseObject;
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
        $cardConfig = $card->getCardConfig();
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
        $editAreas = $card->pageModel->getEditArea(
            false,
            [CardContainer::TYPE_SUBJECTIVE, CardContainer::TYPE_EXTRA]
        );
        foreach ($editAreas as $index => $editArea) {
            $res['items']['area'][] = [
                'attributes' => [
                    'name' => $editArea->getTitle(),
                    'outId' => (int)($card->pageNum . str_pad($index, 3, 0, STR_PAD_LEFT)),
                    // 'fullScore' => $struct->score ?? 0,
                    'struct_id' => $editArea->struct_id,
                    'pageIndex' => $card->pageNum,
                    'x1' => 2 * $editArea->realLtX(),
                    'y1' => 2 * $editArea->realLtY(),
                    'x2' => 2 * $editArea->realRbX(),
                    'y2' => 2 * $editArea->realRbY(),
                    'content' => $showContent ? $editArea->content : '',
                    'areaId' => $editArea->id,
                ],
            ];
        }
        return $res;
    }
}
