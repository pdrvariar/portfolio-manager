<?php
namespace App\Controllers;

use App\Core\EntityManagerFactory;
use App\Core\Auth;
use App\Core\Session;
use App\Entities\User;
use App\Entities\Asset;
use App\Entities\Portfolio;
use App\Entities\PortfolioAsset;
use SimulationResult;

class AdminController {
    
    public function __construct() {
        Session::start();
    }
    
    public function dashboard() {
        Auth::checkAdmin();
        
        // Estatísticas
        $stats = $this->getStats();
        
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }
    
    public function users() {
        Auth::checkAdmin();
        
        $entityManager = EntityManagerFactory::createEntityManager();
        $userRepository = $entityManager->getRepository(User::class);
        $entities = $userRepository->findAll();

        $users = array_map(function($u) {
            return [
                'id' => $u->getId(),
                'username' => $u->getUsername(),
                'full_name' => $u->getFullName(),
                'email' => $u->getEmail(),
                'status' => $u->getStatus(),
                'is_admin' => $u->isAdmin(),
                'created_at' => $u->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $entities);
        
        require_once __DIR__ . '/../views/admin/users.php';
    }
    
    public function assets() {
        Auth::checkAdmin();
        
        $entityManager = EntityManagerFactory::createEntityManager();
        $assetRepository = $entityManager->getRepository(Asset::class);
        $entities = $assetRepository->findAll();

        $assets = array_map(function($a) {
            return [
                'id' => $a->getId(),
                'code' => $a->getCode(),
                'name' => $a->getName(),
                'currency' => $a->getCurrency(),
                'asset_type' => $a->getAssetType(),
                'is_active' => $a->isActive(),
                'data_count' => 0, // Simplificado para admin view se necessário
                'min_date' => null,
                'max_date' => null
            ];
        }, $entities);
        
        require_once __DIR__ . '/../views/admin/assets.php';
    }
    
    public function createDefaultPortfolios() {
        Auth::checkAdmin();
        
        $entityManager = EntityManagerFactory::createEntityManager();
        // Portfólio Permanente
        $portfolio = new Portfolio();
        $portfolio->setUser($entityManager->find(User::class, Auth::getCurrentUserId()))
                  ->setName('Portfólio Permanente')
                  ->setDescription('Portfólio diversificado com ações, títulos, ouro e caixa')
                  ->setInitialCapital('100000.00')
                  ->setStartDate(new \DateTime('2023-01-01'))
                  ->setRebalanceFrequency('monthly')
                  ->setOutputCurrency('USD')
                  ->setIsSystemDefault(true);
        
        $entityManager->persist($portfolio);
        
        // Portfólio Conservador
        $portfolio2 = new Portfolio();
        $portfolio2->setUser($entityManager->find(User::class, Auth::getCurrentUserId()))
                   ->setName('Portfólio Conservador')
                   ->setDescription('Foco em renda fixa e ativos defensivos')
                   ->setInitialCapital('100000.00')
                   ->setStartDate(new \DateTime('2020-01-01'))
                   ->setRebalanceFrequency('quarterly')
                   ->setOutputCurrency('BRL')
                   ->setIsSystemDefault(true);
        
        $entityManager->persist($portfolio2);
        $entityManager->flush();
        
        Session::setFlash('success', 'Portfólios padrão criados com sucesso!');
        header('Location: /index.php?url=' . obfuscateUrl('admin/dashboard'));
        exit;
    }
    
    private function getStats() {
        $entityManager = EntityManagerFactory::createEntityManager();
        
        return [
            'users' => $entityManager->getRepository(User::class)->countAll(),
            'portfolios' => $entityManager->getRepository(Portfolio::class)->countAll(),
            'simulations' => (new SimulationResult())->countAll(),
            'assets' => $entityManager->getRepository(Asset::class)->countAll()
        ];
    }
}
?>