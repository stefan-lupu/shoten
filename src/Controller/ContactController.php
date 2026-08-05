<?php

namespace App\Controller;

use App\Dto\ContactMessage;
use App\Form\ContactType;
use App\Service\StoreConfig;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, MailerInterface $mailer, StoreConfig $store, RateLimiterFactoryInterface $contactFormIpLimiter): Response
    {
        $contactMessage = new ContactMessage();
        $form = $this->createForm(ContactType::class, $contactMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $contactFormIpLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Ai trimis prea multe mesaje. Te rugăm să încerci din nou mai târziu.');

                return $this->redirectToRoute('app_contact');
            }

            $email = (new TemplatedEmail())
                ->from(new Address($store->email, $store->name))
                ->to($store->email)
                ->replyTo(new Address($contactMessage->email, $contactMessage->name))
                ->subject(sprintf('[Contact] %s', $contactMessage->subject))
                ->htmlTemplate('emails/contact_message.html.twig')
                ->context(['contact' => $contactMessage])
            ;
            $mailer->send($email);

            $this->addFlash('success', 'Mesajul tău a fost trimis. Îți vom răspunde cât mai curând.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('page/contact.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
