<?= $this->extend('layouts/admin_default') // Menggunakan layout admin agar tampilan konsisten jika sudah login ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="text-center" style="margin-top: 50px;">
        <div class="error mx-auto" data-text="403">403</div>
        <p class="lead text-gray-800 mb-5">Access Forbidden</p>
        <p class="text-gray-500 mb-0">You do not have permission to view this page.</p>
        <p class="text-gray-500 mb-0">
            <?php if (session()->getFlashdata('error')) : ?>
                <?= session()->getFlashdata('error') ?>
            <?php endif; ?>
        </p>
        <a href="<?= site_url('/') ?>">&larr; Back to Dashboard or Homepage</a>
    </div>

    <style>
        .error {
            color: #5a5c69;
            font-size: 7rem;
            position: relative;
            line-height: 1;
            width: 12.5rem;
        }
        .error:after {
            content: attr(data-text);
            position: absolute;
            left: 2px;
            text-shadow: -1px 0 #e74a3b;
            top: 0;
            color: #5a5c69;
            background: #f8f9fc;
            overflow: hidden;
            clip: rect(0,900px,0,0);
            animation: noise-anim-2 .5s linear infinite alternate-reverse,glitch 1.5s linear infinite alternate-reverse;
        }

        @keyframes glitch {
            2%,64% { transform: translate(2px,0) skew(0deg); }
            4%,60% { transform: translate(-2px,0) skew(0deg); }
            62% { transform: translate(0,0) skew(5deg); }
        }
        @keyframes noise-anim-2 {
            0% { clip-path: inset(3% 0 94% 0); }
            20% { clip-path: inset(60% 0 3% 0); }
            40% { clip-path: inset(41% 0 45% 0); }
            60% { clip-path: inset(80% 0 8% 0); }
            80% { clip-path: inset(54% 0 33% 0); }
            100% { clip-path: inset(5% 0 77% 0); }
        }

    </style>

</div>

<?= $this->endSection() ?>
