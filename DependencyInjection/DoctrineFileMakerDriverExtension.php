<?php

namespace MSDev\DoctrineFileMakerDriverBundle\DependencyInjection;

use Exception;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\FileLocator;
use MSDev\DoctrineFileMakerDriverBundle\Types\TypeRegistry;

class DoctrineFileMakerDriverExtension extends Extension implements PrependExtensionInterface
{

    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $processedConfig = $this->processConfiguration( $configuration, $configs );

        // Need to work out which driver is installed (or if they are both present)
        $services = $this->determineDriver();
        if($services) {
            $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load($services);
        }

        $sd = $container->getDefinition( 'fm.valuelist_service' );
        $sd->addMethodCall( 'setValuelistLayout', array( $processedConfig[ 'valuelist_layout' ] ) );

        $container->setParameter( 'doctrine_file_maker_driver.javascript_translations', $processedConfig[ 'javascript_translations' ] );
        $container->setParameter( 'doctrine_file_maker_driver.content_class', $processedConfig[ 'content_class' ] );

        $container->setParameter( 'doctrine_file_maker_driver.admin_server', $processedConfig[ 'admin_server' ] );
        $container->setParameter( 'doctrine_file_maker_driver.admin_port', $processedConfig[ 'admin_port' ] );
        $container->setParameter( 'doctrine_file_maker_driver.admin_username', $processedConfig[ 'admin_username' ] );
        $container->setParameter( 'doctrine_file_maker_driver.admin_password', $processedConfig[ 'admin_password' ] );
    }

    public function prepend(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['DoctrineBundle'])) {
            throw new RuntimeException('Doctrine bundle required!');
        }
        $config = [
            'dbal' => [
                'types' => (new TypeRegistry())->getDoctrineMapping(),
            ],
        ];

        $container->prependExtensionConfig('doctrine', $config);
    }

    private function determineDriver(): string
    {
        $isCWP = $this->isCWP();
        $isDAPI = $this->isDAPI();

        if(!$isCWP && !$isDAPI) {
            return false;
        }

        if(!$isCWP) {
            return 'dapi-services.yml';
        }
        if(!$isDAPI) {
            return 'cwp-services.yml';
        }

        return 'services.yml';
    }

    private function isCWP(): bool
    {
        return class_exists('\MSDev\DoctrineFileMakerDriver\FMPlatform');
    }

    private function isDAPI(): bool
    {
        return class_exists('\MSDev\DoctrineFMDataAPIDriver\FMPlatform');
    }

}
