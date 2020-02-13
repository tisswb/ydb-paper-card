<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-12
 * Time: 11:33
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use common\models\instance\CardPage;
use Imagick;
use ImagickDraw;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class Header
 * @package card\components
 */
class Header extends BaseObject
{
    /**
     * 一栏、两栏头部
     * @param CardImage $card
     * @return bool
     */
    public static function drawWide(&$card)
    {
        if ($card->gutter == YES) {
            if ($card->pageNum % 2 == 1) {
                static::drawGutterOne($card);
            } else {
                static::drawGutterTwo($card);
            }
            return static::createWideGutter($card);
        } else {
            return static::createWide($card);
        }
    }

    /**
     * 三栏头部
     * @param CardImage $card
     * @return bool
     */
    public static function drawNarrow(&$card)
    {
        if ($card->gutter == YES) {
            if ($card->pageNum % 2 == 1) {
                static::drawGutterOne($card);
            } else {
                static::drawGutterTwo($card);
            }
            return static::createNarrowGutter($card);
        } else {
            return static::createNarrow($card);
        }
    }

    /**
     * @param CardImage $card
     * @return bool
     */
    protected static function createWide(&$card)
    {
        if ($card->pageNum % 2 == 0) {
            return false;
        }
        $left = $card->left;
        $top = $card->top;
        $headers = static::createArray($card);
        $examNumX = 1134;
        $examNumY = 246;
        if ($headers['examNoType'] == 'fill') {
            $align = $headers['examNumberLength'] == 9 ? 'center' : 'right';
            list($offset, $box) = static::fillExamNumBox(
                $examNumX + $card->left,
                $examNumY + $card->top,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                1036,
                604,
                $align
            );
            $boxTitle = static::examNumTitle(
                $examNumX + $card->left,
                $examNumY + $card->top,
                '准考证号',
                $headers['examNumberLength'] == 9 ? 920 : 1036,
                68,
                $offset,
                $card->textFont
            );
            $card->im->drawImage($box);
            $card->im->drawImage($boxTitle);
        } else {
            list($offset2, $paste) = static::pasteImage(
                $examNumX + $card->left,
                $examNumY + $card->top,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                1038
            );
            $paste_title = static::pasteTitle(
                $examNumX + $card->left,
                $examNumY + $card->top,
                '准考证号',
                79,
                $offset2,
                $card->textFont
            );
            $examNumber = $card->imageDir . '/h_exam_no_paste.svg';
            $card->composeSVG($examNumber, 1121 + $card->left, 248 + $card->top);
            $card->im->drawImage($paste);
            $card->im->drawImage($paste_title);
        }
        if ($card->pageNum == 1) {
            if ($headers['showQrcode']) {
                $qrImage = $card->imageDir . '/qr_image.jpg';
                $card->composeImage(file_get_contents($qrImage), $left + 8, $top + 434);

                $noticeImage = $card->imageDir . '/h_notice_qr.svg';
                $card->composeSVG($noticeImage, $left + 256, $top + 434);
            } else {
                $noticeImage = $card->imageDir . '/h_notice_no_qr.svg';
                $card->composeSVG($noticeImage, $left, $top + 460);
            }
        }
        if ($headers['title']) {
            static::drawPaperTitle($headers['title'], $card);
        }
        $nameImage = $card->imageDir . '/h_name.svg';
        if ($headers['showSchool'] == ACTIVE_YES || $headers['showGradeClass'] == ACTIVE_YES) {
            $card->composeSVG($nameImage, $left, $top + 248);
        } else {
            $card->composeSVG($nameImage, $left, $top + 338);
        }

        if ($headers['showSchool'] == ACTIVE_YES) {
            $img = $card->imageDir . '/h_school.svg';
            $card->composeSVG($img, $left, $top + 338);
        }

        if ($headers['showGradeClass'] == ACTIVE_YES) {
            $img = $card->imageDir . '/h_grade_class.svg';
            $card->composeSVG($img, $left + 630, $top + 338);
        }
        return true;
    }

    /**
     * @param CardImage $card
     * @return bool
     */
    protected static function createWideGutter(&$card)
    {
        if ($card->pageNum % 2 == 0) {
            return false;
        }
        $left = $card->left;
        $top = $card->top;
        $headers = static::createArray($card);
        $examNumX = 1134;
        $examNumY = 246;
        if ($headers['examNoType'] == 'fill') {
            $align = $headers['examNumberLength'] == 9 ? 'center' : 'right';
            list($offset, $box) = static::fillExamNumBox(
                $examNumX + $card->left,
                $examNumY + $card->top,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                1036,
                604,
                $align
            );
            $boxTitle = static::examNumTitle(
                $examNumX + $card->left,
                $examNumY + $card->top,
                '准考证号',
                $headers['examNumberLength'] == 9 ? 920 : 1036,
                68,
                $offset,
                $card->textFont
            );
            $card->im->drawImage($box);
            $card->im->drawImage($boxTitle);
        } else {
            list($offset2, $paste) = static::pasteImage(
                $examNumX + $card->left,
                $examNumY + $card->top,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                1038
            );
            $paste_title = static::pasteTitle(
                $examNumX + $card->left,
                $examNumY + $card->top,
                '准考证号',
                79,
                $offset2,
                $card->textFont
            );
            $examNumber = $card->imageDir . '/h_exam_no_paste.svg';
            $card->composeSVG($examNumber, 1121 + $card->left, 248 + $card->top);
            $card->im->drawImage($paste);
            $card->im->drawImage($paste_title);
        }
        if ($card->pageNum == 1) {
            if ($headers['showQrcode']) {
                $qrImage = $card->imageDir . '/qr_image.jpg';
                $card->composeImage(file_get_contents($qrImage), $left + 8, $top + 248);

                $noticeImage = $card->imageDir . '/h_gutter_notice_qr.svg';
                $card->composeSVG($noticeImage, $left + 256, $top + 248);
            } else {
                $noticeImage = $card->imageDir . '/h_gutter_notice_no_qr.svg';
                $card->composeSVG($noticeImage, $left, $top + 248);
            }
        }
        if ($headers['title']) {
            static::drawPaperTitle($headers['title'], $card);
        }
        return true;
    }

    /**
     * @param CardImage $card
     * @return bool
     */
    protected static function createNarrow(&$card)
    {
        if ($card->pageNum % 2 == 0) {
            return false;
        }
        $left = $card->left;
        $top = $card->top;
        $headers = static::createArray($card);
        $examNumX = 478 + $card->left - $card->helpPointWidth;
        $examNumY = 460 + $card->top;
        if ($headers['examNoType'] == 'fill') {
            $align = $headers['examNumberLength'] == 9 ? 'center' : 'right';
            $width = 1036;
            list($offset, $box) = static::fillExamNumBox(
                $headers['examNumberLength'] == 9 ? $examNumX + 100 : $examNumX,
                $examNumY,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                $width,
                604,
                $align
            );
            $boxTitle = static::examNumTitle(
                $examNumX,
                $examNumY,
                '准考证号',
                $width,
                68,
                $offset,
                $card->textFont
            );
            $card->im->drawImage($box);
            $card->im->drawImage($boxTitle);
        } else {
            list($offset2, $paste) = static::pasteImage(
                $examNumX,
                $examNumY+10,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                1038
            );
            $paste_title = static::pasteTitle(
                $examNumX,
                $examNumY+10,
                '准考证号',
                79,
                $offset2,
                $card->textFont
            );
            $examNumber = $card->imageDir . '/h_exam_no_paste_n.svg';
            $card->composeSVG(
                $examNumber,
                $card->left + $card->contentWidth - 710,
                $examNumY + 140
            );
            $card->im->drawImage($paste);
            $card->im->drawImage($paste_title);
        }
        if ($card->pageNum == 1) {
            if ($headers['examNoType'] == 'fill') {
                if ($headers['examNumberLength'] > 9) {
                    $noticeImage = $card->imageDir . '/h_notice_narrow_n.svg';
                    $card->composeSVG($noticeImage, $left, $top + 461);
                } else {
                    $noticeImage = $card->imageDir . '/h_notice_narrow_w.svg';
                    $card->composeSVG($noticeImage, $left, $top + 461);
                }
                if ($headers['showQrcode']) {
                    $qrImage = $card->imageDir . '/qr_image_narrow_fill.svg';
                    $card->composeImage(
                        file_get_contents($qrImage),
                        $left,
                        248 + $top
                    );
                }

            } elseif ($headers['examNoType'] == 'paste') {
                $noticeImage = $card->imageDir . '/h_notice_narrow_p.svg';
                $card->composeSVG($noticeImage, $left, $top + 600);
                if ($headers['showQrcode']) {
                    $qrImage = $card->imageDir . '/qr_image_narrow_paste.svg';
                    $card->composeImage(
                        file_get_contents($qrImage),
                        $left,
                        278 + $top
                    );
                }
            }
        }
        if ($headers['title']) {
            static::drawPaperTitle($headers['title'], $card);
        }
        $nameImage = $card->imageDir . '/h_name.svg';
        if ($headers['showSchool'] == ACTIVE_YES || $headers['showGradeClass'] == ACTIVE_YES) {
            $card->composeSVG($nameImage, $examNumX, $top + 278);
        } else {
            $card->composeSVG($nameImage, $examNumX, $top + 378);
        }

        if ($headers['showSchool'] == ACTIVE_YES) {
            $img = $card->imageDir . '/h_school.svg';
            $card->composeSVG($img, $examNumX, $top + 378);
        }

        if ($headers['showGradeClass'] == ACTIVE_YES) {
            $img = $card->imageDir . '/h_grade_class.svg';
            $card->composeSVG($img, 630 + $examNumX, $top + 378);
        }
        return true;
    }

    /**
     * @param CardImage $card
     * @return bool
     */
    protected static function createNarrowGutter(&$card)
    {
        if ($card->pageNum % 2 == 0) {
            return false;
        }
        $left = $card->left;
        $top = $card->top;
        $headers = static::createArray($card);
        $examNumX = 478 + $card->left - $card->helpPointWidth;
        $examNumY = 460 + $card->top;
        if ($headers['examNoType'] == 'fill') {
            $align = $headers['examNumberLength'] == 9 ? 'center' : 'right';
            $width = 1036;
            list($offset, $box) = static::fillExamNumBox(
                $headers['examNumberLength'] == 9 ? $examNumX + 100 : $examNumX,
                $examNumY,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                $width,
                604,
                $align
            );
            $boxTitle = static::examNumTitle(
                $examNumX,
                $examNumY,
                '准考证号',
                $width,
                68,
                $offset,
                $card->textFont
            );
            $card->im->drawImage($box);
            $card->im->drawImage($boxTitle);
        } else {
            list($offset2, $paste) = static::pasteImage(
                $examNumX,
                $examNumY+10,
                $card->colorRed,
                $card->colorTransparent,
                $headers['examNumberLength'],
                $card->examNumBoxWidth,
                1038
            );
            $paste_title = static::pasteTitle(
                $examNumX,
                $examNumY+10,
                '准考证号',
                79,
                $offset2,
                $card->textFont
            );
            $examNumber = $card->imageDir . '/h_exam_no_paste_n.svg';
            $card->composeSVG(
                $examNumber,
                $card->left + $card->contentWidth - 710,
                $examNumY + 140
            );
            $card->im->drawImage($paste);
            $card->im->drawImage($paste_title);
        }
        if ($card->pageNum == 1) {
            if ($headers['examNoType'] == 'fill') {
                if ($headers['examNumberLength'] > 9) {
                    $noticeImage = $card->imageDir . '/h_notice_narrow_n.svg';
                    $card->composeSVG($noticeImage, $left, $top + 461);
                } else {
                    $noticeImage = $card->imageDir . '/h_notice_narrow_w.svg';
                    $card->composeSVG($noticeImage, $left, $top + 461);
                }
            } elseif ($headers['examNoType'] == 'paste') {
                $noticeImage = $card->imageDir . '/h_notice_narrow_p.svg';
                $card->composeSVG($noticeImage, $left, $top + 600);
            }
            if ($headers['showQrcode']) {
                static::drawGutterQr($card);
            }
        }
        if ($headers['title']) {
            static::drawPaperGutterTitle($headers['title'], $card);
        }
        return true;
    }

    /**
     * @param CardImage $card
     * @return array
     */
    public static function createArray(&$card)
    {
        if ($card->pageNum % 2 == 1) {
            $cardConfig = $card->getCardConfig();
            if (!empty($cardConfig)) {
                return [
                    'hasHeader' => true,
                    'title' => $cardConfig['title'] . "\n" . $cardConfig['shortTitle'],
                    'pageCount' => CardPage::pageCount($card->pageModel->getPageCount()),
                    'paperType' => $cardConfig['pageType'],
                    'examNoType' => $cardConfig['examNoType'],
                    'examNumberLength' => ArrayHelper::getValue($cardConfig, 'examNumberLength', 9),
                    'examNumberUseLength' => ArrayHelper::getValue($cardConfig, 'examNumberUseLength', 0),
                    'showSecretTag' => $cardConfig['showSecretTag'],
                    'showSchool' => $cardConfig['showSchool'],
                    'showGradeClass' => $cardConfig['showGradeClass'],
                    'showParentScore' => $cardConfig['showParentScore'],
                    'showChildScore' => $cardConfig['showChildScore'],
                    'showQrcode' => $cardConfig['showQrcode'],
                ];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * @param  CardImage $card
     */
    protected static function drawGutterQr(&$card)
    {
        $noticeImage = $card->imageDir . '/qr_narrow_gutter_image.png';
        $card->composeSVG($noticeImage, $card->left - 265, $card->top + 200);
    }

    /**
     * @param  CardImage $card
     */
    protected static function drawGutterOne(&$card)
    {
        $noticeImage = $card->imageDir . '/h_gutter_one.svg';
        $card->composeSVG($noticeImage, $card->left - 202, $card->top);
    }

    /**
     * @param  CardImage $card
     */
    protected static function drawGutterTwo(&$card)
    {
        $noticeImage = $card->imageDir . '/h_gutter_two.svg';
        $card->composeSVG($noticeImage, $card->width - 206, $card->top);
    }

    /**
     * @param $title
     * @param CardImage $card
     * @return bool
     */
    protected static function drawPaperTitle($title, &$card)
    {
        $fontSize = 72;
        $x = $card->left + $card->contentWidth / 2;
        $y = $fontSize + $card->helpPointWidth + $card->top;
        $draw = new ImagickDraw();
        $draw->setFont($card->textFont2);
        $draw->setFontSize($fontSize);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);
        $draw->setTextInterLineSpacing(15);
        $draw->annotation($x, $y, $title);
        // $draw->rectangle($x, $y-$fontSize, $x+$fontSize, $y);
        return $card->im->drawImage($draw);
    }

    /**
     * @param $title
     * @param CardImage $card
     * @return bool
     */
    protected static function drawPaperGutterTitle($title, &$card)
    {
        $fontSize = 72;
        $x = $card->left + $card->contentWidth / 2;
        $y = $fontSize + $card->helpPointWidth + $card->top + 100;
        $draw = new ImagickDraw();
        $draw->setFont($card->textFont2);
        $draw->setFontSize($fontSize);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);
        $draw->setTextInterLineSpacing(15);
        $draw->annotation($x, $y, $title);
        // $draw->rectangle($x, $y-$fontSize, $x+$fontSize, $y);
        return $card->im->drawImage($draw);
    }

    /**
     * @param $x
     * @param $y
     * @param $strokeColor
     * @param $fillColor
     * @param $colmun
     * @param $boxWidth
     * @param $imageWidth
     * @return array
     */
    public static function pasteImage($x, $y, $strokeColor, $fillColor, $colmun, $boxWidth ,$imageWidth)
    {
        $lineHeight = 79;
        $strokeWidth = 2;
        $boxWidth = $colmun > 9 ? 62 : $boxWidth;
        $width = $colmun * $boxWidth + $strokeWidth;
        $offset = $imageWidth - $width;

        $draw = new ImagickDraw();

        $draw->setStrokeColor($strokeColor);
        $draw->setFillColor($fillColor);

        $draw->setStrokeWidth($strokeWidth);

        for ($i = 1; $i <= $colmun; $i++) {
            $draw->rectangle(
                $x + $offset + ($i - 1) * $boxWidth,
                $y + 1,
                $x + $offset + $i * $boxWidth,
                $y + $lineHeight
            );
        }
        return [$offset, $draw];
    }

    /**
     * @param $x
     * @param $y
     * @param $content
     * @param $lineHeight
     * @param $offset
     * @param $textFont
     * @return ImagickDraw
     */
    public static function pasteTitle($x, $y, $content, $lineHeight, $offset, $textFont)
    {
        $draw = new ImagickDraw();

        $draw->setFont($textFont);
        $draw->setFontSize(36);
        $draw->setTextAlignment(Imagick::ALIGN_LEFT);
        $draw->annotation($x + $offset - 168, $y + $lineHeight - ($lineHeight - 36) * 2 / 3,
            $content);

        return $draw;
    }

    /**
     * @param $x
     * @param $y
     * @param $strokeColor
     * @param $fillColor
     * @param $colmun
     * @param $colmunWidth
     * @param $imageWidth
     * @param $imageHeight
     * @param string $align
     * @return array
     */
    private static function fillExamNumBox(
        $x,
        $y,
        $strokeColor,
        $fillColor,
        $colmun,
        $colmunWidth,
        $imageWidth,
        $imageHeight,
        $align = 'left'
    ) {
        $lineHeight = 68;
        $strokeWidth = 2;
        $width = $colmun * $colmunWidth;
        $height = $imageHeight;
        $offset = 0;
        switch ($align) {
            case 'right':
                $offset = $imageWidth - $width;
                break;
            case 'center':
                $offset = (int)(($imageWidth - $width) / 2);
                break;
            case 'left':
                $offset = 0;
                break;
        }

        $draw = new ImagickDraw();
        $draw->setStrokeColor($strokeColor);
        $draw->setFillColor($fillColor);
        $draw->setStrokeWidth($strokeWidth);
        $draw->rectangle(
            $x + $offset + 1,
            $y + 1,
            $x + $offset + $width,
            $y + $height
        );
        for ($i = 1; $i <= $colmun; $i++) {
            $draw->rectangle(
                $x + $offset + ($i - 1) * $colmunWidth + 1,
                $y + 1 + $lineHeight,
                $x + $offset + $i * $colmunWidth,
                $y + $height
            );
        }
        $draw->line(
            $x + $offset + 1,
            $y + 2 * $lineHeight + 10,
            $x + $offset + $width,
            $y + 2 * $lineHeight + 10
        );
        return [$offset, $draw];
    }

    /**
     * @param $x
     * @param $y
     * @param $content
     * @param $imageWidth
     * @param $lineHeight
     * @param $offset
     * @param $textFont
     * @return ImagickDraw
     */
    public static function examNumTitle(
        $x,
        $y,
        $content,
        $imageWidth,
        $lineHeight,
        $offset,
        $textFont
    ) {
        $draw = new ImagickDraw();

        $draw->setFont($textFont);
        $draw->setFontSize(36);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);
        $draw->annotation($x + ($imageWidth + $offset) / 2,
            $y + $lineHeight - ($lineHeight - 36) * 2 / 3, $content);

        return $draw;
    }
}
