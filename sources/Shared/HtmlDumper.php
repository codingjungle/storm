<?php

namespace IPS\storm\Shared;

use IPS\storm\Application;

Application::initAutoloader();

class HtmlDumper extends \Symfony\Component\VarDumper\Dumper\HtmlDumper
{
    protected string $dumpPrefix = '<pre class="CodeMirror sf-dump" id=%s data-indent-pad="%s" data-ips-text-highlighted="yes">';
    protected string $dumpSuffix = '</pre><script>Sfdump(%s)</script>';

    protected function getDumpHeader(): string
    {
        return '';
    }
//    public function dump(Data $data, $output = null, array $extraDisplayOptions = []): ?string
//    {
//        $html =  parent::dump($data, $output, $extraDisplayOptions);
//        $html = str_replace('sf-dump-expanded', 'sf-dump-expanded nohighlight', $html);
//        _p($html);
//        return $html;
//    }

//    protected function dumpLine(int $depth, bool $endOfValue = false): void
//    {
//        if (-1 === $this->lastDepth) {
//            $this->line = \sprintf($this->dumpPrefix, $this->dumpId, $this->indentPad).$this->line;
//        }
//        if ($this->headerIsDumped !== ($this->outputStream ?? $this->lineDumper)) {
//            $this->line = $this->getDumpHeader().$this->line;
//        }
//
//        if (-1 === $depth) {
//            $args = ['"'.$this->dumpId.'"'];
//            if ($this->extraDisplayOptions) {
//                $args[] = json_encode($this->extraDisplayOptions, \JSON_FORCE_OBJECT);
//            }
//            // Replace is for BC
//            $this->line .= \sprintf(str_replace('"%s"', '%s', $this->dumpSuffix), implode(', ', $args));
//        }
//        $this->lastDepth = $depth;
//
//        $this->line = mb_encode_numericentity($this->line, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
//
//        if (-1 === $depth) {
//            AbstractDumper::dumpLine(0);
//        }
//        AbstractDumper::dumpLine($depth);
//    }
}