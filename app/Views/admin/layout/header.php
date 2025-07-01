<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'SI-AKADEMIK Admin') ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; }
        .navbar { background-color: #333; overflow: hidden; }
        .navbar a { float: left; display: block; color: white; text-align: center; padding: 14px 16px; text-decoration: none; }
        .navbar a:hover { background-color: #ddd; color: black; }
        .container { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px;}
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-links a { margin-right: 10px; text-decoration: none; color: #007bff; }
        .action-links a.delete { color: red; }
        .add-button { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;}
        .add-button:hover { background-color: #218838; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="date"], select, textarea {
            width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;
        }
        textarea { resize: vertical; }
        .form-errors { color: red; margin-top: 5px; font-size: 0.9em; padding:10px; border: 1px solid red; background-color: #ffeeee; border-radius:4px; }
        .form-errors ul { padding-left: 20px; margin:0; }
        .button-group { margin-top: 20px; }
        .button-group button, .button-group a { padding: 10px 15px; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 1em;}
        .button-group button[type="submit"] { background-color: #007bff; color: white; }
        .button-group button[type="submit"]:hover { background-color: #0056b3; }
        .button-group a { background-color: #6c757d; color: white; margin-left:10px;}
        .button-group a:hover { background-color: #5a6268; }
        h1 { color: #333; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="<?= site_url('admin/students') ?>">Students</a>
    <a href="<?= site_url('admin/teachers') ?>">Teachers</a>
    <a href="<?= site_url('admin/subjects') ?>">Subjects</a>
    <a href="<?= site_url('admin/classes') ?>">Classes</a>
    <!-- Add more links as modules are developed -->
</div>

<div class="container">
    <?= $this->renderSection('content') ?>
</div>

</body>
</html>
