<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= esc($title) ?></h1>

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Notifikasi</h6>
            <a href="<?= site_url('notifications/mark-as-read/all') ?>" class="btn btn-sm btn-outline-primary" id="markAllReadBtn">
                <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($notifications) && is_array($notifications)) : ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification) : ?>
                        <li class="list-group-item <?= !$notification['is_read'] ? 'list-group-item-light font-weight-bold' : '' ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 small">
                                    <?php
                                    $icon = 'fas fa-info-circle'; // Default icon
                                    if (strpos($notification['type'], 'alfa') !== false) $icon = 'fas fa-calendar-times text-danger';
                                    if (strpos($notification['type'], 'sakit_izin') !== false) $icon = 'fas fa-notes-medical text-warning';
                                    // Add more icons based on type
                                    ?>
                                    <i class="<?= $icon ?>"></i>
                                    <?= esc(ucfirst(str_replace('_', ' ', $notification['type']))) ?>
                                    <?php if ($notification['student_id']) : ?>
                                        <?php
                                        // Quick way to get student name, ideally join in controller or use a dedicated method
                                        $studentModel = new \App\Models\StudentModel();
                                        $student = $studentModel->find($notification['student_id']);
                                        if($student) echo ' - Siswa: ' . esc($student['full_name']);
                                        ?>
                                    <?php endif; ?>
                                </h5>
                                <small><?= esc(time_ago($notification['created_at'])) ?></small>
                            </div>
                            <p class="mb-1"><?= esc($notification['message']) ?></p>
                            <small class="d-flex justify-content-end">
                                <?php if (!$notification['is_read']) : ?>
                                    <a href="<?= site_url('notifications/mark-as-read/' . $notification['id']) ?>" class="btn btn-sm btn-link p-0 mark-single-read-btn">Tandai dibaca</a>
                                <?php else : ?>
                                    <span class="text-muted">Sudah dibaca</span>
                                <?php endif; ?>
                                <?php if (!empty($notification['link'])) : ?>
                                    <a href="<?= esc($notification['link'], 'attr') ?>" class="btn btn-sm btn-link p-0 ms-2">Lihat Detail</a>
                                <?php endif; ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-3">
                    <?= $pager->links() ?>
                </div>
            <?php else : ?>
                <p class="text-center">Tidak ada notifikasi.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // AJAX for marking all as read
    $('#markAllReadBtn').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET', // Or POST if you prefer for actions, but GET is fine for this with CSRF on main page
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Refresh page or update UI dynamically
                    location.reload();
                } else {
                    alert(response.error || 'Gagal menandai semua notifikasi.');
                }
            },
            error: function() {
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }
        });
    });

    // AJAX for marking single notification as read (optional, if you want to avoid page reload from controller)
    // The controller currently redirects or returns JSON. If it always returns JSON for single mark as read:
    $('.mark-single-read-btn').on('click', function(e) {
        e.preventDefault();
        var link = $(this);
        var url = link.attr('href');

        $.ajax({
            url: url,
            type: 'GET', // Or POST
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Option 1: Reload page to reflect changes (simplest)
                     location.reload();
                    // Option 2: Dynamically update UI (more complex)
                    // link.closest('li').removeClass('list-group-item-light font-weight-bold').addClass('text-muted');
                    // link.replaceWith('<span class="text-muted">Sudah dibaca</span>');
                    // Update unread count in navbar (requires another AJAX call or passing new count in response)
                } else {
                    alert(response.error || 'Gagal menandai notifikasi.');
                }
            },
            error: function() {
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
