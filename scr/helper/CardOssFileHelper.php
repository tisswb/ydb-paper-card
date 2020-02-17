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
     * 获取试卷答题卡图片存储文件名
     *
     * @param int $cardId
     * @param string $course
     * @param $type
     * @param int $page
     * @param string $extension
     * @return string
     */
    public static function getCardImageFilename(
        $cardId,
        $course,
        $type,
        $page,
        $extension
    ): string {
        $extension = ltrim($extension, '.');
        return static::getCardPath(
                $cardId,
                $course
            ) . "/card-image-{$page}-{$type}.{$extension}";
    }

    /**
     * 获取试卷答题卡灰度图片存储文件名
     *
     * @param int $cardId
     * @param string $course
     * @param $type
     * @param int $page
     * @param string $extension
     * @return string
     */
    public static function getCardGrayImageFilename(
        $cardId,
        $course,
        $type,
        $page,
        $extension
    ): string {
        $extension = ltrim($extension, '.');
        return static::getCardPath(
                $cardId,
                $course
            ) . "/gray-card-image-{$page}-{$type}.{$extension}";
    }

    /**
     * @param int $examId
     * @param string $course
     * @return string
     */
    public static function getCardPath(int $examId, string $course)
    {
        //todo
        return '';
    }
}