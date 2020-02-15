<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-12
 * Time: 13:38
 */

namespace ydb\card;

use ydb\card\helper\ArrayFormat;
use ydb\card\helper\ArrayResize;
use common\models\base\CourseStage;
use common\models\exam\Exam;
use common\models\instance\CardPage;
use common\models\instance\ExamPaper;
use SimpleXMLElement;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

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

    /**
     * @param int $paperId
     * @param string $to
     * @return string
     * @throws \Exception
     */
    public static function markingXml($paperId, $to = '')
    {
        $card = simplexml_load_string(static::baseTemplate());
        $array = static::prepareArray($paperId);
        if (in_array($to, [static::CARD_TYPE_8K, static::CARD_TYPE_B4])) {
            ArrayResize::transformPageAttr($array, $to);
            ArrayResize::transformArray($array, 'A3', $to);
        }
        static::arrayToXML($card, $array);
        return $card->asXML();
    }

    /**
     * @param $paperId
     * @return array
     */
    public static function prepareArray($paperId)
    {
        $array = static::paperArray($paperId, false);
        ArrayFormat::mergeArea($array);
        if (static::isYouhen($paperId)) {
            ArrayFormat::relatedOcr($array);
        } else {
            ArrayFormat::relatedStruct($array);
        }
        return $array;
    }

    /**
     * @param $paperId
     * @param $showContent
     * @return array
     */
    public static function paperArray($paperId, $showContent)
    {
        $pageItems = [];
        $pageCards = CardPage::find()
            ->andWhere(['exam_paper_id' => $paperId])
            ->orderBy('order')
            ->all();
        $paperConfig = ExamPaper::cardConfig($paperId);
        $cardType = $cardWidth = $cardHeight = $courseId = '';
        $gutter = $paperConfig['showGutterLine'] ?? 0;
        foreach ($pageCards as $pageCard) {
            $card = new CardImage([
                'pageModel' => $pageCard,
                'columns' => $paperConfig['pageColumn'],
                'gutter' => $gutter
            ]);
            $pageItems[] = $card->pageArray($showContent);
            $cardType = empty($cardType) ? $paperConfig['pageType'] : $cardType;
            $cardWidth = empty($cardWidth) ? $card->width : $cardWidth;
            $cardHeight = empty($cardHeight) ? $card->height : $cardHeight;
            $courseId = empty($courseId) ? $card->pageModel->getPaper()->course_id : $courseId;
        }
        $count = count($pageCards);
        if ($count % 2 == 1) {
            $pageItems[] = static::getBlankPage($count + 1, $cardType, $cardWidth, $cardHeight, $courseId);
        }
        $pageAttributes = static::getPaperAttributes($paperId);
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
     * @return array|bool
     */
    public static function getPaperAttributes($paperId)
    {
        $paper = ExamPaper::findOne($paperId);
        $exam = Exam::findOne($paper->exam_id);
        $pageCount = CardPage::pageCount($paperId);
        $courseName = CourseStage::find()
                ->select('display_name')
                ->andWhere(['identifier' => $paper->course_id])
                ->scalar() ?? '';
        return [
            'paperId' => $paperId,
            'courseCode' => $paper->course_id,
            'courseName' => $courseName,
            'examType' => ($exam->review_type == Exam::REVIEW_YOUHEN) ? 'youhen' : 'wuhen',
            'pageCount' => $pageCount, //页
            'sheetCount' => ceil($pageCount / 2), //张
        ];
    }

    /**
     * @param $paperId
     * @return bool
     */
    public static function isYouhen($paperId)
    {
        $paperAttributes = static::getPaperAttributes($paperId);
        return $paperAttributes['examType'] == 'youhen';
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
    public static function baseTemplate()
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<package>
</package>
XML;
    }
}
