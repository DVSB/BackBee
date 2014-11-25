<?php
namespace BackBuilder\Resources;

use Composer\Script\Event;

class Installer {
    public static function downloadBBCoreJS(Event $event) {
        exec('git clone https://github.com/Lp-digital/BbCoreJs.git ' . __DIR__ . DIRECTORY_SEPARATOR . 'toolbar');
        putenv('BOWERPHP_TOKEN=ce486984b0412db0fffeabf526a0a22af364c07d');
    }
}