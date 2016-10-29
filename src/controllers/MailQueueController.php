<?php
/**
 * @link https://github.com/tigrov/yii2-mailqueue
 * @author Sergei Tigrov rrr-r@ya.ru
 */

namespace tigrov\mailqueue\controllers;

class MailQueueController extends \yii\console\Controller
{
    public $defaultAction = 'sending';

    /**
     * This command send mails from queue
     */
    public function actionSending()
    {
        \Yii::$app->getMailer()->sending();
    }
}
