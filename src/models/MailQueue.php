<?php
/**
 * @link https://github.com/tigrov/yii2-mailqueue
 * @author Sergei Tigrov rrr-r@ya.ru
 */

namespace tigrov\mailqueue\models;

use tigrov\mailqueue\Message;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%mail_queue}}".
 *
 * @property integer $id
 * @property string $unique_key unique key for the mail message, new message with the same key will replace old one
 * @property string $message_data
 * @property integer $attempts count of attempts to send mail message
 * @property string $send_at date and time when mail message will be sent
 */
class MailQueue extends ActiveRecord
{
    private $_data;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return \Yii::$app->getMailer()->table;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['message_data'], 'required'],
            [['unique_key'], 'string'],
            [['attempts'], 'integer'],
            [['send_at'], 'safe'],
        ];
    }

    /**
     * @param integer|string $interval seconds or an interval specification to delay of sending the mail message, see http://php.net/manual/en/dateinterval.construct.php
     */
    public function applyDelay($interval)
    {
        if ($interval) {
            if (is_integer($interval)) {
                $interval = 'PT' . $interval . 'S';
            }

            $this->setSendAt((new \DateTime)->add(new \DateInterval($interval))->format('Y-m-d H:i:s'));
        }
    }

    /**
     * @param string $datetime
     */
    public function setSendAt($datetime)
    {
        $this->send_at = $datetime;
    }

    /**
     * @param string $key
     */
    public function setUniqueKey($key)
    {
        $this->unique_key = $key;
    }

    /**
     * @param string $name
     * @param array $params
     */
    public function setData($name, $params)
    {
        $this->_data[$name] = $params;
    }

    /**
     * @param string $name
     * @param array $params
     */
    public function addData($name, $params)
    {
        $this->_data[$name][] = $params;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        /* @var $messageClass Message */
        $messageClass = \Yii::$app->getMailer()->messageClass;

        return (new $messageClass)->initFromModel($this);
    }

    /**
     * @inheritdoc
     *
     * Replace old data if unique_key already exists.
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($this->unique_key !== null) {
            if ($model = static::findOne(['unique_key' => $this->unique_key])) {
                $model->setAttributes($this->getDirtyAttributes());
                $model->decodeData();

                $result = $model->save();

                $this->setAttributes($model->getAttributes(), false);
                $this->setIsNewRecord(false);
                $this->addErrors($model->getErrors());
                $this->decodeData();

                return $result;
            }
        }

        return parent::insert($runValidation, $attributes);
    }

    public function decodeData()
    {
        $this->_data = json_decode($this->message_data, true);
    }

    public function encodeData()
    {
        $this->message_data = json_encode($this->_data);
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $this->encodeData();

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->encodeData();

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->decodeData();

        parent::afterFind();
    }
}
