<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2017-11-29
 * Time: 15:15
 */

namespace ydb\card\jobs;

use ydb\card\CardImage;
use ydb\card\helper\CardOssFileHelper;
use ydb\card\PaperCard;
use yii\base\BaseObject;
use yii\db\Query;
use yii\helpers\Json;
use yii\queue\JobInterface;

/**
 * Class CardImageJob
 * @package ydb\card\jobs
 */
class CardImageJob extends BaseObject implements JobInterface
{
    /** @var PaperCard $component */
    public $component;
    public $cardId;
    public $page;
    public $format;
    public $color;
    public $resizeTo;
    public $courseName;
    
    /**
     * @param \yii\queue\Queue $queue
     * @throws \Exception
     */
    public function execute($queue)
    {
        $card = (new Query())
            ->select('*')
            ->from($this->component->tableCard)
            ->andWhere(['id' => $this->cardId])
            ->one($this->component->db);
        if (!is_array($card)) {
            \Yii::error('can not find paper with paperId ' . $this->paperId, __METHOD__);
            \Yii::$app->end();
        }
        try {
            $cardImage = new CardImage([
                'component' => $this->component,
                'card' => $card,
                'cardPage' => $this->page,
                'courseName' => $this->courseName,
                'resizeTo' => $this->resizeTo,
            ]);
            $file = $cardImage->saveCard();
            $target = CardOssFileHelper::getCardImageFilename(
                $this->component->uniqueId,
                $card['id'],
                $this->resizeTo,
                $page['order'],
                $this->format
            );
            $fileGray = $cardImage->saveMarkingTemplate();
            $targetGray = CardOssFileHelper::getCardGrayImageFilename(
                $this->component->uniqueId,
                $card['id'],
                $this->resizeTo,
                $page['order'],
                'jpg'
            );
            $imageUrl = OssHelper::uploadFile($target, $file);
            $imageGrayUrl = OssHelper::uploadFile($targetGray, $fileGray);
            if (
                !$this->component->db->createCommand()->update(
                    $this->component->tablePage,
                    ['image_url' => $imageUrl, 'image_gray_url' => $imageGrayUrl],
                    ['id' => $this->page['id']]
                )->execute()
            ) {
                \Yii::error('save image url into cardPage error', __METHOD__);
            }
        } catch (\Exception $e) {
            \Yii::error('upload error', __METHOD__);
        }
        \Yii::error('create card image done ' . $this->$card . '|' . $this->pageId);
        file_exists($fileGray) && unlink($fileGray);
    }
}
