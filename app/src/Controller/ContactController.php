<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Entity\User;
use App\Form\ContactType;
use App\Mail\ContactNotifier;
use App\Translation\Translations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Publiczny formularz kontaktowy: walidacja, ochrona przed spamem, zapis do bazy i powiadomienie e-mail.
 */
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly Translations $translations,
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactNotifier $notifier,
        #[Autowire(service: 'limiter.contact_form')]
        private readonly RateLimiterFactory $contactFormLimiter,
    ) {
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $message = new ContactMessage();
        // Zalogowanemu podpowiadamy jego adres; można go zmienić w formularzu.
        $user = $this->getUser();
        if ($user instanceof User) {
            $message->setEmail($user->getEmail());
        }
        $form = $this->createForm(ContactType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Honeypot: bot dostaje taką samą odpowiedź jak człowiek, ale nic nie zapisujemy.
            if ((string) $form->get(ContactType::HONEYPOT)->getData() !== '') {
                return $this->redirectWithFlash('success', 'contact.sent');
            }

            if (!$this->contactFormLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
                $this->addFlash('error', $this->translations->get('contact.rate_limited'));

                return $this->render('contact/index.html.twig', ['form' => $form->createView()], new Response(status: Response::HTTP_TOO_MANY_REQUESTS));
            }

            $message->setLocale($this->translations->locale());
            $this->entityManager->persist($message);
            $this->entityManager->flush();

            // Błąd wysyłki nie ma znaczenia dla odwiedzającego: wiadomość jest już zapisana i widoczna w panelu.
            $this->notifier->notify($message);

            return $this->redirectWithFlash('success', 'contact.sent');
        }

        return $this->render('contact/index.html.twig', ['form' => $form->createView()], new Response(
            status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }

    private function redirectWithFlash(string $type, string $key): Response
    {
        $this->addFlash($type, $this->translations->get($key));

        return $this->redirectToRoute('app_contact', status: Response::HTTP_SEE_OTHER);
    }
}
