<?php

declare(strict_types=1);

namespace FraudLabsPro\SyliusFraudLabsProPlugin\Entity;

use Sylius\Component\Channel\Model\ChannelInterface;

class ChannelConfiguration
{
    private ?int $id = null;
    private ?ChannelInterface $channel = null;
	private ?bool $enabled = false;
    private ?string $apiKey = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }
    
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }
}