<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 18:10
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use yii\base\BaseObject;

/**
 * Class StructInfo
 * @package common\card\components
 */
class StructInfo extends BaseObject
{
    /**
     * @param CardImage $card
     * @return array|bool
     */
    public static function createArray(&$card)
    {
        //todo
        $res = $scoreBoxInfo = [];
        $structs = $card->pageModel->getSubjectStructs();
        foreach ($structs as $struct) {
            $scoreBoxInfo[] = [
                'attributes' => [
                    'name' => $struct->fullTitle(),
                    // 'boxCount' => $scoreBox->step_score == 0.5 ? 3 : 2,
                    // 'blockId' => $scoreBox->id,
                    'scoreStep' => $struct->step_score ?? 1,
                    'fullScore' => $struct->score,
                    'related_struct' => $struct->id,
                    // 'x1' => '',
                    // 'y1' => '',
                    // 'x2' => '',
                    // 'y2' => '',
                ],
            ];
        }
        if (empty($scoreBoxInfo)) {
            return false;
        }
        $res['items']['struct'] = $scoreBoxInfo;
        return $res;
    }
}
