<?php

namespace App\EventSubscriber;

use App\Translation\Translations;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Translations $translations)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if ($event->getFirewallName() !== 'main') {
            return;
        }

        $event->getRequest()->getSession()->getFlashBag()->add('success', $this->translations->get('flash.login_success'));
    }
}
