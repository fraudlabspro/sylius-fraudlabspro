<?php

declare(strict_types=1);

namespace FraudLabsPro\SyliusFraudLabsProPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use FraudLabsPro\SyliusFraudLabsProPlugin\DependencyInjection\FraudLabsProSyliusFraudLabsProPluginExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class FraudLabsProSyliusFraudLabsProPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
	public function getContainerExtension(): ?ExtensionInterface
    {
        return new FraudLabsProSyliusFraudLabsProPluginExtension();
    }
}
