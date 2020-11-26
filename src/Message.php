<?php
/**
 * @link https://github.com/tigrov/yii2-mailqueue
 * @author Sergei Tigrov rrr-r@ya.ru
 */

namespace tigrov\mailqueue;

use tigrov\mailqueue\models\MailQueue;
use yii\mail\MailerInterface;

/**
 * Extends `yii\swiftmailer\Message` to enable queuing.
 *
 * @see http://www.yiiframework.com/doc-2.0/yii-swiftmailer-message.html
 */
class Message extends \yii\swiftmailer\Message implements MessageInterface
{
    const MULTIPLE_VALUES = ['attach', 'attachContent', 'addHeader'];

    const EMBED_VALUES = ['embed', 'embedContent'];

    const BASE_ENCODED_VALUES = ['embedContent', 'attachContent'];

    private $_model;

    /**
     * Get a model of `MailQueue`
     *
     * @return MailQueue
     */
    public function getModel()
    {
        if ($this->_model === null) {
            $modelClass = \Yii::$app->getMailer()->modelClass;
            $this->_model = new $modelClass;
        }

        return $this->_model;
    }

    /**
     * Init the message using data from a model of `MailQueue`.
     *
     * @param MailQueue $model
     * @return $this
     */
    public function initFromModel(MailQueue $model)
    {
        $this->_model = $model;
        $embedIds = [];

        foreach ($model->getData() as $name => $params) {
            if (in_array($name, self::MULTIPLE_VALUES)) {
                foreach ($params as $value) {
                    if (in_array($name, self::BASE_ENCODED_VALUES) && isset($value[0])) {
                        $value[0] = base64_decode($value[0]);
                    }
                    call_user_func_array('parent::' . $name, $value);
                }
            } elseif (in_array($name, self::EMBED_VALUES)) {
                foreach ($params as list($content, $options, $id)) {
                    if (in_array($name, self::BASE_ENCODED_VALUES)) {
                        $content = base64_decode($content);
                    }
                    $embedIds[$id] = call_user_func_array('parent::' . $name, [$content, $options]);
                }
            } else {
                if ($name == 'setHtmlBody' && !empty($embedIds)) {
                    $params[0] = strtr($params[0], $embedIds);
                }
                call_user_func_array('parent::' . $name, $params);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function send(MailerInterface $mailer = null)
    {
        try {
            return parent::send($mailer);
        } catch (\Swift_TransportException $e) {
            if (554 != $e->getCode()) {
                throw $e;
            }

            $recipients = array_merge($this->getTo() ?: [], $this->getCc() ?: [], $this->getBcc() ?: []);
            \Yii::info($e->getMessage() . ' Filed recipients: ' . implode(', ', array_keys($recipients)));

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function delay($interval)
    {
        $this->getModel()->applyDelay($interval);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function unique($key)
    {
        $this->getModel()->setUniqueKey($key);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function queue()
    {
        return $this->getModel()->save();
    }

    /**
     * @inheritdoc
     */
    public function setCharset($charset)
    {
        $this->getModel()->setData('setCharset', [$charset]);

        return parent::setCharset($charset);
    }

    /**
     * @inheritdoc
     */
    public function setFrom($from)
    {
        $this->getModel()->setData('setFrom', [$from]);

        return parent::setFrom($from);
    }

    /**
     * @inheritdoc
     */
    public function setReplyTo($replyTo)
    {
        $this->getModel()->setData('setReplyTo', [$replyTo]);

        return parent::setReplyTo($replyTo);
    }

    /**
     * @inheritdoc
     */
    public function setTo($to)
    {
        $this->getModel()->setData('setTo', [$to]);

        return parent::setTo($to);
    }

    /**
     * @inheritdoc
     */
    public function setCc($cc)
    {
        $this->getModel()->setData('setCc', [$cc]);

        return parent::setCc($cc);
    }

    /**
     * @inheritdoc
     */
    public function setBcc($bcc)
    {
        $this->getModel()->setData('setBcc', [$bcc]);

        return parent::setBcc($bcc);
    }

    /**
     * @inheritdoc
     */
    public function setSubject($subject)
    {
        $this->getModel()->setData('setSubject', [$subject]);

        return parent::setSubject($subject);
    }

    /**
     * @inheritdoc
     */
    public function setTextBody($text)
    {
        $this->getModel()->setData('setTextBody', [$text]);

        return parent::setTextBody($text);
    }

    /**
     * @inheritdoc
     */
    public function setHtmlBody($html)
    {
        $this->getModel()->setData('setHtmlBody', [$html]);

        return parent::setHtmlBody($html);
    }

    /**
     * @inheritdoc
     */
    public function attach($fileName, array $options = [])
    {
        $this->getModel()->addData('attach', [$fileName, $options]);

        return parent::attach($fileName, $options);
    }

    /**
     * @inheritdoc
     */
    public function attachContent($content, array $options = [])
    {
        $this->getModel()->addData('attachContent', [base64_encode($content), $options]);

        return parent::attachContent($content, $options);
    }

    /**
     * @inheritdoc
     */
    public function embed($fileName, array $options = [])
    {
        $id = parent::embed($fileName, $options);
        $this->getModel()->addData('embed', [$fileName, $options, $id]);

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function embedContent($content, array $options = [])
    {
        $id = parent::embedContent($content, $options);
        $this->getModel()->addData('embedContent', [base64_encode($content), $options, $id]);

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function addHeader($name, $value)
    {
        $this->getModel()->addData('addHeader', [$name, $value]);

        return parent::addHeader($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function setHeader($name, $value)
    {
        $this->getModel()->setData('setHeader', [$name, $value]);

        return parent::setHeader($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function setReturnPath($address)
    {
        $this->getModel()->setData('setReturnPath', [$address]);

        return parent::setReturnPath($address);
    }

    /**
     * @inheritdoc
     */
    public function setPriority($priority)
    {
        $this->getModel()->setData('setPriority', [$priority]);

        return parent::setPriority($priority);
    }

    /**
     * @inheritdoc
     */
    public function setReadReceiptTo($addresses)
    {
        $this->getModel()->setData('setReadReceiptTo', [$addresses]);

        return parent::setReadReceiptTo($addresses);
    }
}
