<?php
namespace Rnr\Utils;

final class RGB {

    public function __construct(
        public int $r,
        public int $g,
        public int $b
    ) {}

    public function toAnsi(bool $backgroundColor = false) : string {
        if($backgroundColor) {
            return "\e[48;2;{$this->r};{$this->g};{$this->b}m\e[38;2;" . ($this->g > 128 ? '0;0;0m' : '255;255;255m');
        } else {
            return "\e[38;2;{$this->r};{$this->g};{$this->b}m";
        }
    }

    public function toCSS(bool $backgroundColor = false) : string {
        if($backgroundColor) {
            return "background: rgb({$this->r}, {$this->g}, {$this->b}); color:" . ($this->g > 128 ? 'rgb(0, 0, 0);' : 'rgb(255, 255, 255);');
        } else {
            return "color: rgb({$this->r}, {$this->g}, {$this->b});";
        }
    }

}

enum LogMessageType {
    case Error ;
    case SQL;
    case Runtime;
    case Notice;

    public function getString() : string {
        return match($this) {
            self::Error => 'Error',
            self::SQL => 'SQL',
            self::Runtime => 'Runtime',
            self::Notice => 'Notice'
        };
    }

    public function getColor() : RGB {
        return match($this) {
            self::Error => new RGB(192, 0, 0),
            self::SQL => new RGB(0, 192, 0),
            self::Runtime => new RGB(255, 160, 0),
            self::Notice => new RGB(0, 160, 255)
        };
    }
}


enum LogOutput {
    case File;
    case HTML;
    case Console;

    public function getMessageType(LogMessageType $logType, string $caller = '') {
        $caller = $caller != '' ? ' | ' . $caller : '';
        return match($this) {
            self::File => "[[ {$logType->getString()} {$caller}]] ",
            self::HTML => '<span style="' . $logType->getColor()->toCSS(true) . '>' . $logType->getString() . $caller . '</span> ',
            self::Console => $logType->getColor()->toAnsi(true) . '  ' . $logType->getString() . $caller . "  \e[0m "
        };
    }
}


class Logger
{

    public function __construct(public LogOutput $outputType)
    {
    }


    public function write(LogMessageType $messageType, string $message) : void
    {
        echo($this->outputType->getMessageType($messageType, $this->getCaller()) . $message . "\n");
    }

    private function getCaller(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        // 0 = tato metoda
        // 1 = logger->log()
        // 2 = skutečný volající
        $caller = $trace[2] ?? null;

        if (!$caller) {
            return '';
        }

        $class = $caller['class'] ?? '';
        $func  = $caller['function'] ?? '';
        $line  = $caller['line'] ?? '';
        $file  = basename($caller['file'] ?? '');

        return "{$class}::{$func} ({$file}:{$line})";
    }

}