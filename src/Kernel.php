<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Override pour garantir que kernel.share_dir est toujours défini,
     * indépendamment de APP_SHARE_DIR dans $_SERVER (PHP-FPM clear_env).
     */
    public function getShareDir(): string
    {
        return $this->getProjectDir().'/var/share';
    }
}
