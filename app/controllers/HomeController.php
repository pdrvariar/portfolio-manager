<?php
class HomeController {
    private $portfolioModel;
    private $simulationModel;
    
    public function __construct() {
        $this->portfolioModel = new Portfolio();
        $this->simulationModel = new SimulationResult();
        Session::start();
    }
    
    public function index() {
        if (!Auth::isLoggedIn()) {
            // Página inicial para visitantes
            require_once __DIR__ . '/../views/home/welcome.php';
            return;
        }

        // SÊNIOR: Verificação de Aceite de Termos Obrigatório
        if (!Auth::hasAcceptedTerms()) {
            header('Location: /index.php?url=terms/accept');
            exit;
        }
        
        // Dashboard do usuário logado
        $userId = Auth::getCurrentUserId();
        // Buscamos os dois grupos separadamente para a UX
        $portfolios = $this->portfolioModel->getUserPortfolios($userId, false);
        $systemPortfolios = $this->portfolioModel->getSystemPortfolios(); // NOVO
        $stats = $this->simulationModel->getStatistics($userId);        

        // Últimas simulações
        $latestSimulations = [];
        foreach ($portfolios as $portfolio) {
            $simulation = $this->simulationModel->getLatest($portfolio['id']);
            if ($simulation) {
                $latestSimulations[] = [
                    'portfolio' => $portfolio,
                    'simulation' => $simulation
                ];
            }
        }
        
        require_once __DIR__ . '/../views/home/dashboard.php';
    }

    public function terms() {
        require_once __DIR__ . '/../views/home/terms.php';
    }

    /**
     * Exibe e processa o aceite forçado dos termos de uso
     */
    public function acceptTerms() {
        Auth::checkAuthentication();

        if (Auth::hasAcceptedTerms()) {
            header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['accept'])) {
                $userModel = new User();
                $userId = Auth::getCurrentUserId();
                
                // Atualiza no banco de dados
                $sql = "UPDATE users SET terms_accepted = 1, terms_accepted_at = NOW() WHERE id = ?";
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute([$userId])) {
                    Auth::updateSessionTermsAccepted(true);
                    Session::setFlash('success', 'Obrigado por aceitar nossos Termos de Uso.');
                    header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
                    exit;
                }
            }
            Session::setFlash('error', 'Você precisa aceitar os termos para continuar utilizando o sistema.');
        }

        require_once __DIR__ . '/../views/home/accept_terms_forced.php';
    }
}
?>