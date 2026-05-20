<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(config('app_name')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="?route=dashboard">Economía Doméstica</a>
    <?php if (is_logged_in()): ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="?route=dashboard">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="?route=movements">Movimientos</a></li>
        <li class="nav-item"><a class="nav-link" href="?route=import">Importar</a></li>
        <li class="nav-item"><a class="nav-link" href="?route=pending">Pendientes</a></li>
        <li class="nav-item"><a class="nav-link" href="?route=categories">Categorías</a></li>
        <li class="nav-item"><a class="nav-link" href="?route=rules">Reglas</a></li>
      </ul>
      <a class="btn btn-outline-light btn-sm" href="?route=logout">Salir</a>
    </div>
    <?php endif; ?>
  </div>
</nav>
<main class="container-fluid container-lg">
<?php foreach (flashes() as $flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
