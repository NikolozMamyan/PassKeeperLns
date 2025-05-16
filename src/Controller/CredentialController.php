<?php
// src/Controller/CredentialController.php
namespace App\Controller;

use App\Entity\Credential;
use App\Form\CredentialType;
use App\Service\EncryptionService;
use App\Repository\CredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SharedAccessRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/credentials')]
class CredentialController extends AbstractController
{
    public function __construct(
        private EncryptionService $encryptionService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'credential_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(SharedAccessRepository $sharedAccessRepository ,CredentialRepository $credentialRepository): Response
    {
        $user = $this->getUser();
        $sharedAccesses = $sharedAccessRepository->findSharedWith($this->getUser());
        
        return $this->render('credential/index.html.twig', [
            'credentials' => $credentialRepository->findByUser($user),
            'sahredAccesses' =>  $sharedAccesses = $sharedAccessRepository->findSharedWith($this->getUser()),
            'heading' => 'Mes accès'
        ]);
    }

    #[Route('/new', name: 'credential_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $credential = new Credential();
        $form = $this->createForm(CredentialType::class, $credential);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'identifiant à l'utilisateur connecté
            $credential->setUser($this->getUser());
            
            // Normaliser le domaine
            $this->normalizeDomain($credential);
            
            // Chiffrer le mot de passe
            $plainPassword = $credential->getPassword();
            $encryptedPassword = $this->encryptionService->encrypt($plainPassword);
            $credential->setPassword($encryptedPassword);
            
            $this->entityManager->persist($credential);
            $this->entityManager->flush();

            $this->addFlash('success', 'Nouvel identifiant ajouté avec succès.');
            return $this->redirectToRoute('credential_index');
        }

        return $this->render('credential/new.html.twig', [
            'credential' => $credential,
            'form' => $form,
            'heading' => 'Mes accès'
        ]);
    }

    #[Route('/{id}', name: 'credential_show', methods: ['GET'])]
    #[IsGranted('VIEW', 'credential')]
    public function show(Credential $credential): Response
    {
        // Déchiffrer le mot de passe pour l'affichage
        $decryptedPassword = $this->encryptionService->decrypt($credential->getPassword());
        
        return $this->render('credential/show.html.twig', [
            'credential' => $credential,
            'decryptedPassword' => $decryptedPassword,
            'heading' => 'Mes accès'
        ]);
    }

    #[Route('/{id}/edit', name: 'credential_edit', methods: ['GET', 'POST'])]
    #[IsGranted('EDIT', 'credential')]
    public function edit(Request $request, Credential $credential): Response
    {
        // Déchiffrer temporairement le mot de passe pour l'édition
        $originalEncryptedPassword = $credential->getPassword();
        $decryptedPassword = $this->encryptionService->decrypt($originalEncryptedPassword);
        $credential->setPassword($decryptedPassword);
        
        $form = $this->createForm(CredentialType::class, $credential);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Normaliser le domaine
            $this->normalizeDomain($credential);
            
            // Gérer le mot de passe
            $plainPassword = $credential->getPassword();
            if ($plainPassword !== $decryptedPassword) {
                $encryptedPassword = $this->encryptionService->encrypt($plainPassword);
                $credential->setPassword($encryptedPassword);
            } else {
                $credential->setPassword($originalEncryptedPassword);
            }
            
            $credential->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->addFlash('success', 'Identifiant mis à jour avec succès.');
            return $this->redirectToRoute('credential_index');
        }

        return $this->render('credential/edit.html.twig', [
            'credential' => $credential,
            'form' => $form,
            'heading' => 'Mes accès'
        ]);
    }

    #[Route('/{id}', name: 'credential_delete', methods: ['POST'])]
    #[IsGranted('DELETE', 'credential')]
    public function delete(Request $request, Credential $credential): Response
    {
        if ($this->isCsrfTokenValid('delete'.$credential->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($credential);
            $this->entityManager->flush();
            $this->addFlash('success', 'Identifiant supprimé avec succès.');
        }

        return $this->redirectToRoute('credential_index');
    }

#[Route('/api/search', name: 'api_credential_search', methods: ['GET', 'POST', 'OPTIONS'])]
#[IsGranted('ROLE_USER')]
public function apiSearch(
    Request $request,
    CredentialRepository $credentialRepository
): JsonResponse {
    // ✅ Gérer les requêtes preflight CORS
    if ($request->getMethod() === 'OPTIONS') {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    $user = $this->getUser();
    if (!$user) {
        return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
    }

    try {
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        return $this->json(['error' => 'Corps JSON invalide'], Response::HTTP_BAD_REQUEST);
    }

    $domain = $payload['domain'] ?? null;
    if (!$domain) {
        return $this->json(['error' => 'Domaine non spécifié'], Response::HTTP_BAD_REQUEST);
    }

    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);

    $credentials = $credentialRepository->findByDomainAndUser($domain, $user);

    $result = array_map(fn (Credential $c) => [
        'id'       => $c->getId(),
        'domain'   => $c->getDomain(),
        'username' => $c->getUsername(),
        'password' => $this->encryptionService->decrypt($c->getPassword()),
        'name'     => $c->getName(),
    ], $credentials);

    return $this->json(['credentials' => $result]);
}


    
    private function normalizeDomain(Credential $credential): void
    {
        $domain = $credential->getDomain();
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $credential->setDomain($domain);
    }
}