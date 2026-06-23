<?php

declare(strict_types=1);

namespace FraudLabsPro\SyliusFraudLabsProPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class FraudLabsProSyliusFraudLabsProPluginExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $loader->load('services.xml');
		
		// 🛠️ ADD THIS BLOCK TO MAP YOUR ENTITY
        // $configDir = dirname(__DIR__, 2) . '/Resources/config/doctrine';
		
		// $srcPath = dirname(__DIR__, 2);
		// $doctrineConfigPath = $srcPath . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'doctrine';
		// if (!is_dir($doctrineConfigPath)) {
			// throw new \RuntimeException(sprintf('The directory "%s" does not exist. Check your folder structure.', $doctrineConfigPath));
		// }
        
        // $container->prependExtensionConfig('doctrine', [
            // 'orm' => [
                // 'mappings' => [
                    // 'FraudLabsProSyliusFraudLabsProPlugin' => [
                        // 'type' => 'xml',
                        // 'dir' => 'C:\\OoiKaiWen\\work\\code\\sylius\\SyliusFraudLabsProPlugin\\src\\Resources\\config\\doctrine',
                        // 'prefix' => 'FraudLabsPro\SyliusFraudLabsProPlugin\Entity',
                        // 'alias' => 'FraudLabsProSyliusFraudLabsProPlugin',
                        // 'is_bundle' => false,
                    // ],
                // ],
            // ],
        // ]);
    }

    public function prepend(ContainerBuilder $container): void
    {
		// 🚨 SCREAM TEST: If Symfony is reading this file, the application will halt and print this exact message.
        // dd('🚨 PREPEND IS FIRING! 🚨');
        // 🛠️ Updated for Sylius 2.x Twig Hooks architecture
        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                'sylius_admin.channel.update.content.form.sections' => [
                    'fraudlabs_pro_api_key' => [
                        'template' => '@FraudLabsProSyliusFraudLabsProPlugin/Admin/Channel/_fraudLabsProApiKey.html.twig',
                    ],
                ],
                'sylius_admin.channel.update.content.form.sections' => [
                    'fraudlabs_pro_api_key' => [
                        'template' => '@FraudLabsProSyliusFraudLabsProPlugin/Admin/Channel/_fraudLabsProApiKey.html.twig',
                    ],
                ],
            ],
        ]);
        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'DoctrineMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@FraudLabsProSyliusFraudLabsProPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
