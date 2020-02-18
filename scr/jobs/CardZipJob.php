<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2017-11-29
 * Time: 15:15
 */

namespace ydb\card\jobs;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ydb\card\helper\CardOssFileHelper;
use ydb\card\helper\CardOssHelper;
use ydb\card\PaperCard;
use yii\base\BaseObject;
use yii\db\Query;
use yii\helpers\Json;
use yii\queue\JobInterface;
use ZipArchive;

/**
 * Class CardZipJob
 * @package ydb\card\jobs
 */
class CardZipJob extends BaseObject implements JobInterface
{
    /** @var PaperCard $component */
    public $component;
    public $cardId;
    public $format;
    public $resizeTo;

    /**
     * @param \yii\queue\Queue $queue
     * @throws \Exception
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function execute($queue)
    {
        $card = (new Query())
            ->select('*')
            ->from($this->component->tableCard)
            ->andWhere(['id' => $this->cardId])
            ->one($this->component->db);
        $zipFileBaseName = Json::decode($card['settings'])['card']['page_setting']['shortTitle'] ?? '';

        $filePath = \Yii::getAlias("@runtime/") . CardOssFileHelper::getCardRootPath(
                                                            $this->component->uniqueId,
                                                            $this->cardId
                                                        );
        $time = time();
        $zipName = "{$this->cardId}_{$this->resizeTo}_{$time}.zip";
        $zipFile = $filePath . '/' . $zipName;
        $this->zip($filePath . '/' . $card->course_id, $zipFile, $zipFileBaseName, $this->format);
        $target = CardOssFileHelper::getCardRootPath(
            $this->component->uniqueId,
            $this->cardId
        ) . '/' . $zipName;
        try {
            $zipUrl = CardOssHelper::uploadFile($target, $zipFile);
            if (
                !$this->component->db->createCommand()->update(
                    $this->component->tableCard,
                    ['card_zip' => $zipUrl, 'card_last_modify' => time()],
                    ['id' => $this->cardId]
                )->execute()
            ) {
                \Yii::info('zipUrl save error', __METHOD__);
            }
        } catch (\Exception $e) {
            \Yii::error('zip file upload error', __METHOD__);
        }
        \Yii::error('create card zip done ' . $this->paperId);
        if (is_file($zipFile)) {
            unlink($zipFile);
        }
    }

    /**
     * @param $source
     * @param $destination
     * @param string $baseName
     * @param string $format
     * @return bool
     */
    private function zip($source, $destination, $baseName = '', $format = 'jpg')
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }
        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }
        $source = str_replace('\\', '/', realpath($source));
        if (is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);
                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, '/') + 1), ['.', '..'])) {
                    continue;
                }
                $file = realpath($file);
                if (is_dir($file) === true) {
                    continue;
                    // $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else {
                    $len = strlen($format);
                    $start = 0 - $len;
                    if (is_file($file) === true && substr($file, $start) == $format) {
                        $fileName = str_replace($source . '/', '', $file);
                        if (!empty($baseName)) {
                            list(, , $part, $typeInfo) = explode('-', $fileName);
                            list($type, $ext) = explode('.', $typeInfo);
                            $fileName = $baseName . '-' . $type . '纸-' . $part . '.' . $ext;
                        }
                        $zip->addFromString($fileName, file_get_contents($file));
                        unlink($file);
                    }
                }
            }
        } else {
            if (is_file($source) === true) {
                $fileName = basename($source);
                if (!empty($baseName)) {
                    list(, , $part, $typeInfo) = explode('-', $fileName);
                    list($type, $ext) = explode('.', $typeInfo);
                    $fileName = $baseName . '-' . $type . '纸-' . $part . '.' . $ext;
                }
                $zip->addFromString($fileName, file_get_contents($source));
                unlink($source);
            }
        }
        return $zip->close();
    }
}
