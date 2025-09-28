<?php
/**
 * 🎨 DEMONSTRAÇÃO LOGOSERVICE 3.0 
 * 
 * Página para demonstrar todos os recursos avançados:
 * - Responsividade automática
 * - Fallbacks inteligentes
 * - Múltiplos contextos
 * - Dark/Light mode
 * - WebP + lazy loading
 */

// Configuração da página
$pageConfig = [
    'title' => 'LogoService 3.0 - Demonstração Avançada',
    'context' => 'default',
    'pageTitle' => 'Demonstração LogoService 3.0',
    'additionalCSS' => ['/assets/css/logo-responsive.css']
];

// Renderizar layout start
$layout = LayoutService::renderPageSkeleton($pageConfig);
echo $layout['start'];
?>

<!-- 🎨 DEMONSTRAÇÃO LOGOSERVICE 3.0 -->
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info">
            <h4><i class="fas fa-info-circle"></i> LogoService 3.0 - Recursos Avançados</h4>
            <p>Esta página demonstra todos os recursos do novo sistema de gestão de logos:</p>
            <ul>
                <li><strong>Responsividade automática</strong> - Adapta-se a diferentes tamanhos de tela</li>
                <li><strong>Fallbacks inteligentes</strong> - WebP → PNG → Fallback → Ícone</li>
                <li><strong>Dark/Light mode</strong> - Logos diferentes para cada tema</li>
                <li><strong>Múltiplos contextos</strong> - Login, sidebar, header, card, footer</li>
                <li><strong>Performance otimizada</strong> - Lazy loading e compressão</li>
            </ul>
        </div>
    </div>
</div>

<!-- Demonstração por Contextos -->
<div class="row">
    <!-- Logo Login -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-sign-in-alt"></i> Contexto: Login</h5>
            </div>
            <div class="card-body text-center">
                <p><strong>Renner (Método Simples):</strong></p>
                <?= LogoService::renderSimpleLogo('renner', 'login') ?>
                
                <hr>
                
                <p><strong>Renner (Método Avançado):</strong></p>
                <?= LogoService::renderAdvancedLogo('renner', 'login', [
                    'theme' => 'auto',
                    'lazy' => false,
                    'responsive' => true,
                    'fallbackIcon' => true
                ]) ?>
                
                <hr>
                
                <p><strong>Sistema:</strong></p>
                <?= LogoService::renderAdvancedLogo('sistema', 'login') ?>
            </div>
        </div>
    </div>

    <!-- Logo Sidebar -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-columns"></i> Contexto: Sidebar</h5>
            </div>
            <div class="card-body">
                <p><strong>Renner (usado na navegação):</strong></p>
                <div class="bg-dark p-3 rounded mb-3">
                    <?= LogoService::renderAdvancedLogo('renner', 'sidebar') ?>
                    <span class="text-white ml-2">Controle Acesso</span>
                </div>
                
                <p><strong>Empresa:</strong></p>
                <div class="bg-primary p-3 rounded">
                    <?= LogoService::renderAdvancedLogo('empresa', 'sidebar') ?>
                    <span class="text-white ml-2">Sistema Empresarial</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Logo Header -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-window-maximize"></i> Contexto: Header</h5>
            </div>
            <div class="card-body text-center">
                <p><strong>Header simples:</strong></p>
                <div class="border p-2 mb-3">
                    <?= LogoService::renderAdvancedLogo('renner', 'header') ?>
                </div>
                
                <p><strong>Header com texto:</strong></p>
                <div class="border p-2 d-flex align-items-center justify-content-center">
                    <?= LogoService::renderAdvancedLogo('sistema', 'header') ?>
                    <span class="ml-2 font-weight-bold">Sistema</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Card -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-id-card"></i> Contexto: Card</h5>
            </div>
            <div class="card-body text-center">
                <p><strong>Card de empresa:</strong></p>
                <div class="border p-3 mb-3">
                    <?= LogoService::renderAdvancedLogo('renner', 'card') ?>
                    <br><small class="text-muted">Renner Coatings</small>
                </div>
                
                <p><strong>Card do sistema:</strong></p>
                <div class="border p-3">
                    <?= LogoService::renderAdvancedLogo('sistema', 'card') ?>
                    <br><small class="text-muted">Sistema Seguro</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Footer -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-window-minimize"></i> Contexto: Footer</h5>
            </div>
            <div class="card-body text-center">
                <p><strong>Footer empresarial:</strong></p>
                <div class="bg-light p-3 border-top">
                    <?= LogoService::renderAdvancedLogo('empresa', 'footer') ?>
                    <br><small class="text-muted">&copy; 2024 Empresa</small>
                </div>
                
                <p><strong>Footer do sistema:</strong></p>
                <div class="bg-secondary p-3 text-white">
                    <?= LogoService::renderAdvancedLogo('renner', 'footer') ?>
                    <br><small>Powered by Renner</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Informações Técnicas -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> Logos Disponíveis (Scan Automático)</h5>
            </div>
            <div class="card-body">
                <?php $availableLogos = LogoService::scanAvailableLogos(); ?>
                <?php if (!empty($availableLogos)): ?>
                    <?php foreach ($availableLogos as $type => $logos): ?>
                        <p><strong><?= ucfirst($type) ?>:</strong></p>
                        <ul class="small">
                            <?php foreach ($logos as $logo): ?>
                                <li><?= basename($logo) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Nenhum logo encontrado no scan automático.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-code"></i> Recursos Técnicos</h5>
            </div>
            <div class="card-body">
                <div class="small">
                    <p><strong>Formatos suportados:</strong></p>
                    <ul>
                        <li>PNG (padrão)</li>
                        <li>WebP (otimizado)</li>
                        <li>Dark mode variants</li>
                        <li>SVG (futuro)</li>
                    </ul>
                    
                    <p><strong>Contextos responsivos:</strong></p>
                    <ul>
                        <li>Desktop, Tablet, Mobile</li>
                        <li>Picture element</li>
                        <li>Lazy loading</li>
                        <li>Fallback automático</li>
                    </ul>
                    
                    <p><strong>Performance:</strong></p>
                    <ul>
                        <li>Compressão automática</li>
                        <li>CDN ready</li>
                        <li>Cache-friendly</li>
                        <li>Print optimized</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Teste de Responsividade -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-mobile-alt"></i> Teste de Responsividade</h5>
            </div>
            <div class="card-body">
                <p>Os logos abaixo se adaptam automaticamente ao tamanho da tela:</p>
                
                <div class="row text-center">
                    <div class="col-6 col-md-3">
                        <p><small>Mobile</small></p>
                        <div class="border p-2">
                            <?= LogoService::renderAdvancedLogo('renner', 'login', ['responsive' => true]) ?>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <p><small>Tablet</small></p>
                        <div class="border p-2">
                            <?= LogoService::renderAdvancedLogo('sistema', 'header', ['responsive' => true]) ?>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <p><small>Desktop</small></p>
                        <div class="border p-2">
                            <?= LogoService::renderAdvancedLogo('empresa', 'card', ['responsive' => true]) ?>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <p><small>Footer</small></p>
                        <div class="border p-2">
                            <?= LogoService::renderAdvancedLogo('renner', 'footer', ['responsive' => true]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS específico para demonstração */
.logo-container img:hover {
    transform: scale(1.05);
    transition: transform 0.2s ease;
}

@media (prefers-color-scheme: dark) {
    .card {
        background-color: #2c3e50;
        border-color: #34495e;
    }
    
    .card-header {
        background-color: #34495e;
        border-color: #3d566e;
    }
}
</style>

<?php
// Renderizar layout end
echo $layout['end'];
?>