<?php
namespace App\Controllers;

use App\Core\EntityManagerFactory;
use App\Core\Auth;
use App\Core\Session;
use App\Entities\Portfolio;
use App\Entities\User;

class PortfolioController {
    private $entityManager;
    private $portfolioRepository;
    private $params;

    public function __construct($params = []) {
        $this->entityManager = EntityManagerFactory::createEntityManager();
        $this->portfolioRepository = $this->entityManager->getRepository(Portfolio::class);
        $this->params = $params;
    }

    public function index() {
        Auth::checkAuthentication();
        
        $user = $this->entityManager->find(User::class, Auth::getCurrentUserId());
        $entities = $this->portfolioRepository->findByUser($user);
        
        // Conversão para array para manter compatibilidade com a view legado
        $portfolios = array_map(function($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'initial_capital' => $p->getInitialCapital(),
                'start_date' => $p->getStartDate()->format('Y-m-d'),
                'end_date' => $p->getEndDate() ? $p->getEndDate()->format('Y-m-d') : null,
                'rebalance_frequency' => $p->getRebalanceFrequency(),
                'output_currency' => $p->getOutputCurrency(),
                'is_system_default' => $p->isSystemDefault(),
                'created_at' => $p->getCreatedAt() ? $p->getCreatedAt()->format('Y-m-d H:i:s') : null
            ];
        }, $entities);
        
        require_once __DIR__ . '/../views/portfolio/index.php';
    }
        
    public function create() {
        Auth::checkAuthentication();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('portfolio/create'));
            }           
            
            $user = $this->entityManager->find(User::class, Auth::getCurrentUserId());
            
            $portfolio = new Portfolio();
            $portfolio->setUser($user)
                      ->setName($_POST['name'])
                      ->setDescription($_POST['description'])
                      ->setInitialCapital($_POST['initial_capital'])
                      ->setStartDate(new \DateTime($_POST['start_date']))
                      ->setEndDate($_POST['end_date'] ? new \DateTime($_POST['end_date']) : null)
                      ->setRebalanceFrequency($_POST['rebalance_frequency'])
                      ->setOutputCurrency($_POST['output_currency']);
            
            $this->entityManager->persist($portfolio);
            $this->entityManager->flush();
            
            $portfolioId = $portfolio->getId();
            
            if ($portfolioId) {
                if (isset($_POST['assets'])) {
                    foreach ($_POST['assets'] as $assetData) {
                        $asset = $this->entityManager->find(\App\Entities\Asset::class, $assetData['asset_id']);
                        if ($asset) {
                            $pa = new \App\Entities\PortfolioAsset();
                            $pa->setPortfolio($portfolio)
                               ->setAsset($asset)
                               ->setAllocationPercentage($assetData['allocation']);
                            $this->entityManager->persist($pa);
                        }
                    }
                    $this->entityManager->flush();
                }
                header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolioId));
                exit;
            }
        }
        require_once __DIR__ . '/../views/portfolio/create.php';
    }
    
    public function clone() {
        Auth::checkAuthentication();
        
        $portfolioId = $this->params['id'] ?? null;
        
        if (!$portfolioId) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }
        
        $original = $this->portfolioRepository->find($portfolioId);
        
        if (!$original) {
            Session::setFlash('error', 'Portfólio original não encontrado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        $user = $this->entityManager->find(User::class, Auth::getCurrentUserId());
        
        $newPortfolio = new Portfolio();
        $newPortfolio->setUser($user)
                     ->setName($original->getName() . ' (Cópia)')
                     ->setDescription($original->getDescription())
                     ->setInitialCapital($original->getInitialCapital())
                     ->setStartDate($original->getStartDate())
                     ->setEndDate($original->getEndDate())
                     ->setRebalanceFrequency($original->getRebalanceFrequency())
                     ->setOutputCurrency($original->getOutputCurrency());
        
        $this->entityManager->persist($newPortfolio);
        
        foreach ($original->getAssets() as $originalAsset) {
            $newAsset = new \App\Entities\PortfolioAsset();
            $newAsset->setPortfolio($newPortfolio)
                     ->setAsset($originalAsset->getAsset())
                     ->setAllocationPercentage($originalAsset->getAllocationPercentage());
            $this->entityManager->persist($newAsset);
        }

        $this->entityManager->flush();
        
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $newPortfolio->getId()));
        exit;
    }
    
    public function runSimulation() {
        Auth::checkAuthentication();
        $portfolioId = $this->params['id'] ?? null;
        
        $backtestService = new BacktestService();
        $result = $backtestService->runSimulation($portfolioId);
        
        if ($result['success']) {
            $dateStr = date('m/Y', strtotime($result['effective_end']));
            Session::setFlash('success', "Simulação concluída! Dados processados até $dateStr (limite dos ativos selecionados).");
        } else {
            Session::setFlash('error', $result['message']);
        }
        
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $portfolioId));
        exit;
    }

    public function view() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        
        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }
        
        $portfolioEntity = $this->portfolioRepository->findWithAssets($id);
        if (!$portfolioEntity) {
            Session::setFlash('error', 'Portfólio não encontrado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Conversão para array para compatibilidade com a view
        $portfolio = [
            'id' => $portfolioEntity->getId(),
            'name' => $portfolioEntity->getName(),
            'initial_capital' => $portfolioEntity->getInitialCapital(),
            'start_date' => $portfolioEntity->getStartDate()->format('Y-m-d'),
            'end_date' => $portfolioEntity->getEndDate() ? $portfolioEntity->getEndDate()->format('Y-m-d') : null,
            'output_currency' => $portfolioEntity->getOutputCurrency(),
            'user_id' => $portfolioEntity->getUser()->getId(),
            'is_system_default' => $portfolioEntity->isSystemDefault()
        ];

        $assets = array_map(function($pa) {
            return [
                'id' => $pa->getId(),
                'asset_id' => $pa->getAsset()->getId(),
                'name' => $pa->getAsset()->getName(),
                'currency' => $pa->getAsset()->getCurrency(),
                'allocation_percentage' => $pa->getAllocationPercentage(),
                'performance_factor' => '1.0' // Placeholder para compatibilidade
            ];
        }, $portfolioEntity->getAssets()->toArray());

        $simulationModel = new SimulationResult();
        $latest = $simulationModel->getLatest($id);

        // Define métricas padrão
        $metrics = $latest ?: [
            'total_return' => 0,
            'annual_return' => 0,
            'volatility' => 0,
            'sharpe_ratio' => 0
        ];

        // CORREÇÃO: Calcula o Retorno Total real comparando com o capital inicial
        if ($latest) {
            $initial = $portfolio['initial_capital'];
            $final = $latest['total_value'];
            $metrics['total_return'] = (($final / $initial) - 1) * 100;
        }

        $chartData = [
            'value_chart' => ['labels' => [], 'datasets' => []],
            'composition_chart' => ['labels' => [], 'datasets' => []],
            'returns_chart' => ['labels' => [], 'datasets' => []]
        ];

        if ($latest && isset($latest['chart_data'])) {
            $chartData = json_decode($latest['chart_data'], true);
        }

        $start = $portfolioEntity->getStartDate();
        $end   = $portfolioEntity->getEndDate() ?: new \DateTime();
        $interval = $start->diff($end);
        $months = ($interval->y * 12) + $interval->m;

        $metrics['is_short_period'] = ($months < 12);        
        
        require_once __DIR__ . '/../views/portfolio/view.php';
    }
    
    /**
     * GET: Exibe o formulário de edição com trava de segurança
     */
    public function edit() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        
        $portfolioEntity = $this->portfolioRepository->findWithAssets($id);

        if (!$portfolioEntity) {
            Session::setFlash('error', 'Portfólio não encontrado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // SEGURANÇA SÊNIOR: Bloqueia edição de portfólio de sistema por não-admins
        if ($portfolioEntity->isSystemDefault() && !Auth::isAdmin()) {
            Session::setFlash('error', 'Estratégias oficiais do sistema não podem ser editadas.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }

        // Validação de propriedade (apenas o dono ou admin edita)
        if ($portfolioEntity->getUser()->getId() != Auth::getCurrentUserId() && !Auth::isAdmin()) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Conversão para array para compatibilidade com a view
        $portfolio = [
            'id' => $portfolioEntity->getId(),
            'name' => $portfolioEntity->getName(),
            'description' => $portfolioEntity->getDescription(),
            'initial_capital' => $portfolioEntity->getInitialCapital(),
            'start_date' => $portfolioEntity->getStartDate()->format('Y-m-d'),
            'end_date' => $portfolioEntity->getEndDate() ? $portfolioEntity->getEndDate()->format('Y-m-d') : null,
            'rebalance_frequency' => $portfolioEntity->getRebalanceFrequency(),
            'output_currency' => $portfolioEntity->getOutputCurrency(),
            'is_system_default' => $portfolioEntity->isSystemDefault()
        ];

        $portfolioAssets = array_map(function($pa) {
            return [
                'id' => $pa->getId(),
                'asset_id' => $pa->getAsset()->getId(),
                'name' => $pa->getAsset()->getName(),
                'allocation_percentage' => $pa->getAllocationPercentage(),
                'performance_factor' => $pa->getPerformanceFactor()
            ];
        }, $portfolioEntity->getAssets()->toArray());

        $assetRepository = $this->entityManager->getRepository(\App\Entities\Asset::class);
        $allAssets = $assetRepository->findAllWithHistoricalBoundaries();
        
        require_once __DIR__ . '/../views/portfolio/edit.php';
    }

    public function update() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('portfolio/edit/' . $id));
            }

            $portfolio = $this->portfolioRepository->find($id);
            if (!$portfolio) {
                Session::setFlash('error', 'Portfólio não encontrado.');
                header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
                exit;
            }

            // Validação de propriedade
            if ($portfolio->getUser()->getId() != Auth::getCurrentUserId() && !Auth::isAdmin()) {
                Session::setFlash('error', 'Acesso negado.');
                header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
                exit;
            }

            $portfolio->setName($_POST['name'])
                      ->setDescription($_POST['description'])
                      ->setInitialCapital($_POST['initial_capital'])
                      ->setStartDate(new \DateTime($_POST['start_date']))
                      ->setEndDate($_POST['end_date'] ? new \DateTime($_POST['end_date']) : null)
                      ->setRebalanceFrequency($_POST['rebalance_frequency'])
                      ->setOutputCurrency($_POST['output_currency']);
            
            if (isset($_POST['assets'])) {
                // Remove assets antigos
                foreach ($portfolio->getAssets() as $pa) {
                    $this->entityManager->remove($pa);
                }
                $this->entityManager->flush();

                // Adiciona novos
                foreach ($_POST['assets'] as $assetData) {
                    $asset = $this->entityManager->find(\App\Entities\Asset::class, $assetData['asset_id']);
                    if ($asset) {
                        $pa = new \App\Entities\PortfolioAsset();
                        $pa->setPortfolio($portfolio)
                           ->setAsset($asset)
                           ->setAllocationPercentage($assetData['allocation'])
                           ->setPerformanceFactor($assetData['performance_factor'] ?? 1.0);
                        $this->entityManager->persist($pa);
                    }
                }
            }

            $this->entityManager->flush();
            Session::setFlash('success', 'Portfólio atualizado com sucesso!');
            
            header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
            exit;
        }
    }

    /**
     * POST: Processa a exclusão com lógica de permissão Admin
     */
    public function delete() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;
        
        $portfolio = $this->entityManager->find(Portfolio::class, $id);

        if (!$portfolio) {
            header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
            exit;
        }

        // Lógica de Permissão Sênior:
        // 1. Se for sistema: Só Admin deleta.
        // 2. Se for pessoal: Só o dono (ou Admin) deleta.
        $canDelete = false;
        if ($portfolio->isSystemDefault()) {
            $canDelete = Auth::isAdmin();
        } else {
            $canDelete = ($portfolio->getUser()->getId() == Auth::getCurrentUserId() || Auth::isAdmin());
        }

        if ($canDelete) {
            $this->entityManager->remove($portfolio);
            $this->entityManager->flush();
            Session::setFlash('success', 'Portfólio removido com sucesso.');
        } else {
            Session::setFlash('error', 'Você não tem permissão para excluir este portfólio.');
        }

        header('Location: /index.php?url=' . obfuscateUrl('portfolio'));
        exit;
    }

    public function toggleSystem() {
        // SEGURANÇA SÊNIOR: Apenas administradores podem acessar esta rota
        Auth::checkAdmin(); 

        $id = $this->params['id'] ?? null;
        $portfolio = $this->entityManager->find(Portfolio::class, $id);

        if ($portfolio) {
            // Inverte o status atual
            $newStatus = !$portfolio->isSystemDefault();
            $portfolio->setIsSystemDefault($newStatus);
            $this->entityManager->flush();
            
            $msg = $newStatus ? "Portfólio promovido ao sistema!" : "Portfólio removido do sistema.";
            Session::setFlash('success', $msg);
        }
        
        header('Location: /index.php?url=' . obfuscateUrl('portfolio/view/' . $id));
        exit;
    }
}
?>