<?php
/**
 * Created by Ydb.
 * User: Tisswb
 * Date: 2020/2/13
 * Time: 20:30
 */

namespace ydb\card;


use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\Inflector;

/**
 * Class PaperCard
 * @package ydb\card
 */
class PaperCard extends Component implements BootstrapInterface
{
    /**
     * @var string|Connection db
     */
    public $db;

    public $captureHost;

    /**
     * @var string card table name
     */
    public $tableCard = '{{%card}}';

    /**
     * @var string card page table name
     */
    public $tablePage = '{{%card_page}}';

    /**
     * @var string card container table name
     */
    public $tableContainer = '{{%card_container}}';

    /**
     * @var string card container image table name
     */
    public $tableContainerImage = '{{%card_container_image}}';

    /**
     * @var string card edit area table name
     */
    public $tableEditArea = '{{%card_edit_area}}';

    /**
     * @var string card struct table name
     */
    public $tableCardStruct = '{{%card_struct}}';

    /**
     * @var string card score box
     */
    public $tableScoreBox = '{{%card_score_box}}';

    /**
     * @var string card score box struct
     */
    public $tableScoreBoxStruct = '{{%card_score_box_struct}}';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * @inheritDoc
     */
    public function bootstrap($app)
    {
        $app->controllerMap[$this->createControllerId()] = [
            'class' => PaperController::class,
            'cardComponent' => $this
        ];
    }

    /**
     * @return string Controller id
     * @throws
     */
    public function createControllerId()
    {
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        throw new InvalidConfigException('Queue must be an application component.');
    }
}