<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2020/2/14
 * Time: 14:44
 */

namespace ydb\card;


use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class PaperController
 * @package ydb\card
 */
class PaperController extends Controller
{
    public const REVIEW_YOUHEN = 1;
    public const REVIEW_WUHEN = 2;
    public const REVIEW_IMPORT = 3;

    public $db;
    public $tableCard;
    public $tablePage;
    public $tableContainer;
    public $tableArea;
    public $tableStruct;

    /**
     * @var PaperCard $paperCard
     */
    public $paperCard;

    public function init()
    {
        parent::init();
        $this->db = $this->paperCard->db;
        $this->tableCard = $this->paperCard->tableCard;
        $this->tablePage = $this->paperCard->tablePage;
        $this->tableContainer = $this->paperCard->tableContainer;
        $this->tableArea = $this->paperCard->tableEditArea;
        $this->tableStruct = $this->paperCard->tableCardStruct;
    }

    /**
     * @param $cardId
     * @param int $page
     * @return string
     */
    public function actionImage($cardId, $page = 1)
    {
        $card = $this->getCard($cardId);
        $pageModel = (new Query())
            ->select('*')
            ->from($this->tablePage)
            ->andWhere(['card_id' => $cardId, 'order' => $page])
            ->one($this->db);
        if (!$card) {
            throw new NotFoundHttpException(404);
        }
        $cardImage = new CardImage([
            'cardPage' => $pageModel,
            'card' => $card,
        ]);
        $cardImage->image();
        return $cardImage->image();
    }

    /**
     * @param $cardId
     * @return string
     */
    public function actionXml($cardId)
    {
        header('Content-type: text/xml');
        return CardService::markingXml($cardId);
    }

    /**
     * @param $areaId
     * @param $cardId
     * @param int $showScore
     * @param string $color
     * @return mixed
     */
    public function actionArea($areaId, $cardId, $showScore = 1, $color = 'black')
    {
        /** @var object $card */
        $card = (new Query())
            ->select('*')
            ->from($this->tableCard)
            ->andWhere(['id' => $cardId])
            ->one($this->db);
        $params = [
            'areaId' => $areaId,
            'showScore' => $showScore,
            'color' => $color,
        ];
        if ($card['review_type'] == static::REVIEW_YOUHEN) {
            return $this->runAction('youhen', $params);
        } else {
            return $this->runAction('wuhen', $params);
        }
    }

    /**
     * @param $areaId
     * @param int $showScore
     * @param string $color
     * @return bool|string
     */
    public function actionYouhen($areaId, $showScore = 1, $color = 'black')
    {
        $area = $this->getArea($areaId);
        $container = $this->getContainer($area['card_container_id']);
        $cardPage = $this->getPage($container['card_page_id']);
        $card = $this->getCard($cardPage['card_id']);
        $cardConfig = Json::decode($card['settings']);
        $containerInfo = Json::decode($container['info']);
        $struct = $this->getStruct($area['struct_id']);
        $title = $struct['title'];
        $title = ($showScore == 1)
            ? $title . "({$struct['score']}分)"
            : $title;
        $containerAreas = $containerInfo['area'] ?? [];
        if (!empty($containerAreas)) {
            $containerAreas = ArrayHelper::index($containerAreas, 'structure_id');
            $containerArea = $containerAreas[$area['struct_id']];
            if (!isset($containerArea['scoreBox']) || empty($containerArea['scoreBox'])) {
                $scoreBoxWidth = 0;
                $scoreBoxHeight = 0;
            } elseif ($containerArea['box']['step'] == 1) {
                // 登分框2位
                $scoreBoxWidth = 308;
                $scoreBoxHeight = 186;
            } else {
                // 登分框3位
                $scoreBoxWidth = 502;
                $scoreBoxHeight = 186;
            }
        } else {
            $scoreBoxWidth = 0;
            $scoreBoxHeight = 0;
        }
        if ($containerInfo['isZuowen'] == 'Y') {
            $bigBox = $this->getCardZuowenSize($area['card_container_id']);
            if ($bigBox === 'small') {
                $zuowenLineCount = $cardConfig['pageColumn'] == 3
                    ? CardService::ZUOWEN_SHORT_LINE_COUNT
                    : CardService::ZUOWEN_LONG_LINE_COUNT;
            } else {
                $zuowenLineCount = $cardConfig['pageColumn'] == 3
                    ? CardService::ZUOWEN_BIG_SHORT_LINE_COUNT
                    : CardService::ZUOWEN_BIG_LONG_LINE_COUNT;
            }
            $zuowen = $containerInfo['zuowen'];
            $allNum = $zuowen['all'];
            $frontWordsNum = $zuowen['frontWordsNum'] ?? 0;
            $lineStart = (int)($frontWordsNum / $zuowenLineCount + 1);
            $lineCount = ceil($zuowen['total'] / $zuowenLineCount);
            $lineCount = $lineCount + $lineStart;
            $brotherIds = $this->getStructBrotherIds($struct);
            if (is_array($brotherIds) && empty($brotherIds)) {
                $title = '';
            }
            return $this->renderPartial(
                'zuowen_youhen',
                [
                    'title' => $title,
                    'width' => (int) ($area['rb_pos_x'] - $area['lt_pos_x']) * 2,
                    'size' => $bigBox,
                    'allNum' => $allNum,
                    'lineCount' => $lineCount,
                    'frontWordsNum' => $frontWordsNum,
                    'lineStart' => $lineStart,
                    'lineHeight' => 80 * $containerInfo['lineHeight'],
                    'fontSize' => ($cardConfig['globalFontSize'] ?? 18) * 2,
                    'color' => $color,
                    'count' => $zuowenLineCount,
                    'scoreBoxWidth' => $scoreBoxWidth,
                    'scoreBoxHeight' => $scoreBoxHeight,
                ]
            );
        } else {
            $content = str_replace(' ', '&nbsp;', $area['content']);
            if (strpos($content, "\n") === 0) {
                $content = '&nbsp;' . $content;
            }
            if (!empty($area)) {
                return $this->renderPartial(
                    'area_youhen',
                    [
                        'title' => $title,
                        'extra' => $container['type'] == 'extra',
                        'lineHeight' => 80 * $containerInfo['lineHeight'],
                        'width' => (int) 2 * ($area['rb_pos_x'] - $area['lt_pos_x']),
                        'fontSize' => ($cardConfig['globalFontSize'] ?? 18) * 2,
                        'content' => $content,
                        'scoreBoxWidth' => $scoreBoxWidth,
                        'scoreBoxHeight' => $scoreBoxHeight,
                    ]
                );
            }
        }
        return false;
    }

    /**
     * @param $areaId
     * @param int $showScore
     * @param string $color
     * @return bool|string
     */
    public function actionWuhen($areaId, $showScore = 1, $color = 'black')
    {
        $area = $this->getArea($areaId);
        $container = $this->getContainer($area['card_container_id']);
        $cardPage = $this->getPage($container['card_page_id']);
        $card = $this->getCard($cardPage['card_id']);
        $cardConfig = Json::decode($card['settings']);
        $containerInfo = Json::decode($container['info']);
        $struct = $this->getStruct($area['struct_id']);
        $title = $struct['title'];
        $title = ($showScore == 1)
            ? $title . "({$struct['score']}分)"
            : $title;
        if ($containerInfo['isZuowen'] == 'Y') {
            $bigBox = $this->getCardZuowenSize($area['card_container_id']);
            if ($bigBox === 'small') {
                $zuowenLineCount = $cardConfig['pageColumn'] == 3
                    ? CardService::ZUOWEN_SHORT_LINE_COUNT
                    : CardService::ZUOWEN_LONG_LINE_COUNT;
            } else {
                $zuowenLineCount = $cardConfig['pageColumn'] == 3
                    ? CardService::ZUOWEN_BIG_SHORT_LINE_COUNT
                    : CardService::ZUOWEN_BIG_LONG_LINE_COUNT;
            }
            $zuowen = $containerInfo['zuowen'];
            $allNum = $zuowen['all'];
            $frontWordsNum = $zuowen['frontWordsNum'] ?? 0;
            $lineStart = (int)($frontWordsNum / $zuowenLineCount + 1);
            $lineCount = ceil($zuowen['total'] / $zuowenLineCount);
            $lineCount = $lineCount + $lineStart;
            $brotherIds = $this->getStructBrotherIds($struct);
            if (is_array($brotherIds) && empty($brotherIds)) {
                $title = '';
            }
            return $this->renderPartial(
                'zuowen_wuhen',
                [
                    'title' => $title,
                    'width' => (int) ($area['rb_pos_x'] - $area['lt_pos_x']) * 2,
                    'size' => $bigBox,
                    'allNum' => $allNum,
                    'lineCount' => $lineCount,
                    'frontWordsNum' => $frontWordsNum,
                    'lineStart' => $lineStart,
                    'lineHeight' => 80 * $containerInfo['lineHeight'],
                    'fontSize' => ($cardConfig['globalFontSize'] ?? 18) * 2,
                    'color' => $color,
                    'count' => $zuowenLineCount,
                ]
            );
        } else {
            $containerAreas = $containerInfo['area'] ?? [];
            if (!empty($containerAreas)) {
                $containerAreas = ArrayHelper::index($containerAreas, 'structure_id');
                $indent = (int)$containerAreas[$area['struct_id']]['textAreaIndent'] ?? 0;
                $content = str_replace(' ', '&nbsp;', $area['content']);
                if (strpos($content, "\n") === 0) {
                    $content = '&nbsp;' . $content;
                }
                if (!empty($area)) {
                    return $this->renderPartial(
                        'area_wuhen',
                        [
                            'title' => $title,
                            'extra' => $container['type'] == 'extra',
                            'lineHeight' => 80 * $containerInfo['lineHeight'],
                            'textIndent' => 2 * $indent,
                            'width' => (int) 2 * ($area['rb_pos_x'] - $area['lt_pos_x']),
                            'fontSize' => ($cardConfig['globalFontSize'] ?? 18) * 2,
                            'content' => $content,
                        ]
                    );
                }
            }
        }
        return false;
    }

    /**
     * @param int $id
     * @return array|bool
     */
    protected function getCard(int $id)
    {
        return (new Query())
            ->select('*')
            ->from($this->tableCard)
            ->andWhere(['id' => $id])
            ->one($this->db);
    }

    /**
     * @param int $id
     * @return array|bool
     */
    protected function getPage(int $id)
    {
        return (new Query())
            ->select('*')
            ->from($this->tablePage)
            ->andWhere(['id' => $id])
            ->one($this->db);
    }

    /**
     * @param int $id
     * @return array|bool
     */
    protected function getContainer(int $id)
    {
        return (new Query())
            ->select('*')
            ->from($this->tableContainer)
            ->andWhere(['id' => $id])
            ->one($this->db);
    }

    /**
     * @param int $id
     * @return array|bool
     */
    protected function getArea(int $id)
    {
        return (new Query())
            ->select('*')
            ->from($this->tableArea)
            ->andWhere(['id' => $id])
            ->one($this->db);
    }

    /**
     * @param $id
     * @return array|bool
     */
    protected function getStruct($id)
    {
        return (new Query())
            ->select('*')
            ->from($this->tableStruct)
            ->andWhere(['id' => $id])
            ->one($this->db);
    }

    /**
     * @param int $containerId
     * @return string
     * @throws ServerErrorHttpException
     */
    private function getCardZuowenSize(int $containerId)
    {
        $model = $this->getContainer($containerId);
        $error = '容器不存在';
        if($model){
            $info = Json::decode($model['info']);
            if($info['isZuowen'] ==='Y'){
                return $info['zuowen']['size']??'small';
            }
            $error = '不是作文容器';
        }
        throw new ServerErrorHttpException($error);
    }

    /**
     * @param array $struct
     * @return array|bool
     */
    private function getStructBrotherIds(array $struct)
    {
        if ($struct['depth'] == 2) {
            $brotherIds = (new Query())
                ->select('id')
                ->from($this->tableStruct)
                ->andWhere(['parent_id' => $struct['parent_id']])
                ->column($this->db);
            return array_diff($brotherIds, [$struct['id']]);
        } else {
            return false;
        }
    }
}