<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-13
 * Time: 9:38
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use Imagick;
use ImagickDraw;
use yii\base\BaseObject;
use yii\db\Query;
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
        $config = $card->cardConfig;
        $containers = static::getContainers($card);
        $draw = new ImagickDraw();
        if (!empty($containers)) {
            foreach ($containers as $container) {
                if (
                    $container['lt_pos_x'] === null
                    && $container['lt_pos_y'] === null
                    && $container['rb_pos_x'] === null
                    && $container['rb_pos_y'] === null
                ) {
                    continue;
                }
                // draw container
                if ($container['type'] == 'forbid') {
                    $draw->setFillColor($card->colorForbid);
                    $draw->setStrokeWidth(2);
                    $draw->setStrokeColor($card->colorRed);
                    $draw->rectangle(
                        $container['lt_pos_x'] * 2,
                        $container['lt_pos_y'] * 2,
                        $container['rb_pos_x'] * 2,
                        $container['rb_pos_y'] * 2
                    );
                    $draw->setFont($card->textFont);
                    $draw->setFontSize(36);
                    $draw->setFillColor($card->colorRed);
                    $draw->setStrokeWidth(0);
                    $draw->setTextAlignment(Imagick::ALIGN_CENTER);
                    $draw->annotation(
                        (int)$container['lt_pos_x'] + (int)$container['rb_pos_x'],
                        (int)$container['lt_pos_y'] + (int)$container['rb_pos_y'],
                        '请勿在此区域作答'
                    );

                } else {
                    $draw->setFillColor($card->colorTransparent);
                    $draw->setStrokeWidth(2);
                    $draw->setStrokeColor($card->colorBlack);
                    $draw->rectangle(
                        $container['lt_pos_x'] * 2,
                        $container['lt_pos_y'] * 2,
                        $container['rb_pos_x'] * 2,
                        $container['rb_pos_y'] * 2
                    );
                }

                // draw container title
                $draw->setFont($card->textFont);
                $fontSize = ($config['globalFontSize'] ?? 18) * 2;
                $draw->setFontSize($fontSize);
                $draw->setFillColor($card->colorBlack);
                $draw->setStrokeWidth(0);
                $draw->setTextAlignment(Imagick::ALIGN_LEFT);
                list($titlePosX, $titlePosY) = static::getTitlePos($container);
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
        $containers = static::getContainers($card);
        $draw = new ImagickDraw();
        $draw->setFillColor($card->colorBlack);
        $draw->setStrokeWidth(0);
        if (!empty($containers)) {
            foreach ($containers as $container) {
                if (
                    $container['lt_pos_x'] === null
                    && $container['lt_pos_y'] === null
                    && $container['rb_pos_x'] === null
                    && $container['rb_pos_y'] === null
                ) {
                    continue;
                }
                // draw separate image
                $images = static::getImages($card, $container['id']);
                if (!empty($images)) {
                    /* @var \cdcchen\yii\aliyun\OssClient $client */
                    $client = \Yii::$app->get('ossClient');
                    foreach ($images as $image) {
                        $url = $client->getUrl($image['url']);
                        $imageData = file_get_contents($url);
                        if (!($imageData == false)) {
                            $card->composeImage(
                                $imageData,
                                2 * ((int)$container['lt_pos_x'] + (int)$image['lt_pos_x']),
                                2 * ((int)$container['lt_pos_y'] + (int)$image['lt_pos_y']),
                                2 * $image['width'],
                                2 * $image['height']
                            );
                        }
                    }
                }
                // draw separate line
                $containerInfo = Json::decode($container['info']);
                if (isset($containerInfo['showSeparateLine']) && $containerInfo['showSeparateLine'] == 'Y') {
                    $lineStartX = (int)$container['lt_pos_x'] + (int)$container['rb_pos_x'];
                    $lineStartY = $container['lt_pos_y'] * 2 + 20;
                    $lineEndX = $lineStartX;
                    $lineEndY = $container['rb_pos_y'] * 2 - 20;
                    $draw->line($lineStartX, $lineStartY, $lineEndX, $lineEndY);
                    $draw->line($lineStartX + 1, $lineStartY, $lineEndX + 1, $lineEndY);
                }
            }
            return $card->im->drawImage($draw);
        }
        return false;
    }

    /**
     * @param $card
     * @return array
     */
    protected static function getContainers(&$card)
    {
        return (new Query())
            ->select('*')
            ->from($card->component->tableContainer)
            ->andWhere(['card_page_id' => $card->cardPage['id']])
            ->all($card->component->db);
    }

    /**
     * @param $card
     * @param int $containerId
     * @return array
     */
    protected static function getImages(&$card, int $containerId)
    {
        return (new Query())
            ->select('*')
            ->from($card->component->tableContainerImage)
            ->andWhere(['card_container_id' => $containerId])
            ->all($card->component->db);
    }

    /**
     * @param array $container
     * @return array
     */
    protected static function getTitlePos(array $container)
    {
        if ($container['type'] == 'objective') {
            return [$container['lt_pos_x'] * 2 + 46, $container['lt_pos_y'] * 2 + 66];
        } else {
            return [$container['lt_pos_x'] * 2 + 46, $container['lt_pos_y'] * 2 + 74];
        }
    }

    /**
     * @param $card
     * @param array $container
     * @param bool $showScore
     * @return string
     */
    protected static function getTitle(&$card, array $container, $showScore = true)
    {
        $info = Json::decode($container['info']);
        if (!($info === false)) {
            if (isset($info['showTitle']) && $info['showTitle'] == 'Y') {
                if ($container['type'] == 'objective') {
                    return $info['parentTitle'];
                } else {
                    $struct = (new Query())
                        ->select('*')
                        ->from($card->component->tableCardStruct)
                        ->andWhere(['id' => $info['parentStructureId']])
                        ->one($card->component->db);
                    if ($struct !== null) {
                        if ($showScore) {
                            $scoreArray = (new Query())
                                ->select('score')
                                ->from($card->component->tableCardStruct)
                                ->andWhere(['parent_id' => $info['parentStructureId']])
                                ->column($card->component->db);
                            $score = array_sum($scoreArray);
                            return "{$struct['title']}({$score}分){$info['description']['content']}";
                        } else {
                            return $struct['title'] . $info['description']['content'];
                        }
                    }
                }
            } elseif ($container['type'] == 'objective') {
                $des = $info['description']['content'] ?? '';
                return $info['config']['title'] . $des;
            }
        }
        return '';
    }
}
