<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'SI-AKADEMIK') ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            padding-top: 4.5rem; /* Adjust based on navbar height */
        }
        .footer {
            background-color: #f8f9fa;
            padding: 1rem 0;
            font-size: 0.9rem;
            text-align: center;
        }
        /* Custom styles for adminLTE-like feel if desired later, or general improvements */
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
    </style>
</head>
<body>
    <?php $session = session(); ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= site_url('/') ?>">SI-AKADEMIK</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($session->get('is_logged_in')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (uri_string() == 'admin/students') ? 'active' : '' ?>" href="<?= site_url('admin/students') ?>">Students</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (uri_string() == 'admin/teachers') ? 'active' : '' ?>" href="<?= site_url('admin/teachers') ?>">Teachers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (uri_string() == 'admin/subjects') ? 'active' : '' ?>" href="<?= site_url('admin/subjects') ?>">Subjects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (uri_string() == 'admin/classes') ? 'active' : '' ?>" href="<?= site_url('admin/classes') ?>">Classes</a>
                        </li>
                        <?php
                            $isAdmin = in_array($session->get('role_id'), [1]); // Assuming role_id 1 is Administrator
                        ?>
                        <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (strpos(uri_string(), 'admin/users') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/users') ?>">User Management</a>
                        </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($session->get('is_logged_in')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?= esc($session->get('full_name') ?? $session->get('username')) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                                <!-- <li><a class="dropdown-item" href="#">Profile</a></li> -->
                                <!-- <li><hr class="dropdown-divider"></li> -->
                                <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (uri_string() == 'login') ? 'active' : '' ?>" href="<?= site_url('login') ?>">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content container mt-4">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="footer mt-auto">
        <div class="container">
            <span class="text-muted">&copy; <?= date('Y') ?> SMAN 1 Campurdarat. SI-AKADEMIK.</span>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
