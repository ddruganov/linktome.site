<?php

namespace core\models\landing;

use core\components\CreatableInterface;
use core\components\ExecutionResult;
use core\components\ExtendedActiveRecord;
use core\components\SaveableInterface;

/**
 * @property int $id
 * @property string $name
 */
class LandingLinkGroup extends ExtendedActiveRecord implements CreatableInterface, SaveableInterface, LandingEntityInterface
{
    public static function tableName()
    {
        return 'landing.landing_link_group';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string'],
        ];
    }

    public static function create(array $attributes): ExecutionResult
    {
        $model = new self([
            'id' => $attributes['id'],
            'name' => 'Новая группа ссылок'
        ]);

        return new ExecutionResult(
            $model->save(),
            $model->getFirstErrors()
        );
    }

    public function saveFromAttributes(array $attributes): ExecutionResult
    {
        $this->setAttributes([
            'name' => $attributes['name'],
        ]);

        if (!$this->save()) {
            return new ExecutionResult(false, $this->getFirstErrors());
        }

        foreach ($attributes['children'] as $child) {
            $childSaveRes = LandingEntity::findOne($child['id'])->saveFromAttributes($child);
            if (!$childSaveRes->isSuccessful()) {
                return $childSaveRes;
            }
        }

        return new ExecutionResult(true);
    }

    public function delete()
    {
        foreach ($this->getChildren() as $child) {
            if ($child->delete() === false) {
                $this->addErrors($child->getErrors());
                return false;
            }
        }

        return parent::delete();
    }

    /**
     * @return LandingEntity[]
     */
    public function getChildren(): array
    {
        return LandingEntity::findAll(['parent_id' => $this->id]);
    }

    public function getData(): array
    {
        return [
            'name' => $this->name,
            'children' => array_map(fn (LandingEntityInterface $landingEntityInterface) => $landingEntityInterface->getData(), $this->getChildren())
        ];
    }
}
