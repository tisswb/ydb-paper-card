<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 17:50
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class OcrInfo
 * @package common\card\components
 */
class OcrInfo extends BaseObject
{
    /**
     * @param CardImage $card
     * @return bool
     */
    public static function draw(&$card)
    {
        $ocrInfo = static::createArray($card);
        $ocrs = ArrayHelper::getValue($ocrInfo, ['items.ocr']);
        if (!empty($ocrs)) {
            foreach ($ocrs as $index => $ocr) {
                if ($ocr['attributes']['boxCount'] == 3) {
                    $imageUrl = $card->imageDir . '/score_box_point.svg';
                } else {
                    $imageUrl = $card->imageDir . '/score_box.svg';
                }
                $image = file_get_contents($imageUrl);
                $card->composeImage($image, $ocr['attributes']['x1'], $ocr['attributes']['y1']);

                // $draw = new ImagickDraw();
                // $draw->setFillColor($this->colorTransparent);
                // $draw->setStrokeWidth(1);
                // $draw->setStrokeColor($this->colorRed);
                // $draw->rectangle(
                //     $ocr['attributes']['x1'],
                //     $ocr['attributes']['y1'],
                //     $ocr['attributes']['x2'],
                //     $ocr['attributes']['y2']
                // );
                // $this->im->drawImage($draw);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param CardImage $card
     * @return array|bool
     */
    public static function createArray(&$card)
    {
        $res = $scoreBoxInfo = [];
        $scoreBoxs = $card->pageModel->getScoreBox();
        foreach ($scoreBoxs as $scoreBox) {
            $scoreBoxInfo[] = [
                'attributes' => [
                    'name' => $scoreBox->getStructName(),
                    'boxCount' => $scoreBox->step_score == 0.5 ? 3 : 2,
                    'blockId' => $scoreBox->id,
                    'scoreStep' => $scoreBox->step_score,
                    'fullScore' => $scoreBox->total,
                    'related_struct' => implode(',', $scoreBox->getStructId()),
                    'x1' => 2 * $scoreBox->realLtX(),
                    'y1' => 2 * $scoreBox->realLtY(),
                    'x2' => 2 * $scoreBox->realRbX(),
                    'y2' => 2 * $scoreBox->realRbY(),
                ],
            ];
        }
        if (empty($scoreBoxInfo)) {
            return false;
        }
        $res['items']['ocr'] = $scoreBoxInfo;
        return $res;
    }
}
