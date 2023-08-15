<?php namespace Braindump\Api\Model\Sentry\Paris;

// adapted from https://github.com/briannesbitt/Carbon
class ExtendedDateTime extends \DateTime
{
    const DEFAULT_TO_STRING_FORMAT = 'Y-m-d H:i:s';
    protected static $toStringFormat = self::DEFAULT_TO_STRING_FORMAT;

    public function __toString()
    {
        return $this->format(static::$toStringFormat);
    }
}

class DateTimeModel extends \Model
{
    public function freshTimestamp() {
        return new ExtendedDateTime;
    }
}
