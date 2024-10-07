<?php
/**
 * @link https://github.com/tigrov/yii2-mailqueue
 * @author Sergei Tigrov rrr-r@ya.ru
 */

namespace tigrov\mailqueue;

use tigrov\mailqueue\models\MailQueue;
use yii\db\Expression;

class Mailer extends \yii\symfonymailer\Mailer
{
    /**
     * @var string message default class name.
     */
	public $messageClass = Message::class;

    /**
     * @var string table name for the model class MailQueue
     */
    public $table = '{{%mail_queue}}';

    /**
     * @var string model default class name
     */
    public $modelClass = MailQueue::class;

    /**
     * @var integer maximum attempts to send an mail message
     */
    public $maxAttempts = 5;

    /**
     * @var integer[]|string[] seconds or interval specifications to delay between attempts to send a mail message, see http://php.net/manual/en/dateinterval.construct.php
     */
    public $attemptIntervals = [0, 'PT10M', 'PT1H', 'PT6H'];

    /**
     * @var bool indicator to remove mail messages which were not sent in `maxAttempts`
     */
    public $removeFailed = true;

    /**
     * @var integer number of mail messages which could be sent per `periodSeconds`
     */
    public $maxPerPeriod = 10;

    /**
     * @var float period in seconds which indicate the time interval for `maxPerPeriod` option
     */
    public $periodSeconds = 1;

    /**
     * Sending all actual mail messages from the queue
     */
    public function sending()
    {
        /* @var $modelClass MailQueue */
        $modelClass = $this->modelClass;
        $list = $modelClass::find()
            ->where(['and', ['<', 'attempts', $this->maxAttempts], ['<=', 'send_at', new Expression('now()')]])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $timeUntil = microtime(true) + $this->periodSeconds;
        for ($i = 0; $i < count($list); ++$i) {
            // Pause if maximum mail messages were already sent per period
            if (($i + 1) % $this->maxPerPeriod == 0) {
                if ($timeUntil > microtime(true)) {
                    time_sleep_until($timeUntil);
                }
                $timeUntil = microtime(true) + $this->periodSeconds;
            }

            /* @var $model MailQueue */
            $model = $list[$i];
            if ($model->getMessage()->send($this)) {
                // Delete from the queue if the mail message was successfully sent
                $model->delete();
            } else {
                ++$model->attempts;
                if ($this->removeFailed && $model->attempts >= $this->maxAttempts) {
                    // Delete from the queue if the number of attempts to send the mail message are exhausted
                    $model->delete();
                } else {
                    $model->applyDelay($this->getAttemptInterval($model->attempts));
                    $model->save(false);
                }
            }
        }
    }

    /**
     * Get time interval of an attempt
     *
     * @param $attempt number of the attempt
     * @return integer|string seconds or interval specifications, see http://php.net/manual/en/dateinterval.construct.php
     */
    public function getAttemptInterval($attempt)
    {
        if (!$this->attemptIntervals) {
            return 0;
        }

        $index = $attempt - 1;
        if ($index <= 0) {
            $index = 0;
        } elseif ($index >= count($this->attemptIntervals)) {
            $index = count($this->attemptIntervals) - 1;
        }

        return $this->attemptIntervals[$index];
    }
}