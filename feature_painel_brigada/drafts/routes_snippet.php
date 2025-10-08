<?php
/**
 * ROTAS DO PAINEL DE BRIGADA
 * Adicionar em public/router.php ANTES do "require_once __DIR__ . '/index.php';"
 */

// ========================================
// ROTAS DO PAINEL DE BRIGADA (SEM LOGIN)
// ========================================

// API: GET /api/brigada/presentes
if ($uri === '/api/brigada/presentes') {
    requirePanelAuth();
    require_once __DIR__ . '/../src/controllers/PanelBrigadaController.php';
    $controller = new PanelBrigadaController();
    $controller->presentesApi();
    exit;
}

// VIEW: GET /painel/brigada
if ($uri === '/painel/brigada') {
    requirePanelAuth();
    require_once __DIR__ . '/../src/controllers/PanelBrigadaController.php';
    $controller = new PanelBrigadaController();
    $controller->painel();
    exit;
}
