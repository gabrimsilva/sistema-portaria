<?php

/**
 * Controller para interfaces web de LGPD e Privacidade
 * 
 * Gerencia as páginas públicas de privacidade, direitos dos titulares
 * e gestão de consentimentos conforme LGPD.
 */
class PrivacyController 
{
    private $lgpdService;

    public function __construct() 
    {
        $this->lgpdService = new LGPDService();
    }

    /**
     * Página principal de Privacidade - Aviso de Privacidade do Sistema
     * Rota: GET /privacy
     */
    public function index()
    {
        $pageTitle = "Privacidade e Proteção de Dados";
        $breadcrumbs = [
            ['name' => 'Início', 'url' => '/'],
            ['name' => 'Privacidade', 'url' => '/privacy', 'active' => true]
        ];

        // Carregar conteúdo do aviso de privacidade
        $privacyNotice = $this->getPrivacyNoticeContent();
        
        require_once __DIR__ . '/../../views/privacy/index.php';
    }

    /**
     * Portal do Titular - Interface para exercer direitos LGPD
     * Rota: GET /privacy/portal
     */
    public function portal()
    {
        $pageTitle = "Portal do Titular de Dados";
        $breadcrumbs = [
            ['name' => 'Início', 'url' => '/'],
            ['name' => 'Privacidade', 'url' => '/privacy'],
            ['name' => 'Portal do Titular', 'url' => '/privacy/portal', 'active' => true]
        ];

        require_once __DIR__ . '/../../views/privacy/portal.php';
    }

    /**
     * Processar solicitação de direitos LGPD
     * Rota: POST /privacy/portal
     * 🛡️ VERSÃO SEGURA: Verificação CSRF + Fluxo assíncrono sem vazamento PII
     */
    public function processRequest()
    {
        try {
            // 🛡️ PROTEÇÃO CSRF OBRIGATÓRIA
            if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido. Recarregue a página e tente novamente.');
            }
            
            $requestType = $_POST['request_type'] ?? null;
            $cpf = $_POST['cpf'] ?? null;
            $email = $_POST['email'] ?? null;
            $message = $_POST['message'] ?? null;

            if (!$requestType || (!$cpf && !$email)) {
                throw new Exception('Tipo de solicitação e CPF ou email são obrigatórios');
            }

            // 🔒 FLUXO SEGURO: Todas as solicitações são processadas de forma assíncrona
            // JAMAIS retornamos dados PII diretamente - sempre enviamos por email seguro
            switch ($requestType) {
                case 'access':
                case 'export':
                    // 🔐 SEGURANÇA: Registra solicitação para processamento manual seguro
                    // Nunca exporta dados diretamente - sempre via canal seguro com verificação
                    $result = $this->lgpdService->createSecureDataRequest($cpf, $email, $requestType, $message);
                    $successMessage = 'Solicitação registrada! Nossa equipe de privacidade entrará em contato em até 15 dias úteis para verificação de identidade e envio seguro dos dados.';
                    break;
                    
                case 'correction':
                    $result = $this->lgpdService->requestDataCorrection($cpf, $email, $message);
                    $successMessage = 'Solicitação de correção registrada com sucesso. Você receberá retorno em até 15 dias úteis.';
                    break;
                    
                case 'deletion':
                    $result = $this->lgpdService->requestDataDeletion($cpf, $email, $message);
                    $successMessage = 'Solicitação de exclusão registrada com sucesso. Você receberá retorno em até 15 dias úteis.';
                    break;
                    
                default:
                    throw new Exception('Tipo de solicitação inválido');
            }

            // Redirecionar com sucesso
            header('Location: /privacy/portal?success=' . urlencode($successMessage ?? 'Solicitação processada com sucesso'));
            exit;

        } catch (Exception $e) {
            // Redirecionar com erro
            header('Location: /privacy/portal?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Página de Gestão de Cookies
     * Rota: GET /privacy/cookies
     */
    public function cookies()
    {
        $pageTitle = "Gestão de Cookies";
        $breadcrumbs = [
            ['name' => 'Início', 'url' => '/'],
            ['name' => 'Privacidade', 'url' => '/privacy'],
            ['name' => 'Cookies', 'url' => '/privacy/cookies', 'active' => true]
        ];

        // Obter preferências atuais de cookies
        $cookiePreferences = $this->getCookiePreferences();
        
        require_once __DIR__ . '/../../views/privacy/cookies.php';
    }

    /**
     * Processar alterações de preferências de cookies
     * Rota: POST /privacy/cookies
     * 🛡️ VERSÃO SEGURA: Proteção CSRF + Flags de segurança nos cookies
     */
    public function updateCookiePreferences()
    {
        try {
            // 🛡️ PROTEÇÃO CSRF OBRIGATÓRIA
            if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido. Recarregue a página e tente novamente.');
            }
            
            $preferences = [
                'functional' => isset($_POST['cookies_functional']),
                'performance' => isset($_POST['cookies_performance']),
                'marketing' => isset($_POST['cookies_marketing'])
            ];

            // 🔒 COOKIES SEGUROS: Configuração com flags de segurança adequadas
            $expiry = time() + (365 * 24 * 60 * 60); // 1 ano
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
            
            setcookie(
                'cookie_preferences', 
                json_encode($preferences), 
                [
                    'expires' => $expiry,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $isHttps,        // Só HTTPS em produção
                    'httponly' => true,          // Não acessível via JavaScript
                    'samesite' => 'Lax'         // Proteção CSRF adicional
                ]
            );

            header('Location: /privacy/cookies?success=' . urlencode('Preferências atualizadas com sucesso'));
            exit;

        } catch (Exception $e) {
            header('Location: /privacy/cookies?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Obtém o conteúdo do aviso de privacidade
     */
    private function getPrivacyNoticeContent()
    {
        return [
            'title' => 'Proteção de Dados Pessoais',
            'subtitle' => 'Sistema de Controle de Acesso Renner Coatings',
            'sections' => [
                [
                    'title' => 'Dados Coletados',
                    'icon' => 'fas fa-database',
                    'content' => [
                        '<strong>Identificação:</strong> Nome, CPF, email, telefone',
                        '<strong>Profissionais:</strong> Cargo, empresa, setor',
                        '<strong>Biométricos:</strong> Foto facial para controle de acesso',
                        '<strong>Registros:</strong> Horários de entrada/saída, setores visitados'
                    ]
                ],
                [
                    'title' => 'Finalidades',
                    'icon' => 'fas fa-bullseye',
                    'content' => [
                        '<strong>Controle de acesso</strong> às instalações',
                        '<strong>Segurança patrimonial</strong> e proteção de pessoas',
                        '<strong>Gestão</strong> de funcionários, visitantes e prestadores',
                        '<strong>Auditoria</strong> e conformidade legal'
                    ]
                ],
                [
                    'title' => 'Base Legal',
                    'icon' => 'fas fa-balance-scale',
                    'content' => [
                        '<strong>Funcionários/Prestadores:</strong> Execução de contrato (Art. 7º, II LGPD)',
                        '<strong>Visitantes:</strong> Legítimo interesse (Art. 7º, VI LGPD)',
                        '<strong>Dados Biométricos:</strong> Prevenção à fraude e segurança (Art. 11, II, g LGPD)'
                    ]
                ],
                [
                    'title' => 'Segurança',
                    'icon' => 'fas fa-shield-alt',
                    'content' => [
                        '<strong>Criptografia</strong> avançada AES-256-GCM',
                        '<strong>Controle de acesso</strong> restrito',
                        '<strong>Backup</strong> seguro e monitoramento contínuo',
                        '<strong>Auditoria</strong> de todas as operações'
                    ]
                ],
                [
                    'title' => 'Retenção',
                    'icon' => 'fas fa-clock',
                    'content' => [
                        '<strong>Funcionários:</strong> 5 anos após desligamento',
                        '<strong>Visitantes:</strong> 2 anos após última visita',
                        '<strong>Prestadores:</strong> 5 anos após término do contrato',
                        '<strong>Anonimização automática</strong> após prazo legal'
                    ]
                ],
                [
                    'title' => 'Seus Direitos',
                    'icon' => 'fas fa-user-shield',
                    'content' => [
                        '✅ <strong>Acesso</strong> aos seus dados',
                        '✅ <strong>Correção</strong> de informações',
                        '✅ <strong>Eliminação</strong> quando aplicável',
                        '✅ <strong>Portabilidade</strong> dos dados',
                        '✅ <strong>Oposição</strong> ao tratamento'
                    ]
                ]
            ],
            'contact' => [
                'email' => 'privacidade@renner.com.br',
                'dpo' => '[Nome do DPO]',
                'response_time' => '15 dias úteis'
            ],
            'important_notes' => [
                '<strong>Dados obrigatórios</strong> para acesso às instalações',
                '<strong>Base legal específica</strong> para dados biométricos (Art. 11, II, g)',
                '<strong>Acesso necessário</strong> - dados biométricos são obrigatórios para acessar',
                '<strong>Anonimização automática</strong> após prazo legal'
            ]
        ];
    }

    /**
     * Obtém as preferências atuais de cookies
     */
    private function getCookiePreferences()
    {
        $defaultPreferences = [
            'functional' => false,
            'performance' => false,
            'marketing' => false
        ];

        if (isset($_COOKIE['cookie_preferences'])) {
            $saved = json_decode($_COOKIE['cookie_preferences'], true);
            return array_merge($defaultPreferences, $saved);
        }

        return $defaultPreferences;
    }
}