<?php

namespace Shyevsa\YiiSwiftmailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\EventListener\MessageListener;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class Mail extends \CApplicationComponent
{
    /**
     * @var bool Disable actually sending email and dump it to file instead
     */
    public bool $dryRun = false;

    /**
     * @var string DSN for Connection
     * Please check https://symfony.com/doc/current/mailer.html#using-built-in-transports for details
     */
    public string $dsn = 'sendmail://default';

    /**
     * @var string Default Template Location
     */
    public string $viewPath = 'application.views.mail';

    /**
     * @var string Default Text Template Location
     */
    public string $textViewPath = 'application.views.mail.text';

    /**
     * @var array The Transport Options
     */
    public array $transportOptions;

    /**
     * @var array|null|false the HTML to Text Converter Options
     * Default to `null` to use `League\HTMLToMarkdown\HtmlConverter`
     * Set to `false` to disable HTML conversion
     */
    public $converter;

    /**
     * @var TransportInterface
     */
    private TransportInterface $_transport;

    /**
     * @var MailerInterface
     */
    private MailerInterface $_mailer;

    /**
     * @var EventDispatcherInterface
     */
    private $_event_dispatcher;

    /**
     * @param YiiMail $message
     * @return int
     * @throws \CException
     */
    public function send(Email $message): int
    {
        if ($this->dryRun) {
            return count($message->getTo());
        }

        try {
            $this->getMailer()->send($message);
            return count($message->getTo());
        } catch (TransportExceptionInterface $e) {
            \Yii::log($e->getDebug(), \CLogger::LEVEL_ERROR, __METHOD__);
            return 0;
        }
    }

    /**
     * @return MailerInterface
     * @throws \CException
     */
    public function getMailer()
    {
        if (!isset($this->_mailer)) {
            $this->_mailer = new Mailer($this->getTransport(), null, $this->getEventDispatcher());
        }

        return $this->_mailer;
    }

    /**
     * @throws \CException
     */
    public function getTransport(): TransportInterface
    {
        if (!isset($this->_transport)) {
            $this->_transport = Transport::fromDsn($this->dsn, $this->getEventDispatcher());
        }

        return $this->_transport;
    }

    /**
     * @throws \CException
     */
    public function getEventDispatcher()
    {
        if (!isset($this->_event_dispatcher)) {
            $controller = \Yii::app()->getController() ?? new \CController('YiiMailer');
            $messageListener = new MessageListener(null,
                new BodyRenderer($controller, [], $this->viewPath, $this->textViewPath, $this->converter));

            $this->_event_dispatcher = new EventDispatcher();
            $this->_event_dispatcher->addSubscriber($messageListener);
        }

        return $this->_event_dispatcher;
    }

}