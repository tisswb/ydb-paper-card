<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2017-11-29
 * Time: 15:15
 */

namespace ydb\card\jobs;

use ydb\card\CardService;
use ydb\card\helper\CardOssFileHelper;
use ydb\card\helper\CardOssHelper;
use ydb\card\PaperCard;
use yii\base\BaseObject;
use yii\db\Query;
use yii\queue\JobInterface;

/**
 * Class CardXMLJob
 * @package ydb\card\jobs
 */
class CardXMLJob extends BaseObject implements JobInterface
{
    public $examPaperId;
    /** @var PaperCard $component */
    public $component;
    public $cardId;
    public $resizeTo;

    /**
     * @param \yii\queue\Queue $queue
     * @throws \Exception
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function execute($queue)
    {
        // InstanceDb::initByExamId($this->examId);
        $file = '';
        $card = (new Query())
            ->select('*')
            ->from($this->component->tableCard)
            ->andWhere(['id' => $this->cardId])
            ->one($this->component->db);
        $pages = (new Query())
            ->select('*')
            ->from($this->component->tablePage)
            ->andWhere(['card_id' => $this->cardId])
            ->all($this->component->db);
        $target = CardOssFileHelper::getPaperCardSpecFilename(
            $this->component->uniqueId,
            $this->cardId,
            $card['course_id'],
            true
        );
        try {
            $xml = CardService::markingXml($this->examPaperId, $card, $pages, $this->resizeTo);
            $filePath = \Yii::getAlias("@runtime/");
            $file = $filePath . $target;
            $path = dirname($file);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            file_put_contents($file, $xml);
        } catch (\Exception $e) {
            \Yii::info('can not create xml', __METHOD__);
        }
        try {
            $xmlRemote = CardOssHelper::uploadFile($target, $file);
            if (
                !$this->component->db->createCommand()->update(
                    $this->component->tableCard,
                    ['xml_url' => $xmlRemote],
                    ['id' => $this->cardId]
                )->execute()
            ) {
                \Yii::error('xml save into paper error', __METHOD__);
            }
            \Yii::error('create card xml done ' . $this->paperId);
        } catch (\Exception $e) {
            \Yii::error('upload error', __METHOD__);
        }
        unlink($file);
    }
}
