<?php
namespace BrownUniversity\DOC\Log;

use Kohana\Log\Writer;

class ErrorLog extends Writer {
    public function write(array $messages) {
        $format = 'time --- level: body';

        foreach ($messages as $message) {
            $message['level'] = $this->_log_levels[$message['level']];
            error_log(PHP_EOL.strtr($format, $message));
        }
    }
}