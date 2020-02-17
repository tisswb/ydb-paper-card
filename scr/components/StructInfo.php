<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 18:10
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use yii\base\BaseObject;
use yii\db\Query;

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
        $res = $scoreBoxInfo = [];
        $containerIds = (new Query())
            ->select(['id'])
            ->from($card->component->tableContainer)
            ->andWhere(['card_page_id' => $card->cardPage['id']])
            ->column($card->component->db);
        $structIds = (new Query())
            ->select('struct_id')
            ->from($card->component->tableEditArea)
            ->andWhere(['card_container_id' => $containerIds])
            ->column($card->component->db);
        $structs = (new Query())
            ->select('*')
            ->from($card->component->tableCardStruct)
            ->andWhere(['id' => $structIds])
            ->andWhere(['type' => ['multi', 'single']])
            ->orderBy(['parent_id' => SORT_ASC, 'id' => SORT_ASC])
            ->all($card->component->db);
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
