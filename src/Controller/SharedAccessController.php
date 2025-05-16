<?php
// src/Controller/SharedAccessController.php
namespace App\Controller;

use App\Entity\SharedAccess;
use App\Entity\User;
use App\Repository\CredentialRepository;
use App\Repository\SharedAccessRepository;
use App\Repository\UserRepository;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/shared-access')]
class SharedAccessController extends AbstractController
{
    #[Route('/', name: 'shared_access_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(SharedAccessRepository $sharedAccessRepository): Response
    {
        $user = $this->getUser();
        
        // Récupérer les accès que l'utilisateur a partagés
        $sharedByMe = $sharedAccessRepository->findBy(['owner' => $user]);
        
        // Récupérer les accès partagés avec l'utilisateur
        $sharedWithMe = $sharedAccessRepository->findBy(['guest' => $user]);
        
        return $this->render('shared_access/index.html.twig', [
            'sharedByMe' => $sharedByMe,
            'sharedWithMe' => $sharedWithMe,
        ]);
    }
    
    #[Route('/new', name: 'shared_access_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository,
        CredentialRepository $credentialRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $credentialIds = $request->get('credentials', []);
            
            // Vérifier si l'utilisateur existe
            $guest = $userRepository->findOneBy(['email' => $email]);
            if (!$guest) {
                $this->addFlash('error', 'Aucun utilisateur trouvé avec cet email.');
                return $this->redirectToRoute('shared_access_new');
            }
            
            // Vérifier que l'utilisateur ne partage pas avec lui-même
            if ($guest === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas partager des identifiants avec vous-même.');
                return $this->redirectToRoute('shared_access_new');
            }
            
            $owner = $this->getUser();
            $createdCount = 0;
            
            foreach ($credentialIds as $credentialId) {
                $credential = $credentialRepository->find($credentialId);
                
                // Vérifier que l'identifiant appartient bien à l'utilisateur connecté
                if (!$credential || $credential->getUser() !== $owner) {
                    continue;
                }
                
                // Vérifier si ce partage existe déjà
                $existingAccess = $entityManager->getRepository(SharedAccess::class)->findOneBy([
                    'owner' => $owner,
                    'guest' => $guest,
                    'credential' => $credential
                ]);
                
                if (!$existingAccess) {
                    $sharedAccess = new SharedAccess();
                    $sharedAccess->setOwner($owner);
                    $sharedAccess->setGuest($guest);
                    $sharedAccess->setCredential($credential);
                    $sharedAccess->setCreatedAt(new \DateTimeImmutable());
                    
                    $entityManager->persist($sharedAccess);
                    $createdCount++;
                }
            }
            
            if ($createdCount > 0) {
                $entityManager->flush();
                $this->addFlash('success', $createdCount . ' identifiant(s) partagé(s) avec succès.');
            } else {
                $this->addFlash('info', 'Aucun nouvel identifiant n\'a été partagé.');
            }
            
            return $this->redirectToRoute('shared_access_index');
        }
        
        // Récupérer les identifiants de l'utilisateur pour les afficher dans le formulaire
        $credentials = $credentialRepository->findBy(['user' => $this->getUser()]);
        
        return $this->render('shared_access/new.html.twig', [
            'credentials' => $credentials,
        ]);
    }
    
    #[Route('/{id}/revoke', name: 'shared_access_revoke', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function revoke(Request $request, SharedAccess $sharedAccess, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'accès partagé appartient à l'utilisateur connecté
        if ($sharedAccess->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à révoquer cet accès partagé.');
        }
        
        if ($this->isCsrfTokenValid('revoke'.$sharedAccess->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sharedAccess);
            $entityManager->flush();
            $this->addFlash('success', 'Accès partagé révoqué avec succès.');
        }
        
        return $this->redirectToRoute('shared_access_index');
    }
    
    #[Route('/view/{id}', name: 'shared_access_view_credential', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function viewSharedCredential(
        SharedAccess $sharedAccess, 
        EncryptionService $encryptionService
    ): Response {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'invité à qui cet accès a été partagé
        if ($sharedAccess->getGuest() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cet identifiant.');
        }
        
        $credential = $sharedAccess->getCredential();
        $decryptedPassword = $encryptionService->decrypt($credential->getPassword());
        
        return $this->render('shared_access/view_credential.html.twig', [
            'credential' => $credential,
            'decryptedPassword' => $decryptedPassword,
            'owner' => $sharedAccess->getOwner(),
        ]);
    }
}