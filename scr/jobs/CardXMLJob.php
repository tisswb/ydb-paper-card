<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2017-11-29
 * Time: 15:15
 */

namespace ydb\card\jobs;

use common\base\InstanceDb;
use common\base\OssHelper;
use common\card\CardService;
use common\models\instance\ExamPaper;
use common\service\OssFileService;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Class CardXMLJob
 * @package ydb\card\jobs
 */
class CardXMLJob extends BaseObject implements JobInterface
{
    public $examId;
    public $paperId;
    public $resizeTo;

    /**
     * @param \yii\queue\Queue $queue
     * @throws \Exception
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function execute($queue)
    {
        InstanceDb::initByExamId($this->examId);
        $file = '';
        $paper = ExamPaper::findOne($this->paperId);
        $target = OssFileService::getPaperCardSpecFilename($this->examId, $paper->course_id, true);
        try {
            $xml = CardService::markingXml($this->paperId, $this->resizeTo);
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
            $xmlRemote = OssHelper::uploadFile($target, $file);
            $paper = ExamPaper::findOne($this->paperId);
            $paper->xml_url = $xmlRemote;
            if (!$paper->save(false)) {
                \Yii::error('xml save into paper error', __METHOD__);
            }
            \Yii::error('create card xml done ' . $this->paperId);
        } catch (\Exception $e) {
            \Yii::error('upload error', __METHOD__);
        }
        unlink($file);
    }
}
