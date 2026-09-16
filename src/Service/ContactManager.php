<?php

namespace App\Service;

use App\Entity\Contact;
use App\Repository\ContactRepository;

class ContactManager
{
    public function __construct(
        private ContactRepository $contactRepository,
        private SlugService $slugService
    ) {
    }

    /** Régénère le slug du contact puis le persiste. */
    public function save(Contact $contact): Contact
    {
        $contact->setSlug($this->slugService->slugifyFullname($contact->getFirstname(), $contact->getLastname()));

        $this->contactRepository->save($contact, true);

        return $contact;
    }
}
