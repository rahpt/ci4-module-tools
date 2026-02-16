<?= $this->extend($layout) ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info elevation-2">
            <div class="inner">
                <h3>150</h3>
                <p>Novos Usuários</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="#" class="small-box-footer">Mais informações <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success elevation-2">
            <div class="inner">
                <h3>53<sup style="font-size: 20px">%</sup></h3>
                <p>Taxa de Conversão</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <a href="#" class="small-box-footer">Mais informações <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning elevation-2">
            <div class="inner">
                <h3>44</h3>
                <p>Módulos Ativos</p>
            </div>
            <div class="icon">
                <i class="fas fa-cubes"></i>
            </div>
            <a href="#" class="small-box-footer">Mais informações <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger elevation-2">
            <div class="inner">
                <h3>65</h3>
                <p>Visitantes Únicos</p>
            </div>
            <div class="icon">
                <i class="fas fa-eye"></i>
            </div>
            <a href="#" class="small-box-footer">Mais informações <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-flex align-items-center mb-3">
            <h4 class="mb-0 font-weight-bold">Widgets do Ecossistema</h4>
            <span class="badge badge-primary ml-3">Injeção Dinâmica via Hooks</span>
        </div>
    </div>
    
    <!-- Renderização dos Gadgets via Hooks -->
    <?= hook('dashboard_gadgets') ?>
    
    <?php if (empty(hook('dashboard_gadgets'))): ?>
        <div class="col-12">
            <div class="card card-outline card-info shadow-none border bg-light">
                <div class="card-body text-center py-5">
                    <i class="fas fa-puzzle-piece fa-4x text-muted mb-3"></i>
                    <h5 class="text-secondary">Nenhum Widget Detectado</h5>
                    <p class="text-muted mb-0">Ative módulos que registram componentes no hook <code>dashboard_gadgets</code> para popular este painel.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
