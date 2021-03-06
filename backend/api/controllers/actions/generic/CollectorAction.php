<?php

namespace api\controllers\actions\generic;

use core\collectors\AbstractDataCollector;
use core\components\ExecutionResult;
use core\components\helpers\UserHelper;
use api\controllers\actions\ApiAction;
use core\components\Telegram;
use Throwable;
use Yii;

class CollectorAction extends ApiAction
{
    public string $collectorClass;
    private AbstractDataCollector $collector;

    public function beforeRun()
    {
        $this->collector = new $this->collectorClass;
        return parent::beforeRun();
    }

    public function run()
    {
        $params = null;
        switch ($this->collector->getDataSource()) {
            case AbstractDataCollector::DATA_SOURCE_GET:
                $params = Yii::$app->request->get();
                break;
            case AbstractDataCollector::DATA_SOURCE_JSON:
                $json = file_get_contents('php://input');
                $params = json_decode($json, true);
                break;
        }

        $params['userId'] ??= UserHelper::id();

        try {
            $this->collector->setParams($params);

            return $this->apiResponse(new ExecutionResult(true, [], $this->collector->get()));
        } catch (Throwable $t) {
            (new Telegram())
                ->setTitle('Ошибка сбора данных через ' . $this->collectorClass)
                ->setMessage($t->getMessage())
                ->setTrace($t->getTraceAsString())
                ->send();

            return $this->apiResponse(new ExecutionResult(false, ['exception' => $t->getMessage()]));
        }
    }
}
