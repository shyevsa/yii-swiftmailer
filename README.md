# Yii Swiftmailer

Very basic `symfony/mailer` wrapper for Yii1

## Config

```php
'component'=>[
    'mail' => [
            'class' => \Shyevsa\YiiSwiftmailer\Mail::class,            
            'dsn' =>"failover(smtp://no-reply%40example.com:mysecurepassword@mail.example.com:587 ses+api://ACCESS_KEY:ACCESS_SECRET@default?region=us-west-2)",            
            'viewPath' => 'application.views.mail'
    ],
]
```

## Transport Setting / DSN

Transport setting or DSN Please Check https://symfony.com/doc/current/mailer.html#using-a-3rd-party-transport

```php

//smtp
'dsn' => 'smtp://no-reply%40example.com:mysecurepassword@mail.example.com:587'

//ses+api require `symfony/amazon-mailer`
'dsn' => 'ses+api://ACCESS_KEY:ACCESS_SECRET@default?region=us-west-2'

```

## Usage

```php

$message = (new \Shyevsa\YiiSwiftmailer\YiiMail())
            ->subject('This is Subject')
            ->to('john.smith@example.com')
            ->from(Yii::app()->params['admin_email'])
            ->setHtmlTemplate('html_template')
            ->setTextTemplate('text_template')
            ->context([
               'name' => 'John Smith',
               'link' => 'https://example.com'
            ])
        ;
        
$send = Yii::app()->mail->send($message);

```