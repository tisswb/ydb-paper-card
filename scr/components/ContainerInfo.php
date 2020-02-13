<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-13
 * Time: 9:38
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use common\models\instance\CardContainer;
use common\models\instance\CardContainerImage;
use Imagick;
use ImagickDraw;
use yii\base\BaseObject;
use yii\helpers\Json;

/**
 * Class Container
 * @package ydb\card\components
 */
class ContainerInfo extends BaseObject
{
    /**
     * @param CardImage $card
     * @return bool
     */
    public static function draw(&$card)
    {
        $config = $card->pageModel->getPaper()->getCardConfig();
        $containers = $card->pageModel->getContainer();
        $draw = new ImagickDraw();
        if (!empty($containers)) {
            foreach ($containers as $container) {
                if (
                    $container->lt_pos_x === null
                    && $container->lt_pos_y === null
                    && $container->rb_pos_x === null
                    && $container->rb_pos_y === null
                ) {
                    continue;
                }
                // draw container
                if ($container->type == CardContainer::TYPE_FORBID) {
                    $draw->setFillColor($card->colorForbid);
                    $draw->setStrokeWidth(2);
                    $draw->setStrokeColor($card->colorRed);
                    $draw->rectangle(
                        $container->lt_pos_x * 2,
                        $container->lt_pos_y * 2,
                        $container->rb_pos_x * 2,
                        $container->rb_pos_y * 2
                    );
                    $draw->setFont($card->textFont);
                    $draw->setFontSize(36);
                    $draw->setFillColor($card->colorRed);
                    $draw->setStrokeWidth(0);
                    $draw->setTextAlignment(Imagick::ALIGN_CENTER);
                    $draw->annotation(
                        (int)$container->lt_pos_x + (int)$container->rb_pos_x,
                        (int)$container->lt_pos_y + (int)$container->rb_pos_y,
                        '请勿在此区域作答'
                    );

                } else {
                    $draw->setFillColor($card->colorTransparent);
                    $draw->setStrokeWidth(2);
                    $draw->setStrokeColor($card->colorBlack);
                    $draw->rectangle(
                        $container->lt_pos_x * 2,
                        $container->lt_pos_y * 2,
                        $container->rb_pos_x * 2,
                        $container->rb_pos_y * 2
                    );
                }

                // draw container title
                $draw->setFont($card->textFont);
                $fontSize = ($config['globalFontSize'] ?? 18) * 2;
                $draw->setFontSize($fontSize);
                $draw->setFillColor($card->colorBlack);
                $draw->setStrokeWidth(0);
                $draw->setTextAlignment(Imagick::ALIGN_LEFT);
                list($titlePosX, $titlePosY) = $container->getTitlePos();
                $draw->annotation(
                    $titlePosX,
                    $titlePosY,
                    $container->getTitle($config['showParentScore'] == ACTIVE_YES)
                );
            }
            return $card->im->drawImage($draw);
        }
        return false;
    }

    /**
     * @param CardImage $card
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public static function drawImageAndLine(&$card)
    {
        $containers = $card->pageModel->getContainer();
        $draw = new ImagickDraw();
        $draw->setFillColor($card->colorBlack);
        $draw->setStrokeWidth(0);
        if (!empty($containers)) {
            foreach ($containers as $container) {
                if (
                    $container->lt_pos_x === null
                    && $container->lt_pos_y === null
                    && $container->rb_pos_x === null
                    && $container->rb_pos_y === null
                ) {
                    continue;
                }
                // draw separate image
                $images = CardContainerImage::find()
                    ->andWhere(['card_container_id' => $container->id])
                    ->all();
                if (!empty($images)) {
                    /* @var \cdcchen\yii\aliyun\OssClient $client */
                    $client = \Yii::$app->get('ossClient');
                    /** @var CardContainerImage $image */
                    foreach ($images as $image) {
                        $url = $client->getUrl($image->url);
                        $imageData = file_get_contents($url);
                        if (!($imageData == false)) {
                            $card->composeImage(
                                $imageData,
                                2 * $image->realLtX(),
                                2 * $image->realLtY(),
                                2 * $image->width,
                                2 * $image->height
                            );
                        }
                    }
                }

                // draw separate line
                $containerInfo = Json::decode($container->info);
                if (isset($containerInfo['showSeparateLine']) && $containerInfo['showSeparateLine'] == 'Y') {
                    $lineStartX = (int)$container->lt_pos_x + (int)$container->rb_pos_x;
                    $lineStartY = $container->lt_pos_y * 2 + 20;
                    $lineEndX = $lineStartX;
                    $lineEndY = $container->rb_pos_y * 2 - 20;
                    $draw->line($lineStartX, $lineStartY, $lineEndX, $lineEndY);
                    $draw->line($lineStartX + 1, $lineStartY, $lineEndX + 1, $lineEndY);
                }
            }
            return $card->im->drawImage($draw);
        }
        return false;
    }
}
