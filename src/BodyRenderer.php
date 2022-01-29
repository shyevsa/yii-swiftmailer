<?php

namespace Shyevsa\YiiSwiftmailer;

use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Message;

class BodyRenderer implements BodyRendererInterface
{
    private \CController $controller;
    private array $context;
    private string $viewPath;
    private string $textViewPath;
    private $converter;

    /**
     * @throws \CException
     */
    public function __construct(
        \CController $controller,
        array $context = [],
        $viewPath = 'application.views.mail',
        $textViewPath = null,
        $converter = null
    ) {
        $this->controller = $controller;
        $this->context = $context;
        $this->viewPath = $viewPath;
        $this->textViewPath = $textViewPath ?? $viewPath . '.text';
        if ($converter === null) {
            $this->converter = new HtmlConverter([
                'hard_break' => true,
                'strip_tags' => true,
                'remove_nodes' => 'head style'
            ]);
        } elseif (is_array($converter)) {
            $this->converter = \Yii::createComponent($converter);
        }
    }

    public function render(Message $message): void
    {
        if (!$message instanceof YiiMail) {
            return;
        }

        $messageContext = $message->getContext();

        $previousRenderingKey = $messageContext[__CLASS__] ?? null;
        unset($messageContext[__CLASS__]);
        $currentRenderingKey = $this->getFingerPrint($message);
        if ($previousRenderingKey === $currentRenderingKey) {
            return;
        }

        if (isset($messageContext['email'])) {
            throw new InvalidArgumentException(sprintf('A "%s" context cannot have an "email" entry as this is a reserved variable.',
                get_debug_type($message)));
        }

        $vars = array_merge($this->context, $messageContext, [
            'email' => $message,
        ]);

        if ($template = $this->resolveViewFile($message->getTextTemplate(), $this->textViewPath)) {
            $message->text($this->renderInternal($template, $vars));
        }

        if ($template = $this->resolveViewFile($message->getHtmlTemplate(), $this->viewPath)) {
            $message->html($this->renderInternal($template, $vars));
        }

        if (!$message->getTextBody() && null !== $html = $message->getHtmlBody()) {
            $message->text($this->convertHtmlToText(is_resource($html) ? stream_get_contents($html) : $html));
        }

        $message->context($message->getContext() + [__CLASS__ => $currentRenderingKey]);
    }

    private function getFingerPrint(YiiMail $message): string
    {
        $messageContext = $message->getContext();
        unset($messageContext[__CLASS__]);

        $payload = [$messageContext, $message->getTextTemplate(), $message->getHtmlTemplate()];
        try {
            $serialized = serialize($payload);
        } catch (\Throwable $e) {
            $serialized = random_bytes(8);
        }

        return md5($serialized);
    }

    public function resolveViewFile($template, $path): ?string
    {
        $basePath = \Yii::app()->getViewPath();
        $viewPath = $this->controller->resolveViewFile(
            $path . '.' . $template,
            $path,
            $basePath,
            $basePath
        );

        if (!file_exists($viewPath)) {
            \Yii::log("'$template' not found in '$path'", \CLogger::LEVEL_TRACE, __METHOD__);
            return null;
        }

        return $viewPath;
    }

    public function renderInternal($template, $vars): string
    {
        return $this->controller->renderInternal($template, $vars, true);
    }

    private function convertHtmlToText(string $html): string
    {
        if (isset($this->converter)) {
            return $this->converter->convert($html);
        }

        return strip_tags(preg_replace('{<(head|style)\b.*?</\1>}is', '', $html));
    }
}