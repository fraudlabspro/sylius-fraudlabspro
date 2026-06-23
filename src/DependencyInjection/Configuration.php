<?php

declare(strict_types=1);

namespace FraudLabsPro\SyliusFraudLabsProPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('fraud_labs_pro_sylius_fraud_labs_pro_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
