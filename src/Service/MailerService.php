<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;

class MailerService implements MailerServiceInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $senderEmail,
        private string $senderName
    ) {
    }

    public function sendEmailConfirmation(User $user, VerifyEmailSignatureComponents $signatureComponents): void
    {
        $this->send(
            $user->getEmail(),
            'Confirmation de votre Email | Alice CRM',
            'register/email.html.twig',
            [
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'signatureComponents' => $signatureComponents,
            ]
        );
    }

    public function sendPasswordReset(User $user, ResetPasswordToken $resetToken): void
    {
        $this->send(
            $user->getEmail(),
            'Réinitialisation de votre mot de passe | Alice CRM',
            'reset_password/email.html.twig',
            ['resetToken' => $resetToken]
        );
    }

    /** Construit et envoie un mail HTML basé sur un template Twig. */
    private function send(string $to, string $subject, string $template, array $context): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);
    }
}
