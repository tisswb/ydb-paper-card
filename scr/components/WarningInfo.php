<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2018-07-13
 * Time: 9:27
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use Imagick;
use ImagickDraw;
use yii\base\BaseObject;
use yii\db\Query;

/**
 * Class Warning
 * @package common\card\components
 */
class WarningInfo extends BaseObject
{
    /**
     * @param CardImage $card
     * @return bool
     */
    public static function draw(&$card)
    {
        $fontSize = 36;
        $courseName = $card->courseName;
        $y = $card->height - $card->bottom - $card->helpPointWidth + 16;
        $pageCount = (new Query())
            ->select('*')
            ->from($card->component->tablePage)
            ->andWhere(['card_id' => $card->card['id']])
            ->count($card->component->db);
        $count = $pageCount * $card->columns;
        $draw = new ImagickDraw();
        $draw->setFont($card->textFontContent);
        $draw->setFontSize($fontSize);
        $draw->setFillColor($card->colorRed);
        $draw->setTextInterLineSpacing(20);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);
        $num = ($card->pageNum - 1) * $card->columns + 1;

        for ($step = 1; $step <= $card->columns; $step++) {
            $x = $card->left + $card->helpPointWidth + ($card->contentWidth + $card->center) * ($step - 1)
                 + $card->contentWidth / 2;
            $draw->annotation($x, $y, "请在各题目区域内作答，超出边框作答无效\n{$courseName} 第 {$num} 页 (共 {$count} 页)");
            $num++;
        }

        return $card->im->drawImage($draw);
    }
}
