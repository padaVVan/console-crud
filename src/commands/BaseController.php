<?php

namespace padavvan\console\commands;

use padavvan\console\components\Operations;
use padavvan\console\helpers\Message as Msg;
use yii\console\Controller;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Class AccountController
 *
 * @package ledger\commands
 */
class BaseController extends Controller
{
    /**
     * @var integer
     */
    public static $currentPage = 0;

    /**
     * @var integer
     */
    public static $totalPages = 0;

    /**
     * @var
     */
    public $model;

    /**
     * @var int SQL limit
     */
    public $limit = 20;

    /**
     * @var array SQL sort
     */
    public $sort = [];

    /**
     * @param string $actionID
     *
     * @return array
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        if ($actionID === 'index') {
            $localOptions = ['limit', 'sort'];
        } else {
            $localOptions = [];
        }

        return ArrayHelper::merge($options, $localOptions);
    }

    /**
     * @param $model
     *
     * @throws \yii\base\ExitException
     */
    public function actionIndex($model)
    {
        $this->model = $model;

        $run = true;
        $operation = 'i';

        while ($run) {
            $skipMenu = false;

            Console::clearScreen();
            Console::moveCursorTo(0, 0);

            # Fonts: slant, rectangles

            $header = 'User';

            $this->fOutput($header);
            $this->output("\n");

            switch ($operation) {
                case Operations::UPDATE:
                    $this->update();
                    $operation = 'i';
                    $skipMenu = true;
                    break;
                case Operations::END:
                    \Yii::$app->end(0);
                    break;
                case Operations::PAGE_FIRST:
                    self::$currentPage = 0;
                    $this->admin();
                    break;
                case Operations::PAGE_LAST:
                    self::$currentPage = self::$totalPages;
                    $this->admin();
                    break;
                case Operations::PAGE_LEFT:
                    self::$currentPage--;
                    self::$currentPage = max(self::$currentPage, 0);
                    $this->admin();
                    break;
                case Operations::PAGE_RIGHT:
                    self::$currentPage++;
                    $this->admin();
                    break;
                case 'i':
                default:
                    $this->admin();
                    break;
            }

            if (!$skipMenu) {
                $operation = $this->select(
                    'Chose operation',
                    [
                        '<<' => 'First',
                        '<'  => 'Prev',
                        '>'  => 'Next',
                        '>>' => 'Last',
                        'c'  => 'Create',
                        'r'  => 'Read',
                        'u'  => 'Update',
                        'd'  => 'Delete',
                        'i'  => 'Index',
                        'e'  => 'Exit',
                    ]
                );
            }
        }
    }

    /**
     * @param null $id
     *
     * @throws \yii\base\ExitException
     */
    public function update($id = null)
    {
        if ($id === null) {
            $id = $this->prompt('Enter record ID:');
            Console::moveCursorUp();
            Console::clearLine();
        }

        $model = $this->findModel($id);

        $header = Msg::str('Change record: %s', [$id])->compile();
        $length = Console::ansiStrlen($header);

        echo $header."\n";
        Msg::delimiter($length > 80 ? 80 : $length, [], '=')->output();

        foreach ($model->attributes as $key => $value) {
            $printValue = strlen($value) < 10 ? $value : sprintf('%\'.-13.10s', $value);
            $msg = $this->fOutput('%s [%s]:', [strtoupper($key), $printValue], true);
            $input = $this->prompt($msg);
            $model->$key = $input ?: $value;
        }

        if ($model->save()) {
            Msg::success('Record updated');
            sleep(1);
        } else {
            \Yii::$app->end(2);
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     * @throws \yii\base\ExitException
     */
    private function findModel($id)
    {
        $model = $this->model::findOne($id);

        if ($model === null) {
            Msg::danger('Record [%s] not found', [$id])->output();
            \Yii::$app->end(1);
        }

        return $model;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function admin()
    {
        $dataProvider = new ActiveDataProvider(
            [
                'query'      => $this->model::find(),
                'pagination' => [
                    'pageSize' => $this->limit,
                    'page'     => self::$currentPage,
                ],
            ]
        );

        if ($dataProvider->totalCount) {
            $dataProvider->getPagination()->totalCount = $dataProvider->totalCount;
            self::$totalPages = $dataProvider->getPagination()->getPageCount() - 1;
            Msg::str(
                'Total: %s records. Pagination: %s page from %s pages',
                [
                    \Yii::$app->formatter->asDecimal($dataProvider->totalCount),
                    self::$currentPage + 1,
                    $dataProvider->getPagination()->getPageCount(),
                ]
            )->output();
        }

        // HEADER
        // ======

        $header = Msg::str('|'.str_repeat(' %s |', count($this->getHeaderConfig())), $this->getHeaderConfig())->compile(
        );
        $this->delimiter(Console::ansiStrlen($header));
        echo "$header\n";
        $this->delimiter(Console::ansiStrlen($header));

        // BODY
        // ====
        //
        $conf = $this->getColumnConfig();
        foreach ($dataProvider->models as $model) {
            $row = $this->createRow($model->getAttributes(array_keys($conf)), array_values($conf));
            Msg::str('|'.str_repeat(' %s |', count($row)), $row)->output();
        }
        $this->delimiter(Console::ansiStrlen($header));
    }

    /**
     * @return array
     */
    public function getHeaderConfig()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getColumnConfig()
    {
        return [];
    }

    /**
     * @param $values
     * @param $config
     *
     * @return array
     */
    private function createRow($values, $config)
    {
        return array_map(
            function ($value, $tpl) {
                return sprintf($tpl, $value);
            },
            $values,
            $config
        );
    }

    /**
     * @param $id
     *
     * @throws \yii\base\ExitException
     */
    public function actionUpdate($id)
    {
        $this->update($id);
    }

    /**
     * @throws \yii\base\InvalidParamException
     */
    public function actionCreate()
    {
        $model = new $this->model;

        $this->render(
            'create',
            [
                'model' => $model,
            ]
        );
    }

    /**
     * @param $id
     *
     * @throws \yii\base\ExitException
     */
    public function actionDelete($id)
    {
        if (!Console::confirm('Are you sure you want to delete the record?')) {
            \Yii::$app->end(1);
        }

        $model = $this->findModel($id);
        if ($model->delete()) {
            Msg::success('Record [%s] has been deleted', [$id])->output();
        }
    }
}
