<?php

require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../../config/csrf.php';

class BrigadaController {
    private $db;
    private $authService;
    private $auditService;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->db = new Database();
        $this->authService = new AuthorizationService();
        $this->auditService = new AuditService();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    private function checkPermission($permission) {
        if (!$this->authService->hasPermission($permission)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado. Você não tem permissão para realizar esta ação.'
            ]);
            exit;
        }
    }
    
    /**
     * Listar brigadistas
     */
    public function index() {
        $this->checkPermission('brigada.read');
        
        try {
            $brigadistas = $this->db->fetchAll("
                SELECT 
                    b.id,
                    b.professional_id,
                    b.active,
                    b.note,
                    b.created_at,
                    p.nome AS professional_name,
                    p.setor AS sector,
                    p.foto_url
                FROM public.brigadistas b
                JOIN public.profissionais_renner p ON p.id = b.professional_id
                WHERE b.active = TRUE
                ORDER BY p.nome ASC
            ");
            
            $pageTitle = 'Brigada de Incêndio';
            include __DIR__ . '/../../views/brigada/index.php';
            
        } catch (Exception $e) {
            error_log("Erro ao listar brigadistas: " . $e->getMessage());
            http_response_code(500);
            echo "Erro ao carregar lista de brigadistas.";
        }
    }
    
    /**
     * API: Buscar profissionais para adicionar à brigada
     */
    public function apiSearchProfessionals() {
        $this->checkPermission('brigada.read');
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $q = trim($_GET['q'] ?? '');
            
            if (mb_strlen($q) < 2) {
                echo json_encode([]);
                return;
            }
            
            // Buscar profissionais que ainda NÃO são brigadistas
            $professionals = $this->db->fetchAll("
                SELECT 
                    p.id,
                    p.nome AS name,
                    p.setor AS sector
                FROM public.profissionais_renner p
                LEFT JOIN public.brigadistas b ON b.professional_id = p.id AND b.active = TRUE
                WHERE b.id IS NULL
                  AND unaccent(p.nome) ILIKE unaccent(?)
                ORDER BY p.nome ASC
                LIMIT 20
            ", ['%' . $q . '%']);
            
            echo json_encode($professionals);
            
        } catch (Exception $e) {
            error_log("Erro na busca de profissionais: " . $e->getMessage());
            echo json_encode(['error' => 'Erro ao buscar profissionais']);
        }
    }
    
    /**
     * Adicionar brigadista
     */
    public function add() {
        $this->checkPermission('brigada.write');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Location: /brigada');
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $professionalId = (int)($_POST['professional_id'] ?? 0);
            $ramal = trim($_POST['ramal'] ?? '');
            
            if ($professionalId <= 0) {
                throw new Exception("ID do profissional inválido");
            }
            
            // Verificar se o profissional existe
            $professional = $this->db->fetch("
                SELECT id, nome FROM public.profissionais_renner WHERE id = ?
            ", [$professionalId]);
            
            if (!$professional) {
                throw new Exception("Profissional não encontrado");
            }
            
            // Verificar se já é brigadista
            $existing = $this->db->fetch("
                SELECT id FROM public.brigadistas 
                WHERE professional_id = ? AND active = TRUE
            ", [$professionalId]);
            
            if ($existing) {
                $_SESSION['flash_error'] = "Este profissional já está na brigada!";
                header('Location: /brigada');
                exit;
            }
            
            // Inserir na brigada (ou reativar se já existiu)
            $this->db->query("
                INSERT INTO public.brigadistas (professional_id, ramal, active)
                VALUES (?, ?, TRUE)
                ON CONFLICT (professional_id) 
                DO UPDATE SET active = TRUE, ramal = EXCLUDED.ramal, updated_at = NOW()
            ", [$professionalId, $ramal ?: null]);
            
            // Auditoria
            $this->auditService->log(
                'brigada',
                'create',
                null,
                ['professional_id' => $professionalId, 'professional_name' => $professional['nome']],
                "Adicionou {$professional['nome']} à brigada de incêndio"
            );
            
            $_SESSION['flash_success'] = "Brigadista adicionado com sucesso!";
            header('Location: /brigada');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao adicionar brigadista: " . $e->getMessage());
            $_SESSION['flash_error'] = "Erro ao adicionar brigadista: " . $e->getMessage();
            header('Location: /brigada');
            exit;
        }
    }
    
    /**
     * Upload de foto do brigadista
     */
    public function uploadFoto() {
        $this->checkPermission('brigada.write');
        
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            return;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $professionalId = (int)($_POST['professional_id'] ?? 0);
            
            if ($professionalId <= 0) {
                throw new Exception("ID do profissional inválido");
            }
            
            // Verificar se o arquivo foi enviado
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Nenhuma foto foi enviada");
            }
            
            $file = $_FILES['photo'];
            
            // Validar tamanho (2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception("A foto deve ter no máximo 2MB");
            }
            
            // Validar tipo MIME
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG ou WEBP");
            }
            
            // Gerar nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'brigadista_' . $professionalId . '_' . time() . '.' . $extension;
            
            // Diretório de destino
            $uploadDir = __DIR__ . '/../../public/uploads/profissionais/';
            
            // Criar diretório se não existir
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $targetPath = $uploadDir . $fileName;
            
            // Remover foto anterior se existir
            $oldPhoto = $this->db->fetch("
                SELECT foto_url FROM public.profissionais_renner WHERE id = ?
            ", [$professionalId]);
            
            if ($oldPhoto && !empty($oldPhoto['foto_url'])) {
                $oldPath = __DIR__ . '/../../public/uploads/' . $oldPhoto['foto_url'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Erro ao salvar a foto");
            }
            
            // Atualizar banco de dados
            $fotoUrl = 'profissionais/' . $fileName;
            $this->db->query("
                UPDATE public.profissionais_renner 
                SET foto_url = ?, updated_at = NOW()
                WHERE id = ?
            ", [$fotoUrl, $professionalId]);
            
            // Auditoria
            $professional = $this->db->fetch("
                SELECT nome FROM public.profissionais_renner WHERE id = ?
            ", [$professionalId]);
            
            $this->auditService->log(
                'brigada',
                'update',
                $professionalId,
                ['foto_url' => $fotoUrl],
                "Atualizou foto do brigadista {$professional['nome']}"
            );
            
            echo json_encode([
                'success' => true,
                'foto_url' => $fotoUrl,
                'message' => 'Foto atualizada com sucesso'
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao fazer upload de foto: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Remover brigadista
     */
    public function remove() {
        $this->checkPermission('brigada.write');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Location: /brigada');
            exit;
        }
        
        try {
            CSRFProtection::verifyRequest();
            
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception("ID inválido");
            }
            
            // Buscar dados antes de remover (para auditoria)
            $brigadista = $this->db->fetch("
                SELECT b.id, b.professional_id, p.nome AS professional_name
                FROM public.brigadistas b
                JOIN public.profissionais_renner p ON p.id = b.professional_id
                WHERE b.id = ?
            ", [$id]);
            
            if (!$brigadista) {
                throw new Exception("Brigadista não encontrado");
            }
            
            // Remover (soft delete - marcar como inativo)
            $this->db->query("
                UPDATE public.brigadistas 
                SET active = FALSE, updated_at = NOW()
                WHERE id = ?
            ", [$id]);
            
            // Auditoria
            $this->auditService->log(
                'brigada',
                'delete',
                $id,
                ['professional_name' => $brigadista['professional_name']],
                "Removeu {$brigadista['professional_name']} da brigada de incêndio"
            );
            
            $_SESSION['flash_success'] = "Brigadista removido com sucesso!";
            header('Location: /brigada');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao remover brigadista: " . $e->getMessage());
            $_SESSION['flash_error'] = "Erro ao remover brigadista: " . $e->getMessage();
            header('Location: /brigada');
            exit;
        }
    }
}
