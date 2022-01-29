<?php

namespace Shyevsa\YiiSwiftmailer;

class YiiMail extends \Symfony\Component\Mime\Email
{
    private ?string $_html_template = null;

    private ?string $_text_template = null;

    private array $context = [];

    /**
     * @return string|null
     */
    public function getHtmlTemplate(): ?string
    {
        return $this->_html_template;
    }

    /**
     * @param string|null $html_template
     * @return YiiMail
     */
    public function setHtmlTemplate(?string $html_template): YiiMail
    {
        $this->_html_template = $html_template;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTextTemplate(): ?string
    {
        return $this->_text_template;
    }

    /**
     * @param string|null $text_template
     * @return YiiMail
     */
    public function setTextTemplate(?string $text_template): YiiMail
    {
        $this->_text_template = $text_template;
        return $this;
    }

    public function context(array $context): YiiMail
    {
        $this->context = $context;
        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

}