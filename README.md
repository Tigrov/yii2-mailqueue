yii2-mailqueue
==============

This is a for of the original [tigrov/yii2-mailqueue](https://github.com/tigrov/yii2-mailqueue) extension for Yii2 mail queue component for [yii2-symfonymailer](https://www.yiiframework.com/extension/yiisoft/yii2-symfonymailer).

[![Latest Stable Version](https://poser.pugx.org/Tigrov/yii2-mailqueue/v/stable)](https://packagist.org/packages/Tigrov/yii2-mailqueue)

Limitation
------------

Since 1.1.6 requires PHP >= 8.1

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist tigrov/yii2-mailqueue "~1.1.1"
```

or add

```
"tigrov/yii2-mailqueue": "~1.1.6"
```

to the require section of your `composer.json` file.

 
Configuration
-------------
Once the extension is installed, add following code to your application configuration:

```php
return [
    // ...
    'components' => [
        'mailer' => [
            'class' => 'tigrov\mailqueue\Mailer',
            'table' => '{{%mail_queue}}',
            'maxAttempts' => 5,
            'attemptIntervals' => [0, 'PT10M', 'PT1H', 'PT6H'],
            'removeFailed' => true,
            'maxPerPeriod' => 10,
            'periodSeconds' => 1,
        ],
    ],
    // ...
];
```

Following properties are available for customizing the mail queue behavior.

* `table` name of the database table to store emails added to the queue;
* `maxAttempts` maximum number of sending attempts per email;
* `attemptIntervals` seconds or interval specifications to delay between attempts to send a mail message, see http://php.net/manual/en/dateinterval.construct.php;
* `removeFailed` indicator to remove mail messages which were not sent in `maxAttempts`;
* `maxPerPeriod` number of mail messages which could be sent per `periodSeconds`;
* `periodSeconds` period in seconds which indicate the time interval for `maxPerPeriod` option.


Updating database schema
------------------------

Run `yii migrate` command in command line:

```
php yii migrate/up --migrationPath=@vendor/tigrov/yii2-mailqueue/src/migrations/
```

Sending the mail queue
-------------------------

To sending mails from the queue call `Yii::$app->mailer->sending()` or run the console command `yii mailqueue` which can be triggered by a CRON job:

```
* * * * * php /var/www/vhosts/domain.com/yii mailqueue/sending
```

After the mail message successfully sent it will be deleted from the queue.

Usage
-----

You can then send a mail to the queue as follows:

```php
Yii::$app->mailer->compose('contact/html')
     ->setFrom('from@domain.com')
     ->setTo($form->email)
     ->setSubject($form->subject)
     ->setTextBody($form->body)
     ->delay('PT3M') // seconds or an interval specification to delay of sending the mail message, see http://php.net/manual/en/dateinterval.construct.php
     ->unique('unique key') // a unique key for the mail message, new message with the same key will replace the old one
     ->queue();
```

You can still send mails directly with `yii2-swiftmailer`:

```php
Yii::$app->mailer->compose('contact/html')
     ->setFrom('from@domain.com')
     ->setTo($form->email)
     ->setSubject($form->subject)
     ->setTextBody($form->body)
     ->send();
```

License
-------

[MIT](LICENSE)
