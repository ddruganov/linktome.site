<?php

namespace core\models\landing;

use core\components\CreatableInterface;
use core\components\ExecutionResult;
use core\components\ExtendedActiveRecord;
use core\components\helpers\DateHelper;
use core\models\common\ModelType;
use yii\db\Query;

/**
 * @property int $id
 * @property string $creationDate
 * @property int $creatorId
 * @property int $parentId
 * @property int $landingId
 * @property int $modelTypeId
 * @property int $weight
 */
class LandingEntity extends ExtendedActiveRecord implements CreatableInterface
{
    // public array $entities = [];

    public static function tableName()
    {
        return 'landing.landing_entity';
    }

    public function rules()
    {
        return [
            [['creationDate', 'creatorId', 'landingId', 'weight'], 'required'],
            [['creationDate'], 'date', 'format' => 'php:Y-m-d H:i:s'],
            [['creatorId', 'landingId', 'weight'], 'integer'],
            // [['entities'], 'filter', 'filter' => [$this, 'saveEntities']],
        ];
    }

    public function saveEntities(array $entities)
    {
        foreach ($entities as $child) {
            $model = self::findOne($child['id']);
            $model->setAttributes($child);
            !$model->save() && $this->addError('entities', @reset($model->getFirstErrors()));
        }

        return [];
    }

    public static function create(array $attributes): ExecutionResult
    {
        $landingId = $attributes['landingId'];
        $modelTypeId = $attributes['modelTypeId'];
        $weight = ((new Query())
            ->select(['max(weight)'])
            ->from(self::tableName())
            ->where(['landing_id' => $landingId])
            ->scalar() ?: 0) + 10;

        $model = new self([
            'creationDate' => DateHelper::now(),
            'creatorId' => $attributes['userId'],
            'parentId' => $attributes['parentId'] ?? null,
            'landingId' => $attributes['landingId'],
            'modelTypeId' => $modelTypeId,
            'weight' => $weight
        ]);

        if (!$model->save()) {
            return new ExecutionResult(false, $model->getFirstErrors());
        }

        return $model->getBoundModelClass()::create([
            'id' => $model->id,
            'name' => 'Новая ' . match ($modelTypeId) {
                ModelType::LANDING_LINK_GROUP => 'группа',
                ModelType::LANDING_LINK => 'ссылка'
            }
        ]);
    }

    public function saveAttributes(array $attributes): ExecutionResult
    {
        $this->setAttributes($attributes);
        $boundModel = $this->getBoundModel();
        $boundModel->setAttributes($attributes);

        return new ExecutionResult($this->save() && $boundModel->save(), array_merge($this->getFirstErrors(), $boundModel->getFirstErrors()));
    }

    public function delete()
    {
        $boundModel = $this->getBoundModel();
        if ($boundModel->delete() === false) {
            $this->addErrors($boundModel->getErrors());
            return false;
        }

        return parent::delete();
    }

    public function getBoundModelClass(): string
    {
        return ModelType::getModelClassById($this->modelTypeId);
    }

    public function getBoundModel(): LandingLink|LandingLinkGroup
    {
        return $this->getBoundModelClass()::findOne($this->id);
    }
}