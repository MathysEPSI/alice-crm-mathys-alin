<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Customer;
use App\Entity\SerpInfo;
use App\Entity\SerpResult;
use App\Form\ContractType;
use App\Form\SerpInfoType;
use App\Form\SerpResultType;
use App\Form\EditContractType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;


#[Route('/admin/contrat')]
class ContractController extends AbstractController
{

    public function __construct(
        private ContractRepository $contractRepository
    ) {
    }

    #[Route('/', name: 'app_contract_list')]
    public function showContracts(): Response
    {
        $contracts = $this->contractRepository->findAll();

        return $this->render('admin_main/contract_list.html.twig', [
            'contracts' => $contracts
        ]);
    }

    /** Fiche contrat : mots-clés SERP et relevé des positions Google. */
    #[Route('/{id}', name: 'app_contract_show')]
    public function showContract(Contract $contract, Request $request, ManagerRegistry $doctrine): Response
    {
        $id = $contract->getId();
        $serpInfos = $contract->getSerpInfos();
        $googleApiKey = $this->getParameter('app.googlesearch.api_key');
        $googleCustomApiKey = $this->getParameter('app.googlecustomsearch.api_key');

        $serpInfoForm=$this->createForm(SerpInfoType::class);
        $serpInfoForm->handleRequest($request);

        if($serpInfoForm->isSubmitted() && $serpInfoForm->isValid()) {
            $newSerpInfo = new SerpInfo();
            $newKeyword = $serpInfoForm->get('keyword')->getData();
            $newSerpInfo->setKeyword($newKeyword);
            $newSerpInfo->setContract($contract);


            $em = $doctrine->getManager();
            $em->persist($newSerpInfo);
            $em->flush();

            $this->addFlash('success', 'Mot clé enregistré avec succès');

            return $this->redirectToRoute('app_contract_show', ['id' => $id]);
        }

        $serpResultForm = $this->createForm(SerpResultType::class);
        $serpResultForm->handleRequest($request);
    
        if ($serpResultForm->isSubmitted() && $serpResultForm->isValid()) {
            $newSerpResults = [];
    
            foreach ($serpInfos as $serpInfo) {
                $newSerpResult = new SerpResult();
                $keyword = $serpInfo->getKeyword();
                $url = 'https://www.googleapis.com/customsearch/v1?key=' . $googleApiKey . '&cx=' . $googleCustomApiKey . '&q=' . urlencode($keyword);
                $response = HttpClient::create()->request('GET', $url);
                $content = json_decode($response->getContent());
                $newRank = null;
    
                if (isset($content->items)) {
                    foreach ($content->items as $index => $item) {
                        if (stripos($item->link, $contract->getWebsiteLink()) !== false) {
                            $newRank = $index + 1;
                            break;
                        }
                    }
                }
    
                if ($newRank) {
                    $newSerpResult->setGoogleRank($newRank);
                    $newSerpResult->setSerpInfo($serpInfo);
                    $newSerpResult->setDate(new \DateTime());
    
                    $newSerpResults[] = $newSerpResult;
                }
            }
    
            if (!empty($newSerpResults)) {
                $em = $doctrine->getManager();
    
                foreach ($newSerpResults as $newSerpResult) {
                    $em->persist($newSerpResult);
                }
    
                $em->flush();
    
                $this->addFlash('success', 'Rangs enregistrés avec succès');
            } else {
                $this->addFlash('error', 'Le site n\'a pas été trouvé dans les résultats de recherche Google pour aucun des mots-clés.');
            }
    
            return $this->redirectToRoute('app_contract_show', ['id' => $id]);
        }
        
        return $this->render('admin_main/contract_show.html.twig', [
            'serpInfoForm' => $serpInfoForm->createView(),
            'contract' => $contract,
            'serpInfos' => $serpInfos,
            'googleApiKey' => $googleApiKey,
            'googleCustomApiKey' => $googleCustomApiKey,
            'serpResultForm' => $serpResultForm->createView()
        ]);
    }

    /** Création d'un contrat rattaché à un client. */
    #[Route('/{id}/{slug}/creer-un-contrat', name: 'app_contract_add')]
    public function createContract(Request $request, Customer $customer): Response
    {
        $contract = new Contract();
        $contract->setCustomer($customer);

        $form = $this->createForm(ContractType::class, $contract, ['customer' => $customer]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contractRepository->save($contract, true);

            $this->addFlash('success', 'La création du contrat est bien enregistrée.');

            return $this->redirectToRoute('app_customer', [
                'id' => $customer->getId(),
                'slug' => $customer->getSlug(),
            ]);
        }

        return $this->render('admin_main/contract_new.html.twig', [
            'customer' => $customer,
            'user' => $customer->getUser(),
            'flash' => $this,
            'form' => $form->createView(),
        ]);
    }

    /** Modification d'un contrat. */
    #[Route('/{id}/modifier-un-contrat', name: 'app_contract_edit')]
    public function editContract(Request $request, Contract $contract): Response
    {
        $customer = $contract->getCustomer();
        $user = $customer?->getUser();

        $form = $this->createForm(EditContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->contractRepository->save($contract, true);

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('admin_main/contract_edit.html.twig', [
            'contract' => $contract,
            'form' => $form->createView(),
            'user' => $user,
            'customer' => $customer,
            'id' => $user?->getId(),
        ]);
    }

    /** Suppression d'un contrat, protégée par jeton CSRF. */
    #[Route('/{id}/supprimer', name: 'app_contract_remove')]
    public function removeContract(Contract $contract, Request $request): Response
    {
        $customer = $contract->getCustomer();

        if (!$this->isCsrfTokenValid('delete_contract' . $contract->getId(), $request->query->get('csrf_token', ''))) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cet élément.');
        } else {
            $this->contractRepository->remove($contract, true);

            $this->addFlash('success', 'Le contrat à bien été supprimé.');
        }

        return $this->redirectToRoute('app_customer', [
            'id' => $customer?->getId(),
            'slug' => $customer?->getSlug(),
        ]);
    }

    /** Suppression d'un mot-clé SERP, protégée par jeton CSRF. */
    #[Route('/{id}/supprimer-serp-info/{serpInfoId}', name: 'app_serp_info_remove')]
    #[ParamConverter('serpInfo', options: ['id' => 'serpInfoId'])]
    public function removeSerpInfo(SerpInfo $serpInfo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $contractId = $serpInfo->getContract()?->getId();

        if (!$this->isCsrfTokenValid('delete_serp_info' . $serpInfo->getId(), $request->query->get('csrf_token', ''))) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cet élément.');
        } else {
            $entityManager->remove($serpInfo);
            $entityManager->flush();

            $this->addFlash('success', 'Le mot clé à bien été supprimé.');
        }

        return $this->redirectToRoute('app_contract_show', [
            'id' => $contractId,
        ]);

    }

}
