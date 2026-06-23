<?php

declare(strict_types=1);

namespace FraudLabsPro\SyliusFraudLabsProPlugin\Form\Extension;

use FraudLabsPro\SyliusFraudLabsProPlugin\Entity\ChannelConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelType;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ChannelTypeExtension extends AbstractTypeExtension
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('fraudLabsProEnabled', CheckboxType::class, [
            'mapped' => false, // 🔑 Vital: Tells Symfony not to look inside Channel.php
			'label' => 'Enable FraudLabs Pro Validator',
			'required' => false,
		])->add('fraudLabsProApiKey', TextType::class, [
            'mapped' => false, // 🔑 Vital: Tells Symfony not to look inside Channel.php
            'required' => false,
            'label' => 'FraudLabs Pro API Key',
            'help' => 'Find your API key in the <a href="https://www.fraudlabspro.com/merchant/dashboard" target="_blank">Merchant Dashboard</a>.',
            'help_html' => true,
        ]);

        // 1. When the form loads, fetch the API key from our table and populate the input
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $channel = $event->getData();
            if (!$channel instanceof ChannelInterface || null === $channel->getId()) {
                return;
            }

            $repository = $this->entityManager->getRepository(ChannelConfiguration::class);
            $config = $repository->findOneBy(['channel' => $channel]);

            if ($config) {
                $event->getForm()->get('fraudLabsProEnabled')->setData($config->isEnabled());
                $event->getForm()->get('fraudLabsProApiKey')->setData($config->getApiKey());
            }
        });

        // 2. When the admin clicks "Save", capture the text input and persist it to our table
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $channel = $event->getData();
            if (!$channel instanceof ChannelInterface) {
                return;
            }

            $enabled = $event->getForm()->get('fraudLabsProEnabled')->getData();
            $apiKey = $event->getForm()->get('fraudLabsProApiKey')->getData();
            $repository = $this->entityManager->getRepository(ChannelConfiguration::class);
            
            $config = $repository->findOneBy(['channel' => $channel]) ?? new ChannelConfiguration();
            $config->setChannel($channel);
            $config->setEnabled($enabled);
            $config->setApiKey($apiKey);

            $this->entityManager->persist($config);
            // No need to manually flush here; Sylius flushes the main entity manager right after this event.
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChannelType::class];
    }
}