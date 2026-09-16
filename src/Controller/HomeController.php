<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\ContactType;
use App\Repository\DocumentRepository;
use App\Service\ContactManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/compte')]
class HomeController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private ContactManager $contactManager
    ) {
    }

    /** Tableau de bord : documents paginés et contacts de l'utilisateur connecté. */
    #[Route('', name: 'app_home')]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getIsVerified()) {
            $this->addFlash('alert', 'Votre compte n\'a pas été vérifié. Veuillez vérifier votre boite mail, ainsi que les spams.');

            return $this->redirectToRoute('app_logout');
        }

        $query = $this->documentRepository->createQueryBuilder('d')->orderBy('d.date', 'DESC');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $query
                ->join('d.user', 'u')
                ->where('u.id = :userId')
                ->setParameter('userId', $user->getId());
        }

        return $this->render('home/index.html.twig', [
            'pagination' => $paginator->paginate($query, $request->query->getInt('page', 1), 10),
            'contacts' => $user->getContacts(),
            'flash' => $this,
            'user' => $user,
        ]);
    }

    /** Liste des contacts de l'utilisateur connecté. */
    #[Route('/contacts', name: 'app_contacts_user')]
    public function showUsercontacts(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('home/contact_user_list.html.twig', [
            'contacts' => $user->getContacts(),
            'user' => $user,
        ]);
    }

    /** Création d'un contact pour l'utilisateur connecté. */
    #[Route('/creer-un-contact', name: 'app_contact_user_add')]
    public function addUserContact(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $contact = new Contact();
        $contact->setUser($user);

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contactManager->save($contact);

            $this->addFlash('success', 'La Création du contact et bien enregistrée.');

            return $this->redirectToRoute('app_contacts_user');
        }

        return $this->render('home/contact_user_add.html.twig', [
            'user' => $user,
            'flash' => $this,
            'form' => $form->createView(),
        ]);
    }

    /** Modification d'un contact appartenant à l'utilisateur connecté. */
    #[Route('/contacts/{id}/{slug}/modifier-un-contact', name: 'app_contacts_user_edit')]
    public function editUserContact(Request $request, Contact $contact): Response
    {
        // Sans ce contrôle, n'importe quel utilisateur connecté peut modifier le contact d'un autre via son id
        if ($contact->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contactManager->save($contact);

            $this->addFlash('success', 'La modification du contact est bien enregistrée.');

            return $this->redirectToRoute('app_contacts_user');
        }

        return $this->render('home/contact_user_edit.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
        ]);
    }
}
