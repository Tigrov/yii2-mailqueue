<?php
/**
 * @link https://github.com/tigrov/yii2-mailqueue
 * @author Sergei Tigrov rrr-r@ya.ru
 */

namespace tigrov\mailqueue;

/**
 * MessageInterface is the interface that should be implemented by mail message classes.
 *
 * A message represents the settings and content of an email, such as the sender, recipient,
 * subject, body, etc.
 *
 * Messages are sent by a [[\tigrov\mailqueue\MailerInterface|mailer]], like the following,
 *
 * ```php
 * Yii::$app->mailer->compose('contact/html')
 *     ->setFrom('from@domain.com')
 *     ->setTo($form->email)
 *     ->setSubject($form->subject)
 *     ->setTextBody($form->body)
 *     ->delay('PT3M') // seconds or an interval specification to delay of sending the mail message, see http://php.net/manual/en/dateinterval.construct.php
 *     ->unique('unique key') // a unique key for the mail message, new message with the same key will replace the old one
 *     ->queue();
 * ```
 *
 * You can still send mails directly with `yii2-swiftmailer`:
 *
 * ```php
 * Yii::$app->mailer->compose('contact/html')
 *     ->setFrom('from@domain.com')
 *     ->setTo($form->email)
 *     ->setSubject($form->subject)
 *     ->setTextBody($form->body)
 *     ->send();
 * ```
 *
 * @see MailerInterface
 */
interface MessageInterface extends \yii\mail\MessageInterface
{
    /**
     * @param integer|string $interval seconds or an interval specification to delay of sending the mail message, see http://php.net/manual/en/dateinterval.construct.php
     * @return $this
     */
    public function delay($interval);

    /**
     * @param string $key a unique key for the mail message, new message with the same key will replace old one
     * @return $this
     */
    public function unique($key);

    /**
     * Enqueue the mail message storing it in the table.
     *
     * @return boolean true on success, false otherwise
     */
    public function queue();
}