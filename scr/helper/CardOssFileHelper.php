<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2020/2/15
 * Time: 22:16
 */

namespace ydb\card\helper;


use yii\base\BaseObject;

/**
 * Class CardOssFileHelper
 * @package ydb\card\helper
 */
class CardOssFileHelper extends BaseObject
{
    /**
     * 获取试卷答题卡格式XML存储文件名
     *
     * @param string $componentId
     * @param int $examId
     * @param string $course
     * @param bool $timeStamp
     * @return string
     */
    public static function getPaperCardSpecFilename(
        $componentId,
        $cardId,
        $courseId,
        $timeStamp = false
    ): string {
        if ($timeStamp === true) {
            $time = time();
            $fileName = "/card-spec-{$course}-{$time}.xml";
        } else {
            $fileName = "/card-spec-{$course}.xml";
        }
        return static::getCardRootPath(
                $componentId,
                $cardId
            ) . $fileName;
    }

    /**
     * 获取试卷答题卡图片存储文件名
     *
     * @param string $componentId
     * @param int $cardId
     * @param $type
     * @param int $page
     * @param string $extension
     * @return string
     */
    public static function getCardImageFilename(
        $componentId,
        $cardId,
        $type,
        $page,
        $extension
    ): string {
        $extension = ltrim($extension, '.');
        return static::getCardRootPath(
                $componentId,
                $cardId
            ) . "/card-image-{$page}-{$type}.{$extension}";
    }

    /**
     * 获取试卷答题卡灰度图片存储文件名
     *
     * @param string $componentId
     * @param int $cardId
     * @param $type
     * @param int $page
     * @param string $extension
     * @return string
     */
    public static function getCardGrayImageFilename(
        $componentId,
        $cardId,
        $type,
        $page,
        $extension
    ): string {
        $extension = ltrim($extension, '.');
        return static::getCardRootPath(
                $componentId,
                $cardId
            ) . "/gray-card-image-{$page}-{$type}.{$extension}";
    }

    /**
     * 获取考试数据存储根目录
     * @param $componentId
     * @param $examId
     * @return string
     */
    public static function getCardRootPath($componentId, $cardId): string
    {
        return "card/{$componentId}/{$cardId}";
    }
}