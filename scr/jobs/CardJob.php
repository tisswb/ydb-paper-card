<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2017-11-29
 * Time: 15:15
 */

namespace ydb\card\jobs;

use ydb\card\PaperCard;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Class CardJob
 * @package ydb\card\jobs
 */
class CardJob extends BaseObject implements JobInterface
{
    /** @var PaperCard $component */
    public $component;
    public $cardId;
    // public $examId;
    // public $paperId;
    public $format;
    public $color;
    public $resizeTo;

    public function init()
    {
        parent::init();
        $this->format = $this->format ?? 'jpg';
        $this->color = $this->color ?? 'black';
        $this->resizeTo = $this->resizeTo ?? 'A3';
    }

    /**
     * @param \yii\queue\Queue $queue
     * @throws \Exception
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function execute($queue)
    {
        // InstanceDb::initByExamId($this->examId);
        $dependJobIds = [];
        // create image
        $pages = (new Query())
            ->select('*')
            ->from($this->component->tablePage)
            ->andWhere(['card_id' => $this->cardId])
            ->all($this->component->db);
        foreach ($pages as $page) {
            $dependJobIds[] = $queue->push(new CardImageJob([
                'component' => $this->component,
                'cardId' => $this->cardId,
                'page' => $page,
                'format' => $this->format,
                'color' => $this->color,
                'resizeTo' => $this->resizeTo
            ]));
        }
        while (true) {
            $status = $this->checkDependJobsDone($queue, $dependJobIds);
            if ($status) {
                break;
            }
            sleep(1);
        }
        // // create xml
        // \Yii::error('create card xml start ' . $this->paperId);
        // $dependJobIds[] = $queue->push(new AnswerCardXMLJob([
        //     'examId' => $this->examId,
        //     'paperId' => $this->paperId,
        //     'resizeTo' => $this->resizeTo,
        // ]));
        // // create zip and wait zip done
        // while (true) {
        //     $status = $this->checkDependJobsDone($queue, $dependJobIds);
        //     if ($status) {
        //         break;
        //     }
        //     sleep(1);
        // }
        // \Yii::error('create card xml start ' . $this->paperId);
        $zipJobId = $queue->push(new CardZipJob([
            'examId' => $this->examId,
            'paperId' => $this->paperId,
            'format' => $this->format,
            'resizeTo' => $this->resizeTo,
        ]));
        $zipJobId = $queue->push(new CardZipJob([
            'component' => $this->component,
            'cardId' => $this->cardId,
            'format' => $this->format,
            'resizeTo' => $this->resizeTo,
        ]));
        while (true) {
            if ($queue->isDone($zipJobId)) {
                break;
            }
            sleep(1);
        }
    }

    /**
     * @param \yii\queue\Queue $queue
     * @param $dependJobIds
     * @return bool
     */
    private function checkDependJobsDone($queue, $dependJobIds)
    {
        foreach ($dependJobIds as $jobId) {
            if (!$queue->isDone($jobId)) {
                return false;
            }
        }
        return true;
    }
}
