<?php

namespace app\services;

use Yii;

class MailService
{
    /**
     * @return bool
     */
    public function send($to, $subject, $textBody)
    {
        return Yii::$app->mailer->compose()
            ->setFrom($this->getFromEmail())
            ->setTo($to)
            ->setSubject($subject)
            ->setTextBody($textBody)
            ->send();
    }

    /**
     * @return array
     */
    private function getFromEmail()
    {
        return [Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']];
    }
}