<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 10/04/2017
 * Time: 15:30
 */

namespace MSDev\DoctrineFileMakerDriverBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class DoctrineFileMakerDriverExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {

        $configuration = new Configuration();
        $processedConfig = $this->processConfiguration( $configuration, $configs );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $sd = $container->getDefinition( 'fm.valuelist_service' );
        $sd->addMethodCall( 'setValuelistLayout', array( $processedConfig[ 'valuelist_layout' ] ) );

    }

}
