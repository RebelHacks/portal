<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Save times in UTC whatever the server's php.ini says. Pages show Las Vegas time (twig.yaml).
        date_default_timezone_set('UTC');

        parent::boot();
    }
}
