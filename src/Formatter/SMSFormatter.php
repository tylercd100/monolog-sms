<?php

namespace Tylercd100\Monolog\Formatter;

use Monolog\Formatter\LineFormatter;

/**
* SMS - Monolog Formatter
*/
class SMSFormatter extends LineFormatter
{
    
    const SIMPLE_FORMAT = "%level_name%: %message% %context% %extra%";

    /**
     * @param string $format                     The format of the message
     * @param bool   $allowInlineLineBreaks      Whether to allow inline line breaks in log entries
     * @param bool   $ignoreEmptyContextAndExtra
     */
    public function __construct($format = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = true)
    {
        $dateFormat = null;
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }
}
