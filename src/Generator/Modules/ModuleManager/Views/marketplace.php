<?= $this->extend($layout) ?>

<?= $this->section('content-header') ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1>Module Marketplace</h1>
    </div>
    <div class="col-sm-6 text-right">
        <a href="<?= base_url('system/modules') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to Registry
        </a>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?php if (session('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            <?= session('message') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?= session('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Debug Panel -->
    <?php if (isset($debug_installed)): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-warning collapsed-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bug mr-2"></i>Debug Info</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <h5>Módulos Instalados (getAvailableModules):</h5>
                    <pre class="small"><?= print_r($debug_installed, true) ?></pre>
                    
                    <h5>Módulos Locais com Debug:</h5>
                    <pre class="small"><?= print_r($localModules, true) ?></pre>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Local Development Repository -->
        <div class="col-md-8">
            <h4 class="mb-3 font-weight-bold text-muted">
                <i class="fas fa-folder-open mr-2 text-warning"></i>
                Local Modules Repository
            </h4>
            <div class="row">
                <?php if (empty($localModules)): ?>
                    <div class="col-12">
                        <div class="alert alert-light border shadow-sm">
                            <i class="fas fa-search mr-2"></i> 
                            Nenhum módulo encontrado em <code>c:\www\mods\Modules</code>.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($localModules as $mod): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm transition-hover border-0 <?= $mod['installed'] ? 'border-success' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary-soft p-3 rounded mr-3">
                                        <i class="fas fa-cube text-primary fa-lg"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <h5 class="card-title font-weight-bold mb-0"><?= $mod['name'] ?></h5>
                                        <?php if ($mod['installed']): ?>
                                            <div class="mt-1">
                                                <span class="badge badge-success badge-sm">
                                                    <i class="fas fa-check-circle mr-1"></i>Instalado
                                                </span>
                                                <?php if ($mod['active']): ?>
                                                    <span class="badge badge-primary badge-sm ml-1">
                                                        <i class="fas fa-power-off mr-1"></i>Ativo
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted small mb-3">Módulo detectado no repositório de desenvolvimento local.</p>
                                
                                <?php if (!$mod['installed']): ?>
                                    <form action="<?= base_url('system/modules/install') ?>" method="POST">
                                        <input type="hidden" name="local" value="<?= $mod['name'] ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm btn-block rounded-pill">
                                            <i class="fas fa-download mr-1"></i> Instalar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($mod['active']): ?>
                                        <button class="btn btn-outline-secondary btn-sm btn-block rounded-pill" disabled>
                                            <i class="fas fa-lock mr-1"></i> Desative para Remover
                                        </button>
                                    <?php else: ?>
                                        <form action="<?= base_url('system/modules/uninstall') ?>" method="POST" 
                                              onsubmit="return confirm('Tem certeza que deseja desinstalar este módulo?');">
                                            <input type="hidden" name="module" value="<?= $mod['name'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm btn-block rounded-pill">
                                                <i class="fas fa-trash-alt mr-1"></i> Desinstalar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- URL Installer -->
        <div class="col-md-4">
            <h4 class="mb-3 font-weight-bold text-muted">
                <i class="fas fa-cloud-download-alt mr-2 text-primary"></i>
                Cloud Installer
            </h4>
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-4">Instale módulos remotos informando a URL direta para o arquivo ZIP.</p>
                    
                    <form action="<?= base_url('system/modules/install') ?>" method="POST">
                        <div class="form-group mb-4">
                            <div class="input-group input-group-sm">
                                <input type="url" name="url" class="form-control" placeholder="URL do arquivo .zip" required>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info border-0 shadow-sm x-small py-2 mb-0">
                            <i class="fas fa-info-circle mr-1"></i>
                            O sistema irá extrair e preparar o módulo para ativação.
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded small border">
                <i class="fas fa-cog mr-1"></i> <strong>Config:</strong> Repositório local mapeado em <code>c:\www\mods\Modules</code>.
            </div>
        </div>
    </div>
</div>

<style>
.bg-primary-soft { background-color: rgba(0, 123, 255, 0.1); }
.transition-hover { transition: transform 0.2s; }
.transition-hover:hover { transform: translateY(-5px); }
.x-small { font-size: 0.75rem; }
</style>
<?= $this->endSection() ?>
