<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2018-07-12
 * Time: 13:38
 */

namespace ydb\card;

use ydb\card\helper\ArrayFormat;
use ydb\card\helper\ArrayResize;
use SimpleXMLElement;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class CardService
 * @package common\card
 */
class CardService extends BaseObject
{
    public const OMR_USED_OBJECT = 'object'; // 客观
    public const OMR_USED_NUMBER = 'examNumber'; // 考号
    public const OMR_USED_MISS_TEST = 'missTest'; // 缺考
    public const OMR_USED_VIOLATION = 'violation'; // 违纪

    public const COLUMN_ONE = 1;
    public const COLUMN_TWO = 2;
    public const COLUMN_THREE = 3;

    public const CARD_TYPE_A3 = 'A3';
    public const CARD_TYPE_A4 = 'A4';
    public const CARD_TYPE_B4 = 'B4';
    public const CARD_TYPE_8K = '8K';

    public const CARD_A3_WIDTH = 4961; // 420mm
    public const CARD_A3_HEIGHT = 3508; // 297mm

    public const CARD_A4_WIDTH = 2480; // 210mm
    public const CARD_A4_HEIGHT = 3508; // 297mm

    public const CARD_B4_WIDTH = 3248; // 275mm
    public const CARD_B4_HEIGHT = 2150; // 182mm
    public const CARD_B4_OFFSET = 104;

    public const CARD_8K_WIDTH = 4606; // 390mm
    public const CARD_8K_HEIGHT = 3189; // 270mm
    public const CARD_8K_OFFSET = 48;

    public const ZUOWEN_BIG_LONG_LINE_COUNT = 20;
    public const ZUOWEN_BIG_SHORT_LINE_COUNT = 13;

    public const ZUOWEN_LONG_LINE_COUNT = 23;
    public const ZUOWEN_SHORT_LINE_COUNT = 15;

    public const GUTTER_OFFSET = 90;

    public $courseId;
    public $courseName;

    /**
     * @param $card
     * @param $cardPages
     * @param $courseName
     * @param string $to
     * @return string
     * @throws \Exception
     */
    public static function markingXml($card, $cardPages, $courseName, $to = '')
    {
        $cardXml = simplexml_load_string(static::baseTemplate());
        $array = static::prepareArray($card, $cardPages, $courseName);
        if (in_array($to, [static::CARD_TYPE_8K, static::CARD_TYPE_B4])) {
            ArrayResize::transformPageAttr($array, $to);
            ArrayResize::transformArray($array, 'A3', $to);
        }
        static::arrayToXML($card, $array);
        return $cardXml->asXML();
    }

    /**
     * @param $card
     * @param $cardPages
     * @param $courseName
     * @return array
     */
    private static function prepareArray($card, $cardPages, $courseName)
    {
        $array = static::paperArray($card, $cardPages, $courseName, false);
        ArrayFormat::mergeArea($array);
        if ($card['review_type'] == 1) {
            ArrayFormat::relatedOcr($array);
        } else {
            ArrayFormat::relatedStruct($array);
        }
        return $array;
    }

    /**
     * @param $card
     * @param $cardPages
     * @param $courseName
     * @param $showContent
     * @return array
     */
    private static function paperArray($card, $cardPages, $courseName, $showContent)
    {
        $pageItems = [];
        $cardType = $cardWidth = $cardHeight = '';
        $cardConfig = Json::decode($card['settings']);
        foreach ($cardPages as $cardPage) {
            $cardImage = new CardImage(
                [
                    'card' => $card,
                    'cardPage' => $cardPage,
                    'columns' => $cardConfig['pageColumn'],
                    'gutter' => $cardConfig['showGutterLine'] ?? 0
                ]
            );
            $pageItems[] = $cardImage->pageArray($showContent);
            $cardType = empty($cardType) ? $cardConfig['pageType'] : $cardType;
            $cardWidth = empty($cardWidth) ? $cardImage->width : $cardWidth;
            $cardHeight = empty($cardHeight) ? $cardImage->height : $cardHeight;
        }
        $count = count($cardPages);
        if ($count % 2 == 1) {
            $pageItems[] = static::getBlankPage(
                $count + 1,
                $cardType,
                $cardWidth,
                $cardHeight,
                $card['course_id']
            );
        }
        $pageAttributes = static::buildPaperAttributes(
            $card['paper_id'],
            $card['course_id'],
            $courseName,
            $card['review_type'],
            $count
        );
        return [
            'attributes' => $pageAttributes,
            'items' => [
                'pages' => [
                    'items' => [
                        'page' => $pageItems,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $paperId
     * @param $courseId
     * @param $courseName
     * @param $reviewType
     * @param $pageCount
     * @return array
     */
    private static function buildPaperAttributes(
        $paperId,
        $courseId,
        $courseName,
        $reviewType,
        $pageCount
    ) {
        return [
            'paperId' => $paperId,
            'courseCode' => $courseId,
            'courseName' => $courseName ?? '',
            'examType' => ($reviewType == 1) ? 'youhen' : 'wuhen',
            'pageCount' => $pageCount, //页
            'sheetCount' => ceil($pageCount / 2), //张
        ];
    }

    /**
     * @param $page
     * @param $paperType
     * @param $width
     * @param $height
     * @param $courseId
     * @return array
     */
    private static function getBlankPage($page, $paperType, $width, $height, $courseId)
    {
        $attributes = [
            'sheetIndex' => ceil($page / 2),
            'pageIndex' => $page,
            'faceAB' => ($page % 2 == 1) ? 'A' : 'B',
            'courseCode' => $courseId,
            'colorImageUrl' => DOMAIN_EXAM . '/static/card_img/blank_A3.png',
            'grayImageUrl' => DOMAIN_EXAM . '/static/card_img/gray_blank_A3.jpg',
            'paperType' => $paperType,
            'outDpi' => 150,
            'imgDpi' => 300,
            'width' => $width,
            'height' => $height,
            'hasHeader' => 0,
        ];
        return [
            'items' => [],
            'attributes' => $attributes,
        ];
    }

    /**
     * @param SimpleXMLElement $xml
     * @param $array
     * @param int $start
     * @return SimpleXMLElement
     * @throws \Exception
     */
    public static function arrayToXML(&$xml, $array, &$start = 1)
    {
        if (isset($array['attributes'])) {
            $xml->addAttribute('id', $start);
            $start++;
            foreach ($array['attributes'] as $attributeKey => $attribute) {
                $xml->addAttribute($attributeKey, $attribute);
            }
        }
        if (isset($array['items'])) {
            $itemArray = $array['items'];
            if (is_array($itemArray)) {
                foreach ($itemArray as $itemKey => $item) {
                    if (is_array($item)) {
                        if (ArrayHelper::isAssociative($item)) {
                            static::arrayToXML($xml->addChild($itemKey), $item, $start);
                        } else {
                            foreach ($item as $subItem) {
                                static::arrayToXML($xml->addChild($itemKey), $subItem, $start);
                            }
                        }
                    } else {
                        $xml->addChild($itemKey, $item);
                    }
                }
            } else {
                throw new \Exception('xml $array struct has error');
            }
        }
        return $xml->saveXML();
    }

    /**
     * @return string
     */
    private static function baseTemplate()
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<package>
</package>
XML;
    }
}
