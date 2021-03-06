<?php

namespace api\controllers\actions\auth;

use api\controllers\actions\ApiAction;
use core\components\ExecutionResult;
use core\components\Telegram;
use core\models\user\behaviors\UserSocialBehavior;
use core\models\user\User;
use core\models\user\UserSocial;
use core\models\user\UserSocialType;
use core\social_network\SocialNetworkAuthFactory;
use Exception;
use Throwable;
use Yii;

class ActionSocial extends ApiAction
{
    public function run()
    {
        $socialNetwork = SocialNetworkAuthFactory::get($this->getData('alias'));

        if (!$socialNetwork) {
            return $this->apiResponse(new ExecutionResult(false, ['exception' => 'Неизвестная социальная сеть']));
        }

        $socialType = UserSocialType::fromAlias($socialNetwork->getAlias());
        if (!$socialType) {
            return $this->apiResponse(new ExecutionResult(false, ['exception' => 'Неизвестная социальная сеть']));
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $userData = $socialNetwork->getClientData($this->getData());
            if (!$userData) {
                throw new Exception('Ошибка получения данных о клиенте');
            }

            $email = $userData->getEmail();

            $user = User::findOne(['email' => $email])
                ?? UserSocial::findOne([
                    'typeId' => $socialType->getId(),
                    'value' => $userData->getSocialId()
                ])?->getUser();

            if (!$user) {
                $email ??= 'user_' . substr(md5(microtime()), 0, 8) . '@linktome.site';
                $userCreateRes = User::create([
                    'email' => $email,
                    'name' => $userData->getName(),
                ]);
                if (!$userCreateRes->isSuccessful()) {
                    throw new Exception('Ошибка регистрации через соцсеть');
                }
                $user = User::findOne($userCreateRes->getData('id'));
            }

            $user->attachBehavior('UserSocialBehavior', new UserSocialBehavior());
            !$user->getSocialValue($socialType->getId()) &&
                $user->saveSocialValue($socialType->getId(), $userData->getSocialId());

            $user->login();

            $transaction->commit();
            return $this->apiResponse(new ExecutionResult(true));
        } catch (Throwable $t) {
            (new Telegram())
                ->setTitle('Ошибка авторизации через ' . $this->getData('alias'))
                ->setMessage($t->getMessage())
                ->setTrace($t->getTraceAsString())
                ->send();

            $transaction->rollBack();
            return $this->apiResponse(new ExecutionResult(false, ['exception' => $t->getMessage()]));
        }
    }
}
