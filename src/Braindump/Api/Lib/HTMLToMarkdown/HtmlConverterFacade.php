<?php namespace Braindump\Api\Lib\HTMLToMarkdown;

use League\HTMLToMarkdown\HtmlConverter;
use Braindump\Api\Lib\HTMLToMarkdown\Converter\StrikeThroughConverter;

class HtmlConverterFacade
{
    const PRE_CONVERT_EXPRESSION = '/(<br\s?\/?>)\s*(<\/(i|b|em|strong|del)>)/';

    // Get a configured converter through DI
    private $converter = null;

    public function __construct()
    {
        $this->converter = new HtmlConverter(array('strip_tags' => true));
        $this->converter->getEnvironment()->addConverter(new StrikeThroughConverter());
    }

    public function convert($html) {
        return $this->converter->convert($this->preConvert($html));
    }

    /***
     * Convert <b>content<br></b> to <b>content</b><br>
     * makes for nicer markdown formatting
     */
    private function preConvert($html) {
        return preg_replace(HtmlConverterFacade::PRE_CONVERT_EXPRESSION, '$2$1', $html);
    }
}