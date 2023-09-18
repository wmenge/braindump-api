<?php namespace Braindump\Api\Lib\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\ElementInterface;

class StrikeThroughConverter implements ConverterInterface
{
    protected function getNormTag(?ElementInterface $element): string
    {
        if ($element !== null && ! $element->isText()) {
            return $element->getTagName();
        }
        return "";
    }

    public function convert(ElementInterface $element): string
    {
        $tag   = $this->getNormTag($element);
        $value = $element->getValue();

        $style = "~~";

        $prefix = \ltrim($value) !== $value ? ' ' : '';
        $suffix = \rtrim($value) !== $value ? ' ' : '';

        /* If this node is immediately preceded or followed by one of the same type don't emit
         * the start or end $style, respectively. This prevents <em>foo</em><em>bar</em> from
         * being converted to *foo**bar* which is incorrect. We want *foobar* instead.
         */
        $preStyle  = $this->getNormTag($element->getPreviousSibling()) === $tag ? '' : $style;
        $postStyle = $this->getNormTag($element->getNextSibling()) === $tag ? '' : $style;

        return $prefix . $preStyle . \trim($value) . $postStyle . $suffix;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['del'];
    }
}