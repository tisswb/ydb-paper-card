<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-12
 * Time: 17:06
 */

namespace ydb\card\helper;

use common\card\CardService;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class ArrayResize
 * @package card\helper
 */
class ArrayResize extends BaseObject
{
    /**
     * @param $array
     * @param $to
     */
    public static function transformPageAttr(&$array, $to)
    {
        $pages = ArrayHelper::getValue($array, ['items.pages.items.page']);
        foreach ($pages as $index => $page) {
            $pageAttrs = $page['attributes'];
            $pageAttrs['paperType'] = $to;
            if ($to === CardService::CARD_TYPE_B4) {
                $pageAttrs['width'] = CardService::CARD_B4_WIDTH;
                $pageAttrs['height'] = CardService::CARD_B4_HEIGHT;
            } elseif ($to === CardService::CARD_TYPE_8K) {
                $pageAttrs['width'] = CardService::CARD_8K_WIDTH;
                $pageAttrs['height'] = CardService::CARD_8K_HEIGHT;
            }
            ArrayHelper::setValue(
                $array,
                "items.pages.items.page.{$index}.attributes",
                $pageAttrs
            );
        }
    }

    /**
     * @param $array
     * @param $from
     * @param $to
     */
    public static function transformArray(& $array, $from, $to)
    {
        foreach ($array as $key => $item) {
            if ($key === 'attributes') {
                foreach ($item as $subKey => $subItem) {
                    if (in_array($subKey, ['x', 'x1', 'x2'])) {
                        $func = $from . 'To' . $to . 'X';
                        $array[$key][$subKey] = call_user_func(
                            __NAMESPACE__ . '\ArrayResize::' . $func,
                            $subItem,
                            $subKey === 'x2' ? false : true
                        );
                    } elseif (in_array($subKey, ['y', 'y1', 'y2'])) {
                        $func = $from . 'To' . $to . 'Y';
                        $array[$key][$subKey] = call_user_func(
                            __NAMESPACE__ . '\ArrayResize::' . $func,
                            $subItem,
                            $subKey === 'y2' ? false : true
                        );
                    }
                }
            } else {
                if (is_array($item)) {
                    static::transformArray($array[$key], $from, $to);
                }
            }
        }
    }

    /**
     * @param $x
     * @param bool $left
     * @return mixed
     */
    public static function A3ToB4X($x, $left = true)
    {
        $newWidth = (int)(CardService::CARD_A3_WIDTH * CardService::CARD_B4_HEIGHT / CardService::CARD_A3_HEIGHT);
        $realX = (int)((int)$x * $newWidth / CardService::CARD_A3_WIDTH);
        if (!$left) {
            $realX = $realX + 1;
        }
        return $realX + CardService::CARD_B4_OFFSET;
    }

    /**
     * @param $y
     * @param bool $top
     * @return mixed
     */
    public static function A3ToB4Y($y, $top = true)
    {
        $realY = (int)((int)$y * CardService::CARD_B4_HEIGHT / CardService::CARD_A3_HEIGHT);
        if (!$top) {
            $realY = $realY + 1;
        }
        return $realY;
    }

    /**
     * @param $x
     * @param bool $left
     * @return mixed
     */
    public static function A3To8KX($x, $left = true)
    {
        $newWidth = (int)(CardService::CARD_A3_WIDTH * CardService::CARD_8K_HEIGHT / CardService::CARD_A3_HEIGHT);
        $realX = (int)((int)$x * $newWidth / CardService::CARD_A3_WIDTH);
        if (!$left) {
            $realX = $realX + 1;
        }
        return $realX + CardService::CARD_8K_OFFSET;
    }

    /**
     * @param $y
     * @param bool $top
     * @return mixed
     */
    public static function A3To8KY($y, $top = true)
    {
        $realY = (int)((int)$y * CardService::CARD_8K_HEIGHT / CardService::CARD_A3_HEIGHT);
        if (!$top) {
            $realY = $realY + 1;
        }
        return $realY;
    }
}
