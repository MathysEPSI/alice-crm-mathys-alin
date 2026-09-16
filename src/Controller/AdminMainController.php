<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\DynamicContent;
use App\Entity\User;
use App\Form\CustomerType;
use App\Form\DynamicContentType;
use App\Form\EditCustomerType;
use App\Form\NewUserType;
use App\Form\EditUserType;
use App\Repository\ContactRepository;
use App\Repository\ContractRepository;
use App\Repository\CustomerRepository;
use App\Repository\DynamicContentRepository;
use App\Repository\TariffZoneRepository;
use App\Repository\UserRepository;
use App\Service\SlugService;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminMainController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private ContactRepository $contactRepository,
        private ContractRepository $contractRepository,
        private TariffZoneRepository $tariffZoneRepository,
        private EntityManagerInterface $entityManager,
        private UserManager $userManager,
        private SlugService $slugService
    ) {
    }

    /** Liste des utilisateurs. */
    #[Route('/utilisateur', name: 'app_user_list')]
    public function showUserList(): Response
    {
        return $this->render('admin_main/user_list.html.twig', [
            'users' => $this->userRepository->findAll(),
            'customer' => $this->customerRepository->findAll(),
        ]);
    }

    /** Fiche utilisateur. */
    #[Route('/utilisateur/{id}/{slug}', name: 'app_user_show')]
    public function showUser(User $user, string $slug): Response
    {
        if ($user->getSlug() !== $slug) {
            $this->addFlash('alert', 'Vous ne pouvez pas faire ça !');

            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('admin_main/user_show.html.twig', [
            'user' => $user,
            'contacts' => $user->getContacts(),
            'customer' => $user->getCustomer(),
            'flash' => $this,
        ]);
    }

    /** Création d'un utilisateur par un administrateur. */
    #[Route('/nouvel-utilisateur', name: 'app_user_add')]
    public function addUser(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(NewUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->userManager->emailExists($user->getEmail())) {
                $this->addFlash('alert', 'L\'email que vous avez renseigné existe déjà !!');

                return $this->redirectToRoute('app_user_list');
            }

            $this->userManager->createUser($user);

            $this->addFlash('success', 'Le nouvel utilisateur est enregistré.');

            return $this->redirectToRoute('app_user_show', [
                'id' => $user->getId(),
                'slug' => $user->getSlug(),
            ]);
        }

        return $this->render('admin_main/user_new.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
        ]);
    }

    /** Modification d'un utilisateur. */
    #[Route('/utilisateur/{id}/{slug}/modifier', name: 'app_user_edit')]
    public function editUser(Request $request, User $user, string $slug): Response
    {
        if ($user->getSlug() !== $slug) {
            $this->addFlash('alert', 'Vous ne pouvez pas faire ça !');

            return $this->redirectToRoute('app_user_list');
        }

        $form = $this->createForm(EditUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'La modification du contact et bien enregistrée.');

            return $this->redirectToRoute('app_user_show', [
                'id' => $user->getId(),
                'slug' => $user->getSlug(),
            ]);
        }

        return $this->render('admin_main/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /** Liste des clients. */
    #[Route('/client', name: 'app_customer_list')]
    public function showCustomers(): Response
    {
        return $this->render('admin_main/customer_list.html.twig', [
            'customers' => $this->customerRepository->findAll(),
        ]);
    }

    /** Fiche client, incluant le formulaire de contenu dynamique. */
    #[Route('/client/{id}/{slug}', name: 'app_customer')]
    public function showCustomer(Customer $customer, string $slug, Request $request): Response
    {
        if ($customer->getSlug() !== $slug) {
            $this->addFlash('alert', 'Vous ne pouvez pas faire ça !');

            // Redirection permanente (301) pour que les moteurs de recherche mettent leur index à jour
            return $this->redirectToRoute('app_customer_list', [], 301);
        }

        $user = $customer->getUser();

        $dynamicContent = new DynamicContent();
        $form = $this->createForm(DynamicContentType::class, $dynamicContent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($dynamicContent);
            $this->entityManager->flush();
        }

        return $this->render('admin_main/customer_show.html.twig', [
            'customer' => $customer,
            'user' => $user,
            'contacts' => $this->contactRepository->findBy(['user' => $user]),
            'contracts' => $this->contractRepository->findBy(['customer' => $customer]),
            'dynamicContent' => $dynamicContent,
            'form' => $form->createView(),
        ]);
    }

    /** Création d'un client rattaché à un utilisateur. */
    #[Route('/client/creer-un-client/{id}/{slug}', name: 'app_customer_add')]
    public function createCustomer(Request $request, User $user): Response
    {
        if (!$this->tariffZoneRepository->findAll()) {
            $this->addFlash('notice', 'Vous n\'avez pas encore définit de zone tarifaire. Merci de renseigner préalablement cet élément. Vous pourrez retourner sur le formulaire de création client par la suite.');

            return $this->redirectToRoute('app_tariff_zone_new');
        }

        $customer = new Customer();
        $customer->setUser($user);

        $form = $this->createForm(CustomerType::class, $customer, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeSiret($customer);
            $customer->setSlug($this->slugService->slugify((string) $customer->getName()));

            $this->customerRepository->save($customer, true);

            $this->addFlash('success', 'La création du client est bien enregistrée.');

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->render('admin_main/customer_new.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'customer' => $customer,
            'user' => $user,
        ]);
    }

    /** Modification d'un client. */
    #[Route('/client/{id}/{slug}/modifier-un-client', name: 'app_customer_edit')]
    public function editCustomer(Request $request, Customer $customer, string $slug): Response
    {
        if ($customer->getSlug() !== $slug) {
            $this->addFlash('alert', 'Vous ne pouvez pas faire ça !');

            return $this->redirectToRoute('app_customer', ['id' => $customer->getId(), 'slug' => $customer->getSlug()], 301);
        }

        $form = $this->createForm(EditCustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeSiret($customer);
            $this->entityManager->flush();

            $this->addFlash('success', 'La modification du client est bien enregistrée.');

            return $this->redirectToRoute('app_customer', ['id' => $customer->getId(), 'slug' => $customer->getSlug()]);
        }

        return $this->render('admin_main/customer_edit.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'customer' => $customer,
        ]);
    }

    /** Édition d'un bloc de contenu dynamique, créé à la volée s'il n'existe pas encore. */
    #[Route('/contenu-dynamique/modifier/{id}/{slug}/{name}/', name: 'dynamic_content_edit', requirements: ['name' => '[a-z0-9_-]{2,50}'])]
    public function dynamicContentEdit(string $name, Request $request, Customer $customer, DynamicContentRepository $dynamicContentRepository): Response
    {
        $dynamicContent = $dynamicContentRepository->findOneBy(['name' => $name]);

        if (!$dynamicContent) {
            $dynamicContent = new DynamicContent();
            $dynamicContent->setName($name);
            $dynamicContentRepository->save($dynamicContent);
        }

        $form = $this->createForm(DynamicContentType::class, $dynamicContent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contenu a bien été modifié !');

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->render('dynamic_content/edit.html.twig', ['form' => $form->createView()]);
    }

    /** Supprime les espaces du SIRET saisi. */
    private function normalizeSiret(Customer $customer): void
    {
        if ($customer->getSiret()) {
            $customer->setSiret(str_replace(' ', '', $customer->getSiret()));
        }
    }
}
