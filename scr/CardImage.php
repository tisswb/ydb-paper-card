<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-11
 * Time: 10:16
 */

namespace ydb\card;

use common\base\OssHelper;
use common\models\instance\CardEditArea;
use common\models\instance\CardPage;
use common\service\OssFileService;
use ydb\card\components\CodeInfo;
use ydb\card\components\StructInfo;
use ydb\card\components\ContainerInfo;
use ydb\card\components\Header;
use ydb\card\components\HelpPoint;
use ydb\card\components\OcrInfo;
use ydb\card\components\OmrInfo;
use ydb\card\components\SecretTag;
use ydb\card\components\TextInfo;
use ydb\card\components\WarningInfo;
use Imagick;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class CardImage
 * @package common\card
 *
 * @property string $columnLine
 * @property array $cardConfig
 */
class CardImage extends BaseObject
{
    public $helpPointWidth = 44;
    public $selectionContentWidth = 120;
    public $pointWidth = 53;
    public $pointHeight = 28;
    public $examNumBoxWidth;
    public $examNumCount;

    public $cardPageId;
    /** @var CardPage $pageModel */
    public $pageModel;
    public $columns;
    public $gutter;

    public $format;
    public $color;
    public $resizeTo;

    public $pageNum;

    public $width;
    public $height;
    public $top;
    public $left;
    public $right;
    public $bottom;
    public $center;
    public $contentWidth;
    public $headHeight;

    public $textFont;
    public $textFontContent;
    public $textFont2;
    public $imageDir;
    public $colorBlack;
    public $colorRed;
    public $colorTransparent;
    public $colorForbid;
    // /** @var RemoteWebDriver $driver */
    // public $driver;

    public $file;
    public $fileGray;
    public $filePath;
    public $fileName;
    public $fileGrayName;

    /** @var Imagick $im */
    public $im;

    public function init()
    {
        parent::init();
        if (!($this->pageModel instanceOf CardPage)) {
            \Yii::error('need set pageModel params');
        }

        $this->pageNum = $this->pageModel->order;

        if ($this->columns == CardService::COLUMN_ONE) {
            $this->initColumnOne();
        } elseif ($this->columns == CardService::COLUMN_TWO) {
            $this->initColumnTwo();
        } elseif ($this->columns == CardService::COLUMN_THREE) {
            $this->initColumnThree();
        } else {
            \Yii::error('need set columns params');
            \Yii::$app->end();
        }

        $this->examNumCount = ArrayHelper::getValue(
            $this->pageModel->getPaper()->getCardConfig(),
            'examNumberLength',
            9);
        $this->examNumBoxWidth = $this->examNumCount > 9 ? 74 : 92;

        $this->im = new Imagick();
        $this->format = $this->format ?? 'jpg';
        $this->color = $this->color ?? 'black';
        $this->im->newImage($this->width, $this->height, '#FFFFFF', $this->format);
        $this->im->setImageUnits(\imagick::RESOLUTION_PIXELSPERINCH);
        $this->im->setImageResolution(300, 300);

        $this->imageDir = \Yii::getAlias('@app/data/' . $this->color);
        $this->textFont = \Yii::getAlias('@app/data/simsun.ttc');
        $this->textFontContent = \Yii::getAlias('@app/data/msyh.ttf');
        $this->textFont2 = \Yii::getAlias('@app/data/simfang.ttf');

        if ($this->color == 'black') {
            $this->colorBlack = new \ImagickPixel('black');
            $this->colorRed = new \ImagickPixel('black');
            $this->colorTransparent = new \ImagickPixel('transparent');
            $this->colorForbid = new \ImagickPixel('#E9E9E9');
        } else {
            $this->colorBlack = new \ImagickPixel('black');
            $this->colorRed = new \ImagickPixel('red');
            $this->colorTransparent = new \ImagickPixel('transparent');
            $this->colorForbid = new \ImagickPixel('#FFF0F0');
        }

        $this->file = $this->fileGray = '';
        $this->filePath = \Yii::getAlias("@runtime/");
        $this->fileName = OssFileService::getPaperCardImageFilename(
            $this->pageModel->getPaper()->exam_id,
            $this->pageModel->getPaper()->course_id,
            $this->resizeTo,
            $this->pageModel->order,
            $this->format
        );
        $this->fileGrayName = OssFileService::getPaperCardGrayImageFilename(
            $this->pageModel->getPaper()->exam_id,
            $this->pageModel->getPaper()->course_id,
            $this->resizeTo,
            $this->pageModel->order,
            'jpg'
        );
    }

    /**
     * 一栏答题卡初始化配置
     */
    public function initColumnOne()
    {
        $this->width = CardService::CARD_A4_WIDTH;
        $this->height = CardService::CARD_A4_HEIGHT;
        $this->top = 120;
        $this->bottom = 100;
        $this->center = 0;
        $this->columns = 1;
        $this->contentWidth = 2168;
        $this->headHeight = 810;
        $this->left = 156;
        $this->right = 156;
    }

    /**
     * 两栏栏答题卡初始化配置
     */
    public function initColumnTwo()
    {
        $this->width = CardService::CARD_A3_WIDTH;
        $this->height = CardService::CARD_A3_HEIGHT;
        $this->top = 120;
        $this->left = 212;
        $this->right = 213;
        $this->bottom = 100;
        $this->center = 200;
        $this->columns = 2;
        $this->contentWidth = 2168;
        $this->headHeight = 812;
        if ($this->gutter == YES) {
            if ($this->pageNum%2 == 1) {
                $this->left = 212 + CardService::GUTTER_OFFSET;
                $this->right = 213 - CardService::GUTTER_OFFSET;
            } else {
                $this->left = 212 - CardService::GUTTER_OFFSET;
                $this->right = 213 + CardService::GUTTER_OFFSET;
            }
        }
    }

    /**
     * 三栏答题卡初始化配置
     */
    public function initColumnThree()
    {
        $this->width = CardService::CARD_A3_WIDTH;
        $this->height = CardService::CARD_A3_HEIGHT;
        $this->top = 120;
        $this->left = 212;
        $this->right = 213;
        $this->bottom = 100;
        $this->center = 60;
        $this->columns = 3;
        $this->contentWidth = 1472;
        $this->headHeight = 1070;
        if ($this->gutter == YES) {
            if ($this->pageNum%2 == 1) {
                $this->left = 212 + CardService::GUTTER_OFFSET;
                $this->right = 213 - CardService::GUTTER_OFFSET;
            } else {
                $this->left = 212 - CardService::GUTTER_OFFSET;
                $this->right = 213 + CardService::GUTTER_OFFSET;
            }
        }
    }

    /**
     * @return bool|string
     * @throws \ImagickException
     * @throws \yii\base\InvalidConfigException
     */
    public function saveCard()
    {
        $this->drawImage();

        $this->file = $this->filePath . $this->fileName;
        $path = dirname($this->file);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (in_array($this->resizeTo, ['B4', '8K'])) {
            $width = $height = $offset = 0;
            if ($this->resizeTo == 'B4') {
                $width = CardService::CARD_B4_WIDTH;
                $height = CardService::CARD_B4_HEIGHT;
                $offset = CardService::CARD_B4_OFFSET;
            } elseif ($this->resizeTo == '8K') {
                $width = CardService::CARD_8K_WIDTH;
                $height = CardService::CARD_8K_HEIGHT;
                $offset = CardService::CARD_8K_OFFSET;
            }
            $newImage = new Imagick();
            $newImage->newImage($width, $height, '#FFFFFF', $this->format);
            $newImage->setImageUnits(\imagick::RESOLUTION_PIXELSPERINCH);
            $newImage->setImageResolution(300, 300);

            $newWidth = (int)(CardService::CARD_A3_WIDTH * $height / CardService::CARD_A3_HEIGHT);
            $localim = new Imagick();
            $localim->readImageBlob($this->im);
            if ($width && $height) {
                $localim->thumbnailImage($newWidth, $height);
            }

            $newImage->compositeImage($localim, Imagick::COMPOSITE_OVER, $offset, 0);

            if ($newImage->writeImage($this->file)) {
                return $this->file;
            } else {
                $this->file = '';
                \Yii::error('create image error');
                return false;
            }
        } else {
            if ($this->im->writeImage($this->file)) {
                return $this->file;
            } else {
                $this->file = '';
                \Yii::error('create image error');
                return false;
            }
        }
    }

    /**
     * @param $showContent
     * @return array
     */
    public function pageArray($showContent)
    {
        $items = [];
        $helpPoints = HelpPoint::createArray($this);
        if (!empty($helpPoints)) {
            $items['helpPoints'] = $helpPoints;
        }

        $omr = OmrInfo::createArray($this, true, false);
        if (!empty($omr)) {
            $items['omrInfo'] = $omr;
        }

        $ocr = OcrInfo::createArray($this);
        if (!empty($ocr)) {
            $items['ocrInfo'] = $ocr;
        } else {
            $structInfo = StructInfo::createArray($this);
            if (!empty($structInfo)) {
                $items['structInfo'] = $structInfo;
            }
        }

        $area = TextInfo::createArray($this, $showContent);
        if (!empty($area)) {
            $items['textInfo'] = $area;
        }

        // $pageCode = PageCode::createArray($this);
        // if (!empty($pageCode)) {
        //     $items['pageCode'] = $pageCode;
        // }

        $header = Header::createArray($this);
        if ($header['examNoType'] != 'fill') {
            $codeInfo = CodeInfo::createArray($this);
            $items['codeInfo'] = $codeInfo;
        }

        $cardConfig = $this->pageModel->getPaper()->getCardConfig();
        $attributes = [
            'sheetIndex' => ceil($this->pageNum / 2),
            'pageIndex' => $this->pageNum,
            'faceAB' => ($this->pageNum % 2 == 1) ? 'A' : 'B',
            'courseCode' => $this->pageModel->getPaper()->course_id,
            'colorImageUrl' => $this->pageModel->image_url,
            'grayImageUrl' => $this->pageModel->image_gray_url,
            'paperType' => $cardConfig['pageType'] ?? CardService::CARD_TYPE_A4,
            'outDpi' => 150,
            'imgDpi' => 300,
            'columns' => $cardConfig['pageColumn'],
            'columnLine' => $this->getColumnLine(),
            'width' => $this->width,
            'height' => $this->height,
            'hasHeader' => 0,
        ];

        if (!empty($header)) {
            // 卷首区域
            $headerX1 = $this->left;
            $headerY1 = $this->top + $this->helpPointWidth;
            $headerX2 = $headerX1 + $this->contentWidth;
            $headerY2 = $headerY1 + $this->headHeight + $this->helpPointWidth;
            $attributes = ArrayHelper::merge(
                $attributes, [
                    'hasHeader' => 1,
                    'x1' => $headerX1,
                    'y1' => $headerY1,
                    'x2' => $headerX2,
                    'y2' => $headerY2,
                ]
            );
        }
        return [
            'items' => $items,
            'attributes' => $attributes,
        ];
    }

    /**
     * @return bool|string
     * @throws \ImagickException
     * @throws \yii\base\InvalidConfigException
     */
    public function saveMarkingTemplate()
    {
        if ($this->file == '') {
            $this->saveCard();
        }
        $markImage = new Imagick();
        $markImage->readImage($this->file);
        $width = $markImage->getImageWidth();
        $height = $markImage->getImageHeight();
        $markImage->thumbnailImage($width / 2, $height / 2);
        $markImage->modulateImage(100, 0, 100);
        $this->fileGray = $this->filePath . $this->fileGrayName;
        $path = dirname($this->fileGray);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if ($markImage->writeImage($this->fileGray)) {
            return $this->fileGray;
        } else {
            $this->fileGray = '';
            \Yii::error('create image error');
            return false;
        }
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function drawImage()
    {
        if ($this->columns == CardService::COLUMN_ONE || $this->columns == CardService::COLUMN_TWO) {
            // PageCode::draw($this);
            HelpPoint::draw($this);
            WarningInfo::draw($this);
            SecretTag::draw($this);
            Header::drawWide($this);
            ContainerInfo::draw($this);
            OmrInfo::draw($this);
            TextInfo::draw($this);
            ContainerInfo::drawImageAndLine($this);
            OcrInfo::draw($this);
        } else {
            // PageCode::draw($this);
            HelpPoint::draw($this);
            WarningInfo::draw($this);
            SecretTag::draw($this);
            Header::drawNarrow($this);
            ContainerInfo::draw($this);
            OmrInfo::draw($this);
            TextInfo::draw($this);
            ContainerInfo::drawImageAndLine($this);
            OcrInfo::draw($this);
        }
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function image()
    {
        $this->drawImage();
        header("Content-Type: image/png");
        echo $this->im->getImageBlob();
        exit;
    }

    /**
     * @param $image
     * @param $x
     * @param $y
     * @param int $width
     * @param int $height
     * @throws \ImagickException
     */
    public function composeImage($image, $x, $y, $width = 0, $height = 0)
    {
        $localim = new Imagick();
        $localim->readImageBlob($image);
        if ($width && $height) {
            $localim->thumbnailImage($width, $height);
        }
        $localim->setImageFormat("png24");
        $this->im->compositeImage($localim, Imagick::COMPOSITE_OVER, $x, $y);
    }

    /**
     * @param $svgFile
     * @param $x
     * @param $y
     * @throws \ImagickException
     */
    public function composeSVG($svgFile, $x, $y)
    {
        $localim = new Imagick();
        $svg = file_get_contents($svgFile);
        $localim->setFont($this->textFont);
        $localim->readImageBlob($svg);
        $localim->setImageFormat("png24");
        $localim->resizeImage(
            $localim->getImageWidth(),
            $localim->getImageHeight(),
            imagick::FILTER_LANCZOS, 1
        ); /*改变大小*/
        $this->im->compositeImage($localim, Imagick::COMPOSITE_OVER, $x, $y);
    }

    /**
     * @return array
     */
    public function getCardConfig()
    {
        return $this->pageModel->getPaper()->getCardConfig();
    }

    /**
     * @param $areaId
     * @param $showScore
     * @return string
     */
    public function capture($areaId, $showScore)
    {
        $areaUrl = urlencode(DOMAIN_EXAM . '/card/area?areaId=' . $areaId
            . '&examId=' . $this->pageModel->getPaper()->exam_id
            . '&showScore=' . $showScore
            . '&color=' . $this->color
            . '&time=' . time());
        $area = CardEditArea::findOne($areaId);
        $width = (string)($area->getWidth() * 2);
        $height = (string)($area->getHeight() * 2);
        \Yii::error("area {$areaId} url: {$width}|{$height}|" . urldecode($areaUrl));
        $url = CARD_CAPTURE_HOST . "/card/screenshot?url={$areaUrl}&width={$width}&height={$height}";
        $content = OssHelper::downloadFile($url);
        // OssHelper::writeFile($this->filePath . '/logs/' . $areaId . '.png', base64_decode($content));
        if ($content === false) {
            \Yii::error('area capture error');
            return '';
        } else {
            return base64_decode($content);
        }
    }

    /**
     * @return string
     */
    public function getColumnLine()
    {
        $res = [];
        for ($idx = 1; $idx <= $this->columns; $idx++) {
            $res[] = $this->left + ($this->contentWidth + $this->center) * ($idx - 1) - 10;
        }
        return implode(',', $res);
    }
}
