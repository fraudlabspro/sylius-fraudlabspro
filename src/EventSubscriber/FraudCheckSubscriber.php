<?php
namespace FraudLabsPro\SyliusFraudLabsProPlugin\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use FraudLabsPro\SyliusFraudLabsProPlugin\Entity\ChannelConfiguration;
use Psr\Log\LoggerInterface;

class FraudCheckSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;
    private RequestStack $requestStack;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
	private ChannelContextInterface $channelContext;
	private EntityManagerInterface $entityManager;
    // private string $apiKey;

    public function __construct(
        RouterInterface $router,
        RequestStack $requestStack,
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        ChannelContextInterface $channelContext,
        EntityManagerInterface $entityManager,
        // string $apiKey = 'YOUR_FRAUDLABS_PRO_API_KEY' // Replace or manage via parameters
    ) {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->channelContext = $channelContext;
        $this->entityManager = $entityManager;
        // $this->apiKey = $apiKey;
    }
	
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.order.pre_complete' => 'checkFraudRisk',
        ];
    }

    public function checkFraudRisk(ResourceControllerEvent $event): void
    {
		// $channel = $this->channelContext->getChannel();
		/** @var \App\Entity\Channel\Channel $channel */ // Optional: Helps your IDE know about your custom method
        // $apiKey = $channel->getFraudLabsProApiKey();
		// if (empty($apiKey)) {
            // Handle the case where the admin hasn't set an API key yet
            // return;
        // }
		
		// 1. Get the current active Sylius channel
        $channel = $this->channelContext->getChannel();

        // 2. Ask Doctrine to find the matching ChannelConfiguration for this channel
        $configurationRepo = $this->entityManager->getRepository(ChannelConfiguration::class);
        
        /** @var ChannelConfiguration|null $config */
        $config = $configurationRepo->findOneBy(['channel' => $channel]);
		
		// Safely check if config exists, AND if the validator is actually enabled
        if ($config === null || !$config->isEnabled()) {
            // The admin toggled it off, or it's not configured yet. Stop here.
            return; 
        }

        // 3. Safely check if the config exists and has a key
        if ($config === null || empty($config->getApiKey())) {
            // Log this or return early—the admin hasn't configured FraudLabs Pro for this channel yet.
            return;
        }

        // 4. Success! You have the key.
        $apiKey = $config->getApiKey();
		
        /** @var OrderInterface $order */
        $order = $event->getSubject();
		// file_put_contents('debug-flp.log', var_export($order, true) . PHP_EOL, FILE_APPEND);
		// dd($order);
		
		// 🔑 Dynamic extraction from the Channel database entry
		$channel = $order->getChannel();
		
		// Search our plugin table for the key tied to this channel
		$configRepository = $this->entityManager->getRepository(ChannelConfiguration::class);
		$config = $configRepository->findOneBy(['channel' => $channel]);

		if (!$config || !$config->getApiKey()) {
			$this->logger->warning('FraudLabs Pro check skipped: No API Key configured for this channel.');
			return; // Pass through safely
		}

		// $apiKey = $channel->getFraudLabsProApiKey();
        
        // Grab the current client IP address safely
        $request = $this->requestStack->getCurrentRequest();
		
		file_put_contents('debug-flp.log', var_export($request->getClientIp(), true) . PHP_EOL, FILE_APPEND);
        $clientIp = $request ? $request->getClientIp() : '127.0.0.1';

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress(); // May be null on digital carts
        $customer = $order->getCustomer();

        // 🛠️ Assembling the FraudLabs Pro payload array using PHP null-safe operators (?->)
        $payload = [
            'key' => $apiKey,
            'format' => 'json',
            'ip' => $clientIp,
            'user_order_id' => $order->getId(),
            'amount' => $order->getTotal() / 100, // Converts cents to floating decimal
            'currency' => $order->getCurrencyCode(),
            'quantity' => $order->getTotalQuantity(),
            
            // Customer Info
            'email' => $customer ? $customer->getEmail() : null,
            'user_phone' => $billingAddress?->getPhoneNumber() ?? $customer?->getPhoneNumber(),
            
            // Billing Data
            'first_name' => $billingAddress?->getFirstName(),
            'last_name' => $billingAddress?->getLastName(),
            'bill_addr' => $billingAddress?->getStreet(),
            'bill_city' => $billingAddress?->getCity(),
            'bill_state' => $billingAddress?->getProvinceCode() ?? $billingAddress?->getProvinceName(),
            'bill_country' => $billingAddress?->getCountryCode(),
            'bill_zip_code' => $billingAddress?->getPostcode(),
            
            // Shipping Data
            'ship_first_name' => $shippingAddress?->getFirstName(),
            'ship_last_name' => $shippingAddress?->getLastName(),
            'ship_addr' => $shippingAddress?->getStreet(),
            'ship_city' => $shippingAddress?->getCity(),
            'ship_state' => $shippingAddress?->getProvinceCode() ?? $shippingAddress?->getProvinceName(),
            'ship_country' => $shippingAddress?->getCountryCode(),
            'ship_zip_code' => $shippingAddress?->getPostcode(),
            
            // Payment Gateway Code
            'payment_mode' => $order->getLastPayment()?->getMethod()?->getCode(),
        ];
		file_put_contents('debug-flp.log', var_export($payload, true) . PHP_EOL, FILE_APPEND);
		
		try {
            // Send POST request with query parameters as defined in the YAML
            $response = $this->httpClient->request('POST', 'https://api.fraudlabspro.com/v2/order/screen', [
                'json' => $payload,
                'timeout' => 4.0, // Don't make the user wait longer than 4 seconds
            ]);

            // Fail-safe: Check if HTTP status code is not 200
            if ($response->getStatusCode() !== 200) {
                $this->logger->error('FraudLabs Pro API returned non-200 status code: ' . $response->getStatusCode());
                return; // Let the order pass through
            }

            $responseData = $response->toArray(); // Automatically parses JSON into an array
			file_put_contents('debug-flp.log', var_export($responseData, true) . PHP_EOL, FILE_APPEND);

            // Fail-safe: Check if the API returned an error object instead of validation results
            if (isset($responseData['error'])) {
                $this->logger->error('FraudLabs Pro API error response: ' . json_encode($responseData['error']));
                return; // Let the order pass through
            }

            // Real business logic validation
            if (isset($responseData['fraudlabspro_status']) && $responseData['fraudlabspro_status'] === 'REJECT') {
                $event->stop(
                    'An error occurred while processing your order. Please review your details and try again.',
                    ResourceControllerEvent::TYPE_ERROR
                );

                $redirectUrl = $this->router->generate('sylius_shop_checkout_complete');
                $event->setResponse(new RedirectResponse($redirectUrl));
            }

        } catch (\Throwable $exception) {
            // Fail-safe: Catch timeouts, network losses, or bad JSON parsing downfalls
            $this->logger->critical('FraudLabs Pro Plugin exception caught: ' . $exception->getMessage());
            
            // Do nothing else, allowing the execution to exit this method smoothly 
            // and letting Sylius proceed with the checkout fulfillment.
            return;
        }

        // 1. Call your FraudLabs Pro API service here using $order data
        // $fraudResult = $this->fraudLabsService->check($order);

        // 2. Simulate a fraud rejection scenario
        // $isFraudulent = false; // Replace with actual API response logic
        /*$isFraudulent = true; // Replace with actual API response logic

        if ($isFraudulent) {
			// 1. Stop the resource processing and attach the flash error message
            $event->stop(
                'Your transaction was flagged by our fraud prevention system. Please contact support.',
                ResourceControllerEvent::TYPE_ERROR
            );

            // 2. Generate a URL back to the final checkout review step
            $redirectUrl = $this->router->generate('sylius_shop_checkout_complete');

            // 3. Force Sylius to use this redirect instead of trying to go to the payment page
            $event->setResponse(new RedirectResponse($redirectUrl));
        }*/
    }
}