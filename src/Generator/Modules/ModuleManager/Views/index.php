<?= $this->extend($layout) ?>

<?= $this->section('content-header') ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1>Module Registry</h1>
    </div>
    <div class="col-sm-6 text-right">
        <a href="<?= base_url('system/modules/marketplace') ?>" class="btn btn-primary">
            <i class="fas fa-cloud-download-alt mr-1"></i> Marketplace
        </a>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <?php foreach ($modules as $slug => $module): ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card card-outline <?= $module['active'] ? 'card-success' : 'card-secondary' ?> h-100 shadow-sm transition-hover">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <?= $module['label'] ?>
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-<?= $module['active'] ? 'success' : 'secondary' ?>">
                            v<?= $module['version'] ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-0">
                        <i class="fas fa-folder mr-1"></i> <?= $module['path'] ?? 'app/Modules/'.ucfirst($slug) ?>
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-link mr-1"></i> /<?= $slug ?>
                    </p>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <a href="<?= base_url('system/modules/toggle/'.$slug) ?>" 
                       class="btn btn-block btn-sm <?= $module['active'] ? 'btn-outline-danger' : 'btn-success shadow-sm' ?>">
                        <i class="fas <?= $module['active'] ? 'fa-power-off' : 'fa-play' ?> mr-1"></i>
                        <?= $module['active'] ? 'Deactivate' : 'Activate' ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.transition-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.transition-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>
<?= $this->endSection() ?>
