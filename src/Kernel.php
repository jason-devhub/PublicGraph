<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Définit kernel.share_dir (requis par FrameworkBundle pour le cache app).
     * Retourner une valeur non-null garantit que %kernel.share_dir% existe dans
     * le container, indépendamment de APP_SHARE_DIR dans l'environnement.
     */
    public function getShareDir(): string
    {
        return $this->getProjectDir().'/'.($_SERVER['APP_SHARE_DIR'] ?? 'var/share');
    }
}
