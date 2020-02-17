<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2020/2/15
 * Time: 22:10
 */

namespace ydb\card\helper;

use Exception;
use OSS\OssClient;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Class CardOssHelper
 * @package ydb\card\helper
 */
class CardOssHelper extends BaseObject
{
    public const IMAGE_STRING = 0; // 字符串
    public const IMAGE_REMOTE = 1; // 远程未下载
    public const IMAGE_JOB = 6; // 任务队列中
    public const IMAGE_OSS = 2;    // 已存入OSS
    public const IMAGE_DOWNLOAD_ERROR = 3;  // 转存失败
    public const IMAGE_SAVE_ERROR = 4;  // 转存失败
    public const IMAGE_UPLOAD_ERROR = 5;  // 转存失败

    /**
     * @return array
     */
    public static function status()
    {
        return [
            static::IMAGE_STRING,
            static::IMAGE_REMOTE,
            static::IMAGE_OSS,
            static::IMAGE_DOWNLOAD_ERROR,
            static::IMAGE_SAVE_ERROR,
            static::IMAGE_UPLOAD_ERROR,
        ];
    }

    /**
     * @return array
     */
    public static function errorCodes()
    {
        return [
            static::IMAGE_DOWNLOAD_ERROR,
            static::IMAGE_SAVE_ERROR,
            static::IMAGE_UPLOAD_ERROR,
        ];
    }

    /**
     * @return array
     */
    public static function needDownloadStatus()
    {
        return ArrayHelper::merge([static::IMAGE_REMOTE], static::errorCodes());
    }

    /**
     * 下载url的图片并上传到OSS中，返回图片url，失败或者图片不存在返回源图片url
     * @param string $url 源图片url
     * @param string $ossFileName 上传文件地址
     * @return int|string
     * @throws Exception
     */
    public static function downloadAndUploadImage($url, $ossFileName)
    {
        if (!static::remoteFileExists($url) || substr($url, 0, 4) != 'http') {
            return static::IMAGE_DOWNLOAD_ERROR;
        }
        $parsedUrl = parse_url($url);
        $filePathArray = explode('/',$parsedUrl['path']);
        $imageName = end($filePathArray);
        $tempImageName = microtime(true) .'_'.mt_rand(1,9999). $imageName;
        $tempDir = Yii::getAlias('@runtime') . '/temp_img/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir);
        }
        if (!is_writable($tempDir)) {
            return static::IMAGE_SAVE_ERROR;
        }
        $filePath = $tempDir . $tempImageName;

        $file_content = static::downloadFile($url, 2);
        if (empty($file_content)) {
            return static::IMAGE_DOWNLOAD_ERROR;
        }

        $writeFile = static::writeFile($filePath, $file_content, 2);
        if ($writeFile == false) {
            return static::IMAGE_SAVE_ERROR;
        }
        $url = static::uploadFile($ossFileName, $filePath, 2);
        if ($url == false) {
            return static::IMAGE_UPLOAD_ERROR;
        }
        unlink($filePath);
        return $url;
    }

    /**
     * @param $url
     * @param $ossFileName
     * @return int|string
     * @throws Exception
     * @deprecated spell error
     */
    public static function donwloadAndUploadImage($url, $ossFileName)
    {
        return static::downloadAndUploadImage($url, $ossFileName);
    }

    /**
     * @param $file
     * @param int $retry
     * @param bool $throw
     * @param bool $nobody
     * @return mixed|string
     * @throws Exception
     */
    public static function downloadFile($file, $retry = 3, $throw = false, $nobody = false)
    {
        while ($retry) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_POST, 0);
                curl_setopt($ch,CURLOPT_URL, $file);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                if ($nobody) {
                    curl_setopt($ch, CURLOPT_NOSIGNAL,1);
                    curl_setopt($ch, CURLOPT_TIMEOUT_MS,200);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                } else {
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                }
                curl_setopt($ch, CURLOPT_POST, false);
                $fileContent = curl_exec($ch);
                $curlHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($curlHttpCode == 200) {
                    return $nobody ? $curlHttpCode : $fileContent;
                } else {
                    throw new Exception('curl response: ' . $curlHttpCode);
                }
            } catch (Exception $e) {
                Yii::error($e->getMessage());
                $retry--;
                continue;
            }
        }
        if ($throw) {
            throw new Exception('download error: ' . $file . PHP_EOL);
        } else {
            Yii::error('download error: ' . $file . PHP_EOL);
            return false;
        }
    }

    /**
     * @param $file
     * @param $content
     * @param int $retry
     * @param bool $throw
     * @return bool
     * @throws Exception
     */
    public static function writeFile($file, $content, $retry = 3, $throw = false)
    {
        while ($retry) {
            try {
                $downloaded_file = fopen($file, 'w');
                fwrite($downloaded_file, $content);
                fclose($downloaded_file);
                break;
            } catch (Exception $e) {
                Yii::error('file write error: ' . $e->getMessage());
                $retry--;
            }
        }
        if ($retry == 0) {
            if ($throw) {
                throw new Exception('write error: ' . $file . PHP_EOL);
            } else {
                Yii::error('write error: ' . $file . PHP_EOL);
                return false;
            }
        }
        return true;
    }

    /**
     * @param $target
     * @param $file
     * @param int $retry
     * @param bool $throw
     * @return bool|string
     * @throws InvalidConfigException
     */
    public static function uploadFile($target, $file, $retry = 3, $throw = false)
    {
        /* @var \cdcchen\yii\aliyun\OssClient $client */
        $client = Yii::$app->get('ossClient');
        /* @var OssClient $ossClient */
        $ossClient = $client->getOssClient();

        $response = [];
        while ($retry) {
            try {
                $response = $ossClient->uploadFile($client->defaultBucket, $target, $file);
                break;
            } catch (Exception $e) {
                Yii::error($e->getMessage().$e->getTraceAsString());
                $retry--;
            }
        }
        if ($retry == 0) {
            if ($throw) {
                throw new Exception('upload retry failed', __METHOD__);
            } else {
                Yii::error('upload retry failed', __METHOD__);
                return false;
            }
        }
        return $response['info']['url'] ?? '';
    }

    /**
     * @param $url
     * @return bool
     */
    public static function remoteFileExists($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        $result = curl_exec($curl);
        $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($result !== false && $curlHttpCode == '200') {
            return true;
        }
        return false;
    }
}