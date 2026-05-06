<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'DIMZSTORE') ?></title>

    <!-- CSRF meta untuk dipakai JS saat POST -->
    <meta name="csrf-name"  content="<?= esc(csrf_token()) ?>">
    <meta name="csrf-token" content="<?= esc(csrf_hash()) ?>">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/style.css?v=999">
</head>
<body class="bg-background">
    <?= $this->renderSection('content') ?>

    <script>
        // CSRF helper untuk semua POST request CI4.
        window.CSRF = {
            name:  document.querySelector('meta[name="csrf-name"]').content,
            token: document.querySelector('meta[name="csrf-token"]').content,
        };
        // Bantu fetch agar otomatis kirim CSRF token.
        window.csrfFormData = function (formData) {
            formData.append(window.CSRF.name, window.CSRF.token);
            return formData;
        };
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
