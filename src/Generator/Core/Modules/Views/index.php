<?= $this->extend($layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-2 text-gray-800">Gerenciador de Módulos</h1>
            <p class="mb-4">Gerencie seu ecossistema modular, ative extensões e monitore versões.</p>
        </div>
        <div class="col-auto">
            <a href="<?= base_url('system/modules/install') ?>" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus"></i> Instalar via URL
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success border-left-success shadow animated--grow-in">
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-left-danger shadow animated--grow-in">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <h4 class="mb-3 font-weight-bold"><i class="fas fa-hdd mr-2"></i> Módulos Instalados</h4>
    <div class="row mb-5">
        <?php foreach ($modules as $slug => $module): ?>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-<?= $module['active'] ? 'success' : 'warning' ?> shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="mb-1">
                                    <span class="badge badge-info">Instalado</span>
                                    <?php if ($module['active']): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inativo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $module['label'] ?></div>
                                <div class="text-xs text-muted mt-1">
                                    Versão: <?= $module['version'] ?> | Slug: <code><?= $slug ?></code>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-cube fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="btn-group w-100">
                                <?php if ($module['active']): ?>
                                    <form action="<?= base_url('system/modules/deactivate/' . $slug) ?>" method="POST" class="w-75">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="fas fa-power-off"></i> Desativar
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-light w-25" title="Não é possível excluir um módulo ativo" disabled>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <form action="<?= base_url('system/modules/activate/' . $slug) ?>" method="POST" class="w-75">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success w-100">
                                            <i class="fas fa-check"></i> Ativar
                                        </button>
                                    </form>
                                    <form action="<?= base_url('system/modules/delete/' . $slug) ?>" method="POST" class="w-25" onsubmit="return confirm('Tem certeza que deseja excluir este módulo permanentemente?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100" title="Excluir Módulo">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <hr>

    <h4 class="mt-5 mb-3 font-weight-bold"><i class="fas fa-store mr-2"></i> Marketplace Local</h4>
    <p class="text-muted small mb-4">Módulos disponíveis para download no servidor local de marketplace.</p>
    
    <div class="row">
        <?php if (!empty($marketplaceModules)): ?>
            <?php foreach ($marketplaceModules as $mModule): ?>
                <?php 
                    $isInstalled = isset($modules[$mModule['slug']]) || isset($modules[strtolower($mModule['slug'])]);
                ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card shadow h-100 border-0 bg-light">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <?php if ($mModule['icon_path']): ?>
                                    <img src="<?= base_url($mModule['icon_path']) ?>" class="rounded mr-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-white rounded mr-3 d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;">
                                        <i class="fas fa-cloud text-primary"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0 font-weight-bold"><?= $mModule['display_name'] ?></h6>
                                    <small class="text-muted">v<?= $mModule['version'] ?></small>
                                </div>
                            </div>
                            
                            <?php if ($isInstalled): ?>
                                <span class="badge badge-success mb-2"><i class="fas fa-check-circle mr-1"></i> Já Instalado</span>
                            <?php else: ?>
                                <button class="btn btn-sm btn-primary btn-block mb-2" onclick="installFromMarketplace('<?= $mModule['zip_path'] ?>')">
                                    <i class="fas fa-download mr-1"></i> Baixar e Instalar
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?= base_url('marketplace/view/' . $mModule['slug']) ?>" target="_blank" class="btn btn-xs btn-link btn-block text-muted">
                                Ver detalhes no Marketplace <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-4 bg-white rounded border">
                <p class="text-muted mb-0">Nenhum módulo encontrado no marketplace local.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function installFromMarketplace(url) {
    if (confirm('Deseja baixar e instalar este módulo agora?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('system/modules/install') ?>';
        
        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = csrfName;
        csrfInput.value = csrfHash;
        form.appendChild(csrfInput);

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'url';
        input.value = '<?= base_url() ?>' + url;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?= $this->endSection() ?>
