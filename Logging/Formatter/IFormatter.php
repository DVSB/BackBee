<?php
namespace BackBuilder\Logging\Formatter;

interface IFormatter {
    public function format($event);
}