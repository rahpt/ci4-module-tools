<?= $this->extend($layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-cog mr-2"></i> Configurações do Sistema</h1>
        </div>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="card card-primary card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="settings-tabs" role="tablist">
                <?php $first = true; foreach ($settings as $group => $config): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $first ? 'active' : '' ?>" id="tab-<?= $group ?>-link" 
                           data-toggle="pill" href="#tab-<?= $group ?>" role="tab" 
                           aria-controls="tab-<?= $group ?>" aria-selected="<?= $first ? 'true' : 'false' ?>">
                            <?= $config['label'] ?>
                        </a>
                    </li>
                <?php $first = false; endforeach; ?>
            </ul>
        </div>
        
        <form action="<?= base_url('system/settings/save') ?>" method="post">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="tab-content" id="settings-tabs-content">
                    <?php $first = true; foreach ($settings as $group => $config): ?>
                        <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= $group ?>" 
                             role="tabpanel" aria-labelledby="tab-<?= $group ?>-link">
                            
                            <?php foreach ($config['fields'] as $name => $field): ?>
                                <?php 
                                    $settingKey = "{$group}.{$name}";
                                    $currentValue = setting($settingKey);
                                    if ($currentValue === null && isset($field['default'])) {
                                        $currentValue = $field['default'];
                                    }
                                    $inputId = "{$group}_{$name}";
                                ?>
                                
                                <div class="form-group row">
                                    <label for="<?= $inputId ?>" class="col-sm-3 col-form-label">
                                        <?= $field['label'] ?>
                                    </label>
                                    <div class="col-sm-9">
                                        <?php if ($field['type'] === 'boolean'): ?>
                                            <div class="custom-control custom-switch mt-2">
                                                <input type="hidden" name="<?= $inputId ?>" value="0">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="<?= $inputId ?>" name="<?= $inputId ?>" value="1"
                                                       <?= $currentValue ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="<?= $inputId ?>"></label>
                                            </div>
                                        <?php elseif ($field['type'] === 'textarea'): ?>
                                            <textarea class="form-control" id="<?= $inputId ?>" 
                                                      name="<?= $inputId ?>" rows="3"><?= esc($currentValue) ?></textarea>
                                        <?php else: ?>
                                            <input type="<?= $field['type'] ?>" class="form-control" 
                                                   id="<?= $inputId ?>" name="<?= $inputId ?>" 
                                                   value="<?= esc($currentValue) ?>">
                                        <?php endif; ?>
                                        
                                        <?php if (isset($field['helper'])): ?>
                                            <small class="form-text text-muted"><?= $field['helper'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                        </div>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save mr-1"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
