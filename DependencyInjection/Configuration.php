<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 10/04/2017
 * Time: 15:30
 */

namespace MSDev\DoctrineFileMakerDriverBundle\DependencyInjection;

use MSDev\DoctrineFileMakerDriverBundle\Entity\WebContent;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('doctrine_file_maker_driver_bundle');
        // BC for symfony/config < 4.2
        $rootNode = method_exists($treeBuilder, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('doctrine_file_maker_driver_bundle');
        $rootNode
            ->children()
                ->scalarNode('valuelist_layout')
                    ->defaultValue(false)
                    ->end()
                ->scalarNode('javascript_translations')
                    ->defaultValue(false)
                    ->end()
                ->variableNode('content_class')
                    ->defaultValue(WebContent::class)
                    ->end()
            ->end();

        return $treeBuilder;
    }
}