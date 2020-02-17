<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 15:04
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use ImagickDraw;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class HelpPoint
 * @package common\card\components
 */
class HelpPoint extends BaseObject
{
    /**
     * @param CardImage $card
     * @return bool|ImagickDraw
     */
    public static function draw(&$card)
    {
        $draw = new ImagickDraw();
        $draw->setFillColor($card->colorBlack);
        $helpPoints = ArrayHelper::getValue(
            static::createArray($card, true), ['items.point']
        );
        if (empty($helpPoints)) {
            return false;
        }
        foreach ($helpPoints as $point) {
            $draw->rectangle(
                $point['attributes']['x1'], $point['attributes']['y1'],
                $point['attributes']['x2'], $point['attributes']['y2']
            );
        }
        return $card->im->drawImage($draw);
    }

    /**
     * @param CardImage $card
     * @param bool $forDraw
     * @return mixed
     */
    public static function createArray(&$card, $forDraw = false)
    {
        $pagePoints = [];
        $onlyDrawPoints = [];
        $base = [
            [
                'attributes' => [
                    'isMain' => $card->pageNum % 2 ? 1 : 0,
                    'x1' => $card->left,
                    'y1' => $card->top,
                    'x2' => $card->left + $card->helpPointWidth,
                    'y2' => $card->top + $card->helpPointWidth,
                ],
            ],
            [
                'attributes' => [
                    'isMain' => 0,
                    'x1' => $card->width - $card->right - $card->helpPointWidth,
                    'y1' => $card->height - $card->bottom - $card->helpPointWidth,
                    'x2' => $card->width - $card->right,
                    'y2' => $card->height - $card->bottom,
                ],
            ],
        ];

        if ($forDraw) {
            // forDraw中的标记点只绘制，不在xml中返回数据
            $onlyDrawPoints = [
                [
                    'attributes' => [
                        'isMain' => 0,
                        'x1' => $card->left,
                        'y1' => $card->height - $card->bottom - $card->helpPointWidth,
                        'x2' => $card->left + $card->helpPointWidth,
                        'y2' => $card->height - $card->bottom,
                    ],
                ],
                [
                    'attributes' => [
                        'isMain' => 0,
                        'x1' => $card->width - $card->right - $card->helpPointWidth,
                        'y1' => $card->top,
                        'x2' => $card->width - $card->right,
                        'y2' => $card->top + $card->helpPointWidth,
                    ],
                ],
            ];
            if ($card->columns - 1) {
                for ($pos = 1; $pos <= $card->columns - 1; $pos++) {
                    $onlyDrawPoints[] = [
                        'attributes' => [
                            'isMain' => 0,
                            'x1' => $card->left + ($card->contentWidth + $card->center) * $pos - ($card->helpPointWidth + $card->center) / 2,
                            'y1' => $card->height - $card->bottom - $card->helpPointWidth,
                            'x2' => $card->left + ($card->contentWidth + $card->center) * $pos - ($card->helpPointWidth + $card->center) / 2 + $card->helpPointWidth,
                            'y2' => $card->height - $card->bottom,
                        ],
                    ];
                }

            }
        }

        if ($card->pageNum % 2 === 1) {
            $pageNum = ceil($card->pageNum / 2);
            for ($pos = 1; $pos <= $pageNum; $pos++) {
                $pagePoints[] = [
                    'attributes' => [
                        'isMain' => 1,
                        'x1' => $card->left + 2 * $pos * $card->helpPointWidth,
                        'y1' => $card->top,
                        'x2' => $card->left + (2 * $pos + 1) * $card->helpPointWidth,
                        'y2' => $card->top + $card->helpPointWidth,
                    ],
                ];
            }
        }

        $res['items']['point'] = ArrayHelper::merge($base, $onlyDrawPoints, $pagePoints);
        return $res;
    }
}
