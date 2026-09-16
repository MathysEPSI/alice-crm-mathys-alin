<?php

namespace App\Tests\Service;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use App\Service\ContactManager;
use App\Service\SlugService;
use PHPUnit\Framework\TestCase;

class ContactManagerTest extends TestCase
{
    public function testSaveGeneratesSlugAndPersists(): void
    {
        $contact = (new Contact())
            ->setFirstname('Élodie')
            ->setLastname('Martin');

        $contactRepository = $this->createMock(ContactRepository::class);
        $contactRepository->expects($this->once())->method('save')->with($contact, true);

        (new ContactManager($contactRepository, new SlugService()))->save($contact);

        $this->assertSame('elodie-martin', $contact->getSlug());
    }
}
