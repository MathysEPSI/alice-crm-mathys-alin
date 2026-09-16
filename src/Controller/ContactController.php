<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use App\Service\ContactManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private ContactRepository $contactRepository,
        private ContactManager $contactManager
    ) {
    }

    /** Liste de tous les contacts. */
    #[Route('/', name: 'app_contacts')]
    public function showContacts(): Response
    {
        return $this->render('admin/contact_list.html.twig', [
            'contacts' => $this->contactRepository->findAll(),
        ]);
    }

    /** Fiche d'un contact. */
    #[Route('/{id}/{slug}', name: 'app_contact')]
    public function showContact(Contact $contact): Response
    {
        $user = $contact->getUser();

        return $this->render('admin/contact_show.html.twig', [
            'contact' => $contact,
            'user' => $user,
            'customer' => $user?->getCustomer(),
        ]);
    }

    /** Création d'un contact rattaché à un utilisateur. */
    #[Route('/creer-un-contact/{id}/{slug}', name: 'app_contact_add')]
    public function createContact(Request $request, User $user): Response
    {
        $contact = new Contact();
        $contact->setUser($user);

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contactManager->save($contact);

            $this->addFlash('success', 'La création du contact est bien enregistrée.');

            return $this->redirectToUserOrCustomer($user);
        }

        return $this->render('admin/contact_new.html.twig', [
            'user' => $user,
            'flash' => $this,
            'form' => $form->createView(),
        ]);
    }

    /** Modification d'un contact. */
    #[Route('/modifier-un-contact/{id}/{slug}', name: 'app_contact_edit')]
    public function editContact(Request $request, Contact $contact): Response
    {
        $user = $contact->getUser();

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contactManager->save($contact);

            $this->addFlash('success', 'La modification du contact est bien enregistrée.');

            return $this->redirectToRoute('app_contact', [
                'id' => $contact->getId(),
                'slug' => $contact->getSlug(),
            ]);
        }

        return $this->render('admin/contact_edit.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'user' => $user,
            'customer' => $user?->getCustomer(),
        ]);
    }

    /** Suppression d'un contact, protégée par jeton CSRF. */
    #[Route('/{id}/{slug}/supprimer', name: 'app_contact_delete')]
    public function deleteContact(Request $request, Contact $contact): Response
    {
        $user = $contact->getUser();

        if (!$this->isCsrfTokenValid('delete_contact' . $contact->getId(), $request->query->get('csrf_token', ''))) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cet élément.');

            return $this->redirectToUserOrCustomer($user);
        }

        $this->contactRepository->remove($contact, true);
        $this->addFlash('success', 'Le contact à été supprimé.');

        return $this->redirectToUserOrCustomer($user);
    }

    /** Renvoie vers la fiche client si l'utilisateur en possède une, sinon vers sa fiche utilisateur. */
    private function redirectToUserOrCustomer(?User $user): Response
    {
        $customer = $user?->getCustomer();

        if ($customer) {
            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->redirectToRoute('app_user_show', [
            'id' => $user?->getId(),
            'slug' => $user?->getSlug(),
        ]);
    }
}
