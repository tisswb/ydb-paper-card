<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-12
 * Time: 17:03
 */

namespace ydb\card\helper;

use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class ArrayFormat
 * @package common\card\helper
 */
class ArrayFormat extends BaseObject
{
    /**
     * @param $array
     */
    public static function mergeArea(&$array)
    {
        $paperType = ArrayHelper::getValue($array, ['attributes.examType']);
        $pages = ArrayHelper::getValue($array, ['items.pages.items.page']);
        $outId = 1;
        foreach ($pages as $index => $page) {
            $areaPoints = [];
            $pageAreas = ArrayHelper::getValue($page, ['items.textInfo.items.area']);
            $pageOcrs = ArrayHelper::getValue($page, ['items.ocrInfo.items.ocr']);
            $columnLine = ArrayHelper::getValue($page,['attributes.columnLine']);
            if (is_array($pageAreas)) {
                $areaAttributes = ArrayHelper::getColumn($pageAreas, 'attributes');
                foreach ($areaAttributes as $areaAttribute) {
                    $areaPoints = ArrayHelper::merge(
                        $areaPoints,
                        [
                            ArrayHelper::filter($areaAttribute,
                                ['struct_id', 'name', 'pageIndex', 'x1', 'y1', 'x2', 'y2']),
                        ]
                    );
                }
            }
            $areaPoints = ArrayHelper::index($areaPoints, null, 'struct_id');
            if (is_array($pageOcrs)) {
                $areas = $allStructIds = [];
                foreach ($pageOcrs as $ocrIndex => $ocr) {
                    $structIds = explode(',', $ocr['attributes']['related_struct']);
                    $allStructIds = ArrayHelper::merge($allStructIds, $structIds);
                    $points = [];
                    $points[] = ArrayHelper::filter($ocr['attributes'], ['x1', 'y1', 'x2', 'y2']);
                    foreach ($structIds as $structId) {
                        if (isset($areaPoints[$structId])) {
                            $points = ArrayHelper::merge($points, $areaPoints[$structId]);
                        }
                    }
                    [$area, $outId] = static::computeBlock($points, $outId, $columnLine, $paperType);
                    $areas = ArrayHelper::merge($areas, $area);
                }
                foreach ($areaPoints as $key => $areaPoint) {
                    if (!in_array($key, $allStructIds)) {
                        [$area, $outId] = static::computeBlock($areaPoint, $outId, $columnLine, $paperType);
                        $areas = ArrayHelper::merge($areas, $area);
                    }
                }
                ArrayHelper::setValue(
                    $array,
                    "items.pages.items.page.{$index}.items.textInfo.items.area",
                    $areas
                );
            } else {
                if (is_array($pageAreas)) {
                    $areas = [];
                    foreach ($pageAreas as $pageArea) {
                        [$area, $outId] = static::computeBlock($pageArea, $outId, $columnLine, $paperType);
                        $areas = ArrayHelper::merge($areas, $area);
                    }
                    ArrayHelper::setValue(
                        $array,
                        "items.pages.items.page.{$index}.items.textInfo.items.area",
                        $areas
                    );
                }

            }
        }
    }

    /**
     * @param $array
     */
    public static function relatedOcr(&$array)
    {
        $pages = ArrayHelper::getValue($array, ['items.pages.items.page']);
        $areaPoints = [];
        foreach ($pages as $page) {
            $pageAreas = ArrayHelper::getValue($page, ['items.textInfo.items.area']);
            if (is_array($pageAreas)) {
                $areaAttributes = ArrayHelper::getColumn($pageAreas, 'attributes');
                foreach ($areaAttributes as $areaAttribute) {
                    $areaPoints = ArrayHelper::merge(
                        $areaPoints,
                        [ArrayHelper::filter($areaAttribute, ['struct_id', 'outId'])]
                    );
                }
            }
        }
        $areaMap = [];
        foreach ($areaPoints as $areaPoint) {
            $structs = explode(',', $areaPoint['struct_id']);
            foreach ($structs as $struct) {
                $areaMap[$struct][] = $areaPoint['outId'];
            }
        }
        foreach ($pages as $index => $page) {
            $pageOcrs = ArrayHelper::getValue($page, ['items.ocrInfo.items.ocr']);
            if (is_array($pageOcrs)) {
                foreach ($pageOcrs as $ocrIndex => $ocr) {
                    $relatedOutId = [];
                    $ocrStructIds = explode(',', $ocr['attributes']['related_struct']);
                    foreach ($ocrStructIds as $structId) {
                        $relatedOutId = ArrayHelper::merge($relatedOutId,
                            $areaMap[$structId] ?? []);
                    }
                    ArrayHelper::setValue(
                        $array,
                        "items.pages.items.page.{$index}.items.ocrInfo.items.ocr.{$ocrIndex}.attributes.relatedOutId",
                        implode(',', array_unique($relatedOutId))
                    );
                }
            }
        }
    }

    /**
     * @param $array
     */
    public static function relatedStruct(&$array)
    {
        $pages = ArrayHelper::getValue($array, ['items.pages.items.page']);
        $areaPoints = [];
        foreach ($pages as $page) {
            $pageAreas = ArrayHelper::getValue($page, ['items.textInfo.items.area']);
            if (is_array($pageAreas)) {
                $areaAttributes = ArrayHelper::getColumn($pageAreas, 'attributes');
                foreach ($areaAttributes as $areaAttribute) {
                    $areaPoints = ArrayHelper::merge(
                        $areaPoints,
                        [ArrayHelper::filter($areaAttribute, ['struct_id', 'outId'])]
                    );
                }
            }
        }
        $areaMap = [];
        foreach ($areaPoints as $areaPoint) {
            $structs = explode(',', $areaPoint['struct_id']);
            foreach ($structs as $struct) {
                $areaMap[$struct][] = $areaPoint['outId'];
            }
        }
        foreach ($pages as $index => $page) {
            $pageOcrs = ArrayHelper::getValue($page, ['items.structInfo.items.struct']);
            if (is_array($pageOcrs)) {
                foreach ($pageOcrs as $structIndex => $struct) {
                    $relatedOutId = [];
                    $structIds = explode(',', $struct['attributes']['related_struct']);
                    foreach ($structIds as $structId) {
                        if (isset($areaMap[$structId])) {
                            $relatedOutId = ArrayHelper::merge($relatedOutId, $areaMap[$structId]);
                            unset($areaMap[$structId]);
                        }
                    }
                    if (empty($relatedOutId)) {
                        unset($array['items']['pages']['items']['page'][$index]['items']['structInfo']['items']['struct'][$structIndex]);
                    } else {
                        ArrayHelper::setValue(
                            $array,
                            "items.pages.items.page.{$index}.items.structInfo.items.struct.{$structIndex}.attributes.relatedOutId",
                            implode(',', array_unique($relatedOutId))
                        );
                    }

                }
            }
            if (empty($array['items']['pages']['items']['page'][$index]['items']['structInfo']['items']['struct'])) {
                unset($array['items']['pages']['items']['page'][$index]['items']['structInfo']);
            } else {
                $array['items']['pages']['items']['page'][$index]['items']['structInfo']['items']['struct']
                    = array_values($array['items']['pages']['items']['page'][$index]['items']['structInfo']['items']['struct']);
            }
        }
    }

    /**
     * @param $array
     * @param $outId
     * @param $columnLine
     * @param $paperType
     * @return array
     */
    public static function computeBlock($array, $outId, $columnLine, $paperType)
    {
        if ($paperType == 'wuhen') {
            $blank = [
                'x1' => 20, //左
                'y1' => 40, //上
                'x2' => 20, //右
                'y2' => 0, //下
            ];
        } elseif ($paperType == 'youhen') {
            $blank = [
                'x1' => 45, //左
                'y1' => 50, //上
                'x2' => 45, //右
                'y2' => 45, //下
            ];
        } else {
            $blank = [
                'x1' => 45, //左
                'y1' => 45, //上
                'x2' => 45, //右
                'y2' => 45, //下
            ];
        }

        $areaGroups = $res = [];
        $lines = explode(',', $columnLine);
        $count = count($lines);
        foreach ($array as $point) {
            for($idx = $count - 1; $idx >= 0; $idx--) {
                if ($point['x1'] > $lines[$idx]) {
                    $areaGroups[$idx][] = $point;
                    break;
                }
            }
        }
        foreach ($areaGroups as $areaGroup) {
            if (!empty($areaGroup)) {
                $res[]['attributes'] = [
                    'struct_id' => implode(',', array_filter(
                        ArrayHelper::getColumn($areaGroup, 'struct_id')
                    )),
                    'name' => implode(',', array_filter(
                        ArrayHelper::getColumn($areaGroup, 'name')
                    )),
                    'pageIndex' => implode(',', array_unique(array_filter(
                        ArrayHelper::getColumn($areaGroup, 'pageIndex')
                    ))),
                    'x1' => min(ArrayHelper::getColumn($areaGroup, 'x1')) - $blank['x1'],
                    'y1' => min(ArrayHelper::getColumn($areaGroup, 'y1')) - $blank['y1'],
                    'x2' => max(ArrayHelper::getColumn($areaGroup, 'x2')) + $blank['x2'],
                    'y2' => max(ArrayHelper::getColumn($areaGroup, 'y2')) + $blank['y2'],
                    'outId' => $outId,
                ];
                $outId++;
            }
        }
        return [$res, $outId];
    }
}
