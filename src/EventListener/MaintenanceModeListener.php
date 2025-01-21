<?php

declare(strict_types=1);


namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

#[AsEventListener]
final class MaintenanceModeListener
{

    public const EXLUDED_PATHS = [
        '/admin',
        '/login',
        '/logout',
        '/2fa',
        '/_wdt',
        '/_profiler',
    ];

    public function __construct(private Environment $twig,
        #[Autowire(env: 'bool:MAINTENANCE_MODE')]
        private readonly bool $enabled = false)
    {

    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        //Exclude certain paths from maintenance mode
        $path = $event->getRequest()->getPathInfo();
        foreach (self::EXLUDED_PATHS as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return;
            }
        }

        $response = new Response($this->twig->render('maintenance.html.twig'), Response::HTTP_SERVICE_UNAVAILABLE);

        $event->setResponse($response);
    }
}