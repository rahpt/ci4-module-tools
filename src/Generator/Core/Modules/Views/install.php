<?= $this->extend($layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Install New Module</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Enter the direct URL of a module ZIP file from your repository. The system will download and extract it to your Modules directory.</p>
                    
                    <form action="<?= base_url('system/modules/install') ?>" method="POST">
                        <div class="form-group">
                            <label for="url">Package URL (ZIP)</label>
                            <input type="url" name="url" id="url" class="form-control" placeholder="https://repo.example.com/api/modules/blog.zip" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block p-3 mt-4 shadow-sm">
                            <i class="fas fa-download mr-2"></i> Download and Install
                        </button>
                    </form>

                    <hr>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i> Make sure the ZIP contains the module structure directly (e.g. <code>Config/Module.php</code> should be in the root of the ZIP if it's the module folder).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
