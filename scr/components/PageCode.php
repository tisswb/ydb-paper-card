<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 16:23
 */

namespace ydb\card\components;

use common\card\CardImage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class PageCode
 * @package common\card\components
 */
class PageCode extends BaseObject
{
    /**
     * @param CardImage $card
     * @throws \Picqer\Barcode\Exceptions\BarcodeException
     */
    public static function draw(&$card)
    {
        $pageCode = static::createArray($card);
        $code = ArrayHelper::getValue($pageCode, ['items.code']);
        $generatorPNG = new BarcodeGeneratorPNG();
        $image = $generatorPNG->getBarcode(
            $code['attributes']['content'],
            $generatorPNG::TYPE_CODE_128_C,
            4,
            44);
        $card->composeImage($image, $code['attributes']['x1'], $code['attributes']['y1']);
    }

    /**
     * @param CardImage $card
     * @return mixed
     */
    public static function createArray(&$card)
    {
        $courseIdEncode = static::courseIdEncode($card->pageModel->getPaper()->course_id);
        $pageEncode = str_pad($card->pageNum, 2, 0, STR_PAD_LEFT);
        $ABEncode = '01'; // AB卷预留，默认A卷01，B卷02
        $pageCode = $ABEncode . $pageEncode . $courseIdEncode;
        $code = [
            'attributes' => [
                'blockId' => 1,
                'content' => $pageCode,
                'x1' => 1820, // 300
                'y1' => 122,
                'x2' => 2180,
                'y2' => 166,
            ],
        ];
        $res['items']['code'] = $code;
        return $res;
    }

    /**
     * @param $courseId
     * @return bool|string
     */
    private static function courseIdEncode($courseId)
    {
        $stage = substr($courseId, 0, 1);
        $courseNum = substr($courseId, 1);
        $stageArray = ['A', 'B', 'C'];
        if (in_array($stage, $stageArray) && is_numeric($courseNum)) {
            $flipped = array_flip($stageArray);
            return $flipped[$stage] . str_pad($courseNum, 4, 0, STR_PAD_LEFT);
        }
        return false;
    }
}
