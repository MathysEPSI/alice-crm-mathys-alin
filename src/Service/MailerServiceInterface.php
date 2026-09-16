<?php

namespace App\Service;

use App\Entity\User;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;

interface MailerServiceInterface
{
    /** Envoie le mail de confirmation d'inscription contenant l'URL signée. */
    public function sendEmailConfirmation(User $user, VerifyEmailSignatureComponents $signatureComponents): void;

    /** Envoie le mail de réinitialisation de mot de passe. */
    public function sendPasswordReset(User $user, ResetPasswordToken $resetToken): void;
}
