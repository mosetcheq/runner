<?php
namespace Rnr\Utils;
use \Throwable;

class ErrorHandler
{

    public function __construct()
    {
        // register_shutdown_function([$this, 'shutdownHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    public function shutdownHandler()
    {
        $status = debug_backtrace();
        print_r($status);
    }

    public function exceptionHandler(Throwable $e)
    {
        $trace = $e->getTrace();
        $html = '<h1 class="' . get_class($e) . '">' . $e->getMessage() . '</h1>';
        if(!empty($trace)) {
            $trace = array_reverse($trace);
            $html.= '<ul>';
            foreach($trace as $t) {
                $html.= '<li><a href="#' . md5($t['file'] . $t['function']) . '">' . basename($t['file']) . ':' . $t['line'] . '</a></li>';
            }
            $html.= '<li><a href="#' . md5($e->getFile()) . '">' . basename($e->getFile()) . ':' . $e->getLine() . '</a></li></ul>';
            foreach($trace as $t) {
                $html.= $this->getFileAsHTML($t['file'], $t['line'], true, $t['class'] ?? '', $t['function']);
            }
        }
        $html.= $this->getFileAsHTML($e->getFile(), $e->getLine());
        echo($this->renderHTML($html));
    }


    private function getFileAsHTML(string $filename, ?int $lineNumber, bool $hidden = false, ?string $class = null, ?string $func = null, int $unfold = 8) : string
    {
        $file = file($filename);
        $html = '<div id="' . md5($filename . ($func ?? '')) . '" class="file' . ($hidden ? ' hide' : '') . '"><h2>' . $filename;
        $html.= ($func != null ? ': <em>' . ($class != null ? $class .'::' : '') . $func . '</em>': '') . '</h2>';
        $html.= '<div class="source">';
        if($lineNumber == null) {
            $startLine = 0;
            $endLine =  count($file) - 1;
        } else {
            $lineNumber--;
            $startLine = $lineNumber - $unfold;
            $endLine = $lineNumber + $unfold;

            if($startLine < 0) $startLine = 0;
            if($endLine >= count($file)) $endLine = count($file) - 1;
        }

        for($c = $startLine; $c <= $endLine; $c++)
        {
            $html.= '<div' . ($lineNumber == $c ? ' class="highlight"' : '') . '><span class="num">' . ($c + 1) . '</span>' . str_replace(' ', '&nbsp;', htmlspecialchars($file[$c])) . '</div>';
        }
        $html.= '</div></div>';
        return $html;
    }


    private function renderHTML(string $content) : string
    {
        $output = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><style>';
        $output.= 'body {font-family: helvetica, sans-serif; font-size: 1em; margin: 0; padding: 0;}';
        $output.= '.source {font-family: "Lucida Console", courier;}';
        $output.= '.highlight {background: #f00; color: #fff; font-weight: bold;}';
        $output.= '.num {text-align: right; color: #aaa; display: inline-block; width: 48px; margin-right: 4px;}';
        $output.= 'ul {list-type: none; padding: 8px; margin: 0;}';
        $output.= 'li {display: inline; padding: 0; margin: 0 4px;}';
        $output.= 'a {text-decoration: none; display: inline-block; border: 1px solid #ccc; background: #eee; color: #000; padding: 4px 8px;}';
        $output.= 'h1 {color: #fff; background: #000; padding: 32px; margin: 0;}';
        $output.= 'h2 {padding: 16px 32px; margin: 0;}';
        $output.= '.hide {display: none;}';
        $output.= '.ParseError {background: #a00;}';
        $output.= '.PDOException {background: #0a0;}';
        $output.= '.Exception {background: #fa0;}';
        $output.= '</style><body>' . $content . '</body><script type="text/javascript">';
        $output.= 'let src = document.getElementsByClassName("file"); let a = document.getElementsByTagName("A"); for(let e of a) {e.addEventListener("click", (ev) => {';
        $output.= 'ev.preventDefault(); let id = ev.target.getAttribute("href").substring(1); for(let s of src) {console.log(s.id); s.style.display = (s.id == id ? "block" : "none");} });}';
        $output.= '</script></html>';
        return $output;
    }
}