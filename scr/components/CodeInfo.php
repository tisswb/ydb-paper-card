<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2018-07-12
 * Time: 10:17
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use ydb\card\CardService;
use yii\base\BaseObject;

/**
 * Class CodeInfo
 * @package common\card\components
 */
class CodeInfo extends BaseObject
{
    /**
     * @param CardImage $card
     */
    public static function draw(&$card)
    {

    }

    /**
     * paste area 709x274
     * @param CardImage $card
     * @return array
     */
    public static function createArray(&$card)
    {
        $res = $code = [];
        if ($card->pageNum % 2 == 1) {
            if (in_array($card->columns, [CardService::COLUMN_ONE, CardService::COLUMN_TWO])) {
                $code = [
                    'attributes' => [
                        'blockId' => 1,
                        'codeType' => 'code',
                        'used' => '',
                        'x1' => $card->left + 1461,
                        'y1' => $card->top + 407,
                        'x2' => $card->left + 2169,
                        'y2' => $card->top + 679,
                    ],
                ];
            } elseif ($card->columns == CardService::COLUMN_THREE) {
                $code = [
                    'attributes' => [
                        'blockId' => 1,
                        'codeType' => 'code',
                        'used' => '',
                        'x1' => $card->left + 762,
                        'y1' => $card->top + 600,
                        'x2' => $card->left + 1471,
                        'y2' => $card->top + 874,
                    ],
                ];
            }
            $res['items']['codeInfo'] = $code;
        }
        return $res;
    }
}
