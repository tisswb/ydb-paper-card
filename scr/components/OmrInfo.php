<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 16:35
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use ydb\card\CardService;
use common\models\instance\CardContainer;
use common\models\instance\CardEditArea;
use Imagick;
use ImagickDraw;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class OmrInfo
 * @package common\card\components
 */
class OmrInfo extends BaseObject
{
    /**
     * @param CardImage $card
     * @return bool|ImagickDraw
     */
    public static function draw(&$card)
    {
        $draw = new ImagickDraw();
        $draw->setFillColor($card->colorRed);
        $omrInfo = static::createArray($card, false, true);
        if (empty($omrInfo)) {
            return false;
        }
        $omrs = ArrayHelper::getValue($omrInfo, ['items.omr']);
        foreach ($omrs as $omr) {
            $omrAttr = ArrayHelper::getValue($omr, ['attributes']);
            if ($omrAttr['content']) {
                $draw->setFont($card->textFont);
                $draw->setFontSize(36);
                $draw->setTextKerning(1);
                $draw->setStrokeColor($card->colorBlack);
                $draw->setStrokeWidth(0);
                $draw->setTextAlignment(Imagick::ALIGN_RIGHT);
                $draw->setFillColor($card->colorBlack);
                $draw->annotation($omrAttr['x'], $omrAttr['y'] - 12, $omrAttr['content']);

                // $draw->setFillColor($card->colorTransparent);
                // $draw->setStrokeColor($card->colorRed);
                // $draw->setStrokeWidth(1);
                // $draw->rectangle($omrAttr['x1'], $omrAttr['y1'], $omrAttr['x'], $omrAttr['y']);
            }
            $points = ArrayHelper::getValue($omr, ['items.point']);
            if (is_array($points)) {
                foreach ($points as $point) {
                    if (isset($point['attributes']['type']) && $point['attributes']['type'] == 'block') {
                        $draw->setFillColor($card->colorTransparent);
                        $draw->setStrokeWidth(1);
                        $draw->setStrokeColor($card->colorRed);
                        $draw->rectangle(
                            $point['attributes']['x1'],
                            $point['attributes']['y1'],
                            $point['attributes']['x2'],
                            $point['attributes']['y2']
                        );
                    } else {
                        $draw->setFont($card->textFont);
                        $fontSize = $omrAttr['fontSize'] ?? 28;
                        $draw->setFontSize($fontSize);
                        $draw->setTextAlignment(Imagick::ALIGN_CENTER);
                        $draw->setTextKerning(5);
                        $draw->setFillColor($card->colorRed);
                        $draw->setStrokeColor($card->colorRed);
                        $draw->setStrokeWidth(1);
                        $draw->annotation(
                            ceil(($point['attributes']['x1'] + $point['attributes']['x2']) / 2),
                            $point['attributes']['y2'] - 4,
                            '[' . $point['attributes']['value'] . ']'
                        );
                    }

                    // $draw->setFillColor($card->colorTransparent);
                    // $draw->setStrokeColor($card->colorRed);
                    // $draw->setStrokeWidth(1);
                    // $draw->rectangle($point['attributes']['x1'], $point['attributes']['y1'], $point['attributes']['x2'], $point['attributes']['y2']);
                }
            }
        }
        return $card->im->drawImage($draw);
    }

    /**
     * @param CardImage $card
     * @param bool $enableUseLength
     * @param bool $isDraw 是否是画图（false的话xml中选项框坐标向外扩1像素）
     * @return array|bool
     */
    public static function createArray(&$card, $enableUseLength = false, $isDraw = true)
    {
        $omr = $res = [];
        $paperCardSetting = $card->getCardConfig();
        $omr = ArrayHelper::merge($omr, static::getObjectOmrInfo($card, $isDraw));
        if ($paperCardSetting !== null && $paperCardSetting['examNoType'] == 'fill') {
            $omr = ArrayHelper::merge($omr, static::getExamNumberOmrInfo($card, $enableUseLength));
        }
        $omr = ArrayHelper::merge($omr,
            static::getForbidOmrInfo($card)
        );
        if (empty($omr)) {
            return false;
        }
        $res['items']['omr'] = $omr;
        return $res;
    }

    /**
     * @param CardImage $card
     * @param $isDraw
     * @return array
     */
    public static function getObjectOmrInfo(&$card, $isDraw)
    {
        $res = [];
        /** @var CardEditArea[] $structAreas */
        $structAreas = ArrayHelper::index($card->pageModel->getEditArea(), 'struct_id');
        $structs = $card->pageModel->getOmrStructs();
        if (!empty($structs)) {
            foreach ($structs as $key => $struct) {
                $bigFont = CardContainer::cardObjOptionsSize(
                    $struct->exam_paper_id,
                    $structAreas[$struct->id]->card_container_id
                );
                $res[] = [
                    'attributes' => [
                        'used' => CardService::OMR_USED_OBJECT,
                        'fontSize' => $bigFont == 0 ? 28 : 36,
                        'name' => $struct->fullTitle(),
                        'selectType' => $struct->type,
                        'pointCount' => $struct->options_num,
                        'sequence' => $key + 1,
                        'answer' => '',
                        'struct_id' => $struct->id,
                        'fullScore' => '',
                        'partScore' => '',
                        'errorScore' => '',
                        'content' => $struct->title,
                        'x1' => 2 * $structAreas[$struct->id]->realLtX(),
                        'y1' => 2 * $structAreas[$struct->id]->realLtY(),
                        'x2' => 2 * $structAreas[$struct->id]->realRbX(),
                        'y2' => 2 * $structAreas[$struct->id]->realRbY(),
                        'x' => 2 * (int)$structAreas[$struct->id]->realLtX() +
                            $card->selectionContentWidth,
                        'y' => 2 * $structAreas[$struct->id]->realRbY(),
                    ],
                    'items' => static::selectionPoints(
                        $struct->options_num,
                        $struct->options_value,
                        $structAreas[$struct->id]->realLtX(),
                        $structAreas[$struct->id]->realLtY(),
                        $structAreas[$struct->id]->realRbX(),
                        $structAreas[$struct->id]->realRbY(),
                        $card->selectionContentWidth,
                        $card->pointWidth,
                        $bigFont == 0 ? 28 : 36,
                        $isDraw
                    ),
                ];
            }
        }
        return $res;
    }

    /**
     * @param $count
     * @param $valueItem
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param $contentWidth
     * @param $pWidth
     * @param $pHeight
     * @param $isDraw
     * @return array
     */
    public static function selectionPoints($count, $valueItem, $x1, $y1, $x2, $y2, $contentWidth, $pWidth, $pHeight, $isDraw)
    {
        $res = [];
        $selectCount = $count > 5 ? $count : 5;
        $stepWidth = ceil((2 * $x2 - 2 * $x1 - $contentWidth) / $selectCount);
        $pointStartX = ceil(
            2 * $x1 + $contentWidth + ($stepWidth - $pWidth) / 2
        );
        $pointEndX = $pointStartX + $pWidth;
        $pointStartY = 2 * $y1 + intval((2 * $y2 - 2 * $y1 - $pHeight) / 2);
        $pointEndY = $pointStartY + $pHeight;
        $valueItem = empty($valueItem) ? 'ABCDEFGH' : $valueItem;
        if ($isDraw) {
            for ($pos = 0; $pos < $count; $pos++) {
                $res['point'][] = [
                    'attributes' => [
                        'x1' => $pointStartX + $stepWidth * $pos,
                        'y1' => $pointStartY,
                        'x2' => $pointEndX + $stepWidth * $pos,
                        'y2' => $pointEndY,
                        'value' => $valueItem[$pos],
                        'type' => '',
                    ],
                ];
            }
        } else {
            for ($pos = 0; $pos < $count; $pos++) {
                $res['point'][] = [
                    'attributes' => [
                        'x1' => $pointStartX + $stepWidth * $pos - 1,
                        'y1' => $pointStartY - 1,
                        'x2' => $pointEndX + $stepWidth * $pos + 1,
                        'y2' => $pointEndY + 1,
                        'value' => $valueItem[$pos],
                        'type' => '',
                    ],
                ];
            }
        }

        return $res;
    }

    /**
     * @param CardImage $card
     * @param bool $enableUseLength
     * @return array
     */
    public static function getExamNumberOmrInfo(&$card, $enableUseLength = false)
    {
        $res = [];
        if ($card->pageNum == 1) {
            $res = [];
        }
        if ($card->pageNum % 2 == 1) {
            if ($card->examNumCount > 9) {
                $res = static::getExamNumberOmrFourteen($card, $enableUseLength);
            } else {
                $res = static::getExamNumberOmrNine($card, $enableUseLength);
            }
        }
        return $res;
    }

    /**
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param $pWidth
     * @param $pHeight
     * @return array
     */
    public static function examNumPoints($x1, $y1, $x2, $y2, $pWidth, $pHeight)
    {
        $res = [];
        $stepHeight = (2 * $y2 - 2 * $y1) / 10;
        $pointStartX = (int)($x1 + $x2 - $pWidth / 2);
        $pointStartY = (int)(2 * $y1 + ($stepHeight - $pHeight) / 2);
        $pointEndX = (int)($pointStartX + $pWidth);
        $pointEndY = (int)($pointStartY + $pHeight);

        $valueItem = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        foreach ($valueItem as $pos => $value) {
            $res['point'][] = [
                'attributes' => [
                    'x1' => (int)($pointStartX),
                    'y1' => (int)($pointStartY + $stepHeight * $pos),
                    'x2' => (int)($pointEndX),
                    'y2' => (int)($pointEndY + $stepHeight * $pos),
                    'value' => $value,
                    'type' => '',
                ],
            ];
        }
        return $res;
    }

    /**
     * @param CardImage $card
     * @return array
     */
    public static function getForbidOmrInfo(& $card)
    {
        if ($card->pageNum != 1) {
            return [];
        }
        $offset = 0;
        $yOffset = 0;
        $config = $card->getCardConfig();
        if ($card->columns == CardService::COLUMN_THREE) {
            if ($config['examNoType'] == 'paste') {
                $miss = [
                    'x1' => 388 + $card->left,
                    'y1' => 1133,
                    'x2' => 388 + $card->left + $card->pointWidth - 2,
                    'y2' => 1133 + $card->pointHeight - 2,
                ];
                $violation = [
                    'x1' => 575 + $card->left,
                    'y1' => 1133,
                    'x2' => 575 + $card->left + $card->pointWidth - 2,
                    'y2' => 1133 + $card->pointHeight - 2,
                ];
            } else {
                if ($config['examNumberLength'] > 9) {
                    $miss = [
                        'x1' => 329 + $card->left,
                        'y1' => 1095,
                        'x2' => 329 + $card->left + $card->pointWidth - 2,
                        'y2' => 1095 + $card->pointHeight - 2,
                    ];
                    $violation = [
                        'x1' => 329 + $card->left,
                        'y1' => 1141,
                        'x2' => 329 + $card->left + $card->pointWidth - 2,
                        'y2' => 1141 + $card->pointHeight - 2,
                    ];
                } else {
                    $miss = [
                        'x1' => 309 + $card->left,
                        'y1' => 1136,
                        'x2' => 309 + $card->left + $card->pointWidth - 2,
                        'y2' => 1136 + $card->pointHeight - 2,
                    ];
                    $violation = [
                        'x1' => 495 + $card->left,
                        'y1' => 1136,
                        'x2' => 495 + $card->left + $card->pointWidth - 2,
                        'y2' => 1136 + $card->pointHeight - 2,
                    ];
                }
            }
        } else {
            if ($config['showQrcode'] == ACTIVE_YES) {
                $offset = 256;
            }
            if ($card->gutter == ACTIVE_YES) {
                $yOffset = 90;
            }
            $miss = [
                'x1' => $offset + 600 - 212 + $card->left,
                'y1' => 915 - $yOffset,
                'x2' => $offset + 600 - 212 + $card->left + $card->pointWidth - 2,
                'y2' => 915 + $card->pointHeight - 2 - $yOffset,
            ];
            $violation = [
                'x1' => $offset + 783 - 212 + $card->left,
                'y1' => 915 - $yOffset,
                'x2' => $offset + 783 - 212 + $card->left + $card->pointWidth - 2,
                'y2' => 915 + $card->pointHeight - 2 - $yOffset,
            ];
        }
        return [
            [
                'attributes' => [
                    'used' => CardService::OMR_USED_MISS_TEST,
                    'name' => '',
                    'selectType' => 'single',
                    'pointCount' => 1,
                    'sequence' => 1,
                    'answer' => '',
                    'struct_id' => '',
                    'fullScore' => '',
                    'partScore' => '',
                    'errorScore' => '',
                    'content' => '',
                    'x1' => '',
                    'y1' => '',
                    'x2' => '',
                    'y2' => '',
                    'x' => '',
                    'y' => '',
                ],
                'items' => [
                    'point' => [
                        [
                            'attributes' => [
                                'x1' => $miss['x1'],
                                'y1' => $miss['y1'],
                                'x2' => $miss['x2'],
                                'y2' => $miss['y2'],
                                'value' => '',
                                'type' => 'block',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'attributes' => [
                    'used' => CardService::OMR_USED_VIOLATION,
                    'name' => '',
                    'selectType' => 'single',
                    'pointCount' => 1,
                    'sequence' => 1,
                    'answer' => '',
                    'struct_id' => '',
                    'fullScore' => '',
                    'partScore' => '',
                    'errorScore' => '',
                    'content' => '',
                    'x1' => '',
                    'y1' => '',
                    'x2' => '',
                    'y2' => '',
                    'x' => '',
                    'y' => '',
                ],
                'items' => [
                    'point' => [
                        [
                            'attributes' => [
                                'x1' => $violation['x1'],
                                'y1' => $violation['y1'],
                                'x2' => $violation['x2'],
                                'y2' => $violation['y2'],
                                'value' => '',
                                'type' => 'block',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $card
     * @param $enableUseLength
     * @return array
     */
    private static function getExamNumberOmrFourteen(&$card, $enableUseLength)
    {
        $res = [];
        $headers = Header::createArray($card);
        $offsetY = 0;
        if ($card->columns == 3) {
            $offsetX = 217;
            $offsetY = 107;
        } else {
            $offsetX = 567;
        }
        $stepWidth = $card->examNumBoxWidth / 2;
        $left = $card->left / 2;
        $x1 = (int)$left + $offsetX;
        $x2 = (int)$left + $card->contentWidth / 2;
        $y1 = 260 + $offsetY;
        $y2 = 482 + $offsetY;
        $offset = ($enableUseLength && $headers['examNumberUseLength'])
            ? (14 - $headers['examNumberUseLength'])
            : (14 - $headers['examNumberLength']);
        for ($index = $offset; $index < 14; $index++) {
            $res[] = [
                'attributes' => [
                    'used' => CardService::OMR_USED_NUMBER,
                    'fontSize' => 28,
                    'name' => '',
                    'selectType' => 'single',
                    'pointCount' => 10,
                    'sequence' => $index + 1,
                    'answer' => '',
                    'struct_id' => '',
                    'fullScore' => '',
                    'partScore' => '',
                    'errorScore' => '',
                    'content' => '',
                    'x1' => 2 * $x1 + 2 * $stepWidth * $index,
                    'y1' => 2 * $y1,
                    'x2' => 2 * $x2 + 2 * $stepWidth * $index,
                    'y2' => 2 * $y2,
                    'x' => '',
                    'y' => '',
                ],
                'items' => static::examNumPoints(
                    $x1 + $stepWidth * $index,
                    $y1,
                    $x1 + $stepWidth * ($index + 1),
                    $y2,
                    $card->pointWidth,
                    $card->pointHeight
                ),
            ];
        }
        return $res;
    }

    /**
     * @param CardImage $card
     * @param $enableUseLength
     * @return array
     */
    private static function getExamNumberOmrNine(&$card, $enableUseLength)
    {
        $res = [];
        $headers = Header::createArray($card);
        $offsetY = 0;
        if ($card->columns == 3) {
            $offsetX = 319;
            $offsetY = 107;
        } else {
            $offsetX = $card->examNumCount == 9 ? 619 : 671;
        }
        $stepWidth = $card->examNumBoxWidth / 2;
        $left = $card->left / 2;
        $x1 = (int)$left + $offsetX;
        $x2 = (int)$left + $card->contentWidth / 2;
        $y1 = 260 + $offsetY;
        $y2 = 482 + $offsetY;
        $offset = ($enableUseLength && $headers['examNumberUseLength'])
            ? (9 - $headers['examNumberUseLength'])
            : (9 - $headers['examNumberLength']);
        for ($index = $offset; $index < 9; $index++) {
            $res[] = [
                'attributes' => [
                    'used' => CardService::OMR_USED_NUMBER,
                    'fontSize' => 28,
                    'name' => '',
                    'selectType' => 'single',
                    'pointCount' => 10,
                    'sequence' => $index + 1,
                    'answer' => '',
                    'struct_id' => '',
                    'fullScore' => '',
                    'partScore' => '',
                    'errorScore' => '',
                    'content' => '',
                    'x1' => 2 * $x1 + 2 * $stepWidth * $index,
                    'y1' => 2 * $y1,
                    'x2' => 2 * $x2 + 2 * $stepWidth * $index,
                    'y2' => 2 * $y2,
                    'x' => '',
                    'y' => '',
                ],
                'items' => static::examNumPoints(
                    $x1 + $stepWidth * $index,
                    $y1,
                    $x1 + $stepWidth * ($index + 1),
                    $y2,
                    $card->pointWidth,
                    $card->pointHeight
                ),
            ];
        }
        return $res;
    }
}
