<?php

namespace core\models\token;

use core\components\ExtendedActiveRecord;
use core\components\helpers\DateHelper;
use core\models\user\User;
use Firebase\JWT\JWT;
use Throwable;

/**
 * @property int $id
 * @property string $value
 * @property bool $isBlacklisted
 */
class AccessToken extends ExtendedActiveRecord
{
    public static function tableName()
    {
        return 'token.access_token';
    }

    public function rules()
    {
        return [
            [['value', 'isBlacklisted'], 'required'],
            [['value'], 'string'],
            [['isBlacklisted'], 'boolean'],
        ];
    }

    public function verify(): bool
    {
        $user = User::findOne($this->getUserId());
        if (!$user) {
            return false;
        }

        try {
            JWT::decode($this->value, $user->password, ['HS256']);
        } catch (Throwable $t) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->getExpirationDate() < DateHelper::now();
    }

    public function isEmpty(): bool
    {
        return !$this->value;
    }

    public function blacklist()
    {
        $this->is_blacklisted = true;
        $this->save();
    }

    public function onLogout()
    {
        $this->blacklist();
        $this->verify();

        $model = new RefreshToken(['userId' => $this->getUserId()]);
        $model->voidCurrentTokens();
    }

    public function getUserId(): ?int
    {
        return $this->getJwtData('userId');
    }

    public function getIssueDate(): ?string
    {
        return $this->getJwtData('issueDate');
    }

    public function getExpirationDate(): ?string
    {
        return $this->getJwtData('expirationDate');
    }

    private function getJwtData(string $key)
    {
        if ($this->isEmpty()) {
            return null;
        }

        $payload = json_decode(base64_decode(explode('.', $this->value)[1]), true);

        return $payload[$key] ?? null;
    }
}
