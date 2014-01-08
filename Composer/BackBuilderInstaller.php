<?php

namespace BackBuilder\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class BackBuilderInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        return "BackBuilder";
    }


}
