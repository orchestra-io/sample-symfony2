<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Acme\DemoBundle\AcmeDemoBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Symfony\Bundle\WebConfiguratorBundle\SymfonyWebConfiguratorBundle();
        }

        return $bundles;
    }

    public function getCacheDir()
    {
        if ($this->getEnvironment() == 'prod') {
            if (!is_dir(sys_get_temp_dir() . '/sf2standard/cache')) {
                mkdir(sys_get_temp_dir() . '/sf2standard/cache', 0777, true);
            }

            return sys_get_temp_dir() . '/sf2standard/cache';
        }

        return parent::getCacheDir();
    }

    public function getLogDir()
    {
        if ($this->getEnvironment() == 'prod') {
            if (!is_dir(sys_get_temp_dir() . '/sf2standard/logs')) {
                mkdir(sys_get_temp_dir() . '/sf2standard/logs', 0777, true);
            }


            return sys_get_temp_dir() . '/sf2standard/logs';
        }

        return parent::getLogDir();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
