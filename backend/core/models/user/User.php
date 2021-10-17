<?php

namespace core\models\user;

use core\components\ErrorLog;
use core\components\ExecutionResult;
use core\components\ExtendedActiveRecord;
use core\components\helpers\CookieHelper;
use core\components\helpers\DateHelper;
use core\components\UserRegisterData;
use core\models\token\TokenGroupGenerator;
use Yii;

/**
 * This is the model class for table "user.user".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $creationDate
 */
class User extends ExtendedActiveRecord
{
    public static function tableName()
    {
        return 'user.user';
    }

    public function rules()
    {
        return [
            [['name', 'email', 'password', 'creationDate'], 'required'],
            [['name', 'email', 'password', 'creationDate'], 'string'],
            [['password'], 'string', 'max' => 64],
        ];
    }

    public static function register(UserRegisterData $data): ExecutionResult
    {
        $user = new self([
            'name' => $data->getName(),
            'email' => $data->getEmail(),
            'password' => Yii::$app->security->generatePasswordHash($data->getPassword()),
            'creationDate' => DateHelper::now()
        ]);

        return new ExecutionResult($user->save(), $user->getFirstErrors(), ['id' => $user->id]);
    }

    public function login(?array $tokens = null): bool
    {
        $tokens ??= (new TokenGroupGenerator())->issueTokenGroup($this);
        if (!$tokens) {
            return false;
        }

        CookieHelper::setCookie(TokenGroupGenerator::ACCESS_TOKEN_NAME, $tokens['access']['token'], $tokens['access']['expirationDate']);
        CookieHelper::setCookie(TokenGroupGenerator::REFRESH_TOKEN_NAME, $tokens['refresh']['token'], $tokens['refresh']['expirationDate']);

        return true;
    }
}