<h1 class="h3 mb-3">Dashboard mensual</h1>
<form class="row g-2 mb-4">
  <input type="hidden" name="route" value="dashboard">
  <div class="col-6 col-md-2"><label class="form-label">Mes</label><input type="number" min="1" max="12" name="month" value="<?= e((string) $month) ?>" class="form-control"></div>
  <div class="col-6 col-md-2"><label class="form-label">Año</label><input type="number" name="year" value="<?= e((string) $year) ?>" class="form-control"></div>
  <div class="col-md-3"><label class="form-label">Cuenta</label><input name="cuenta_bancaria" value="<?= e($account) ?>" class="form-control" placeholder="Todas"></div>
  <div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo" class="form-select"><option value="todos">Todos</option><option value="ingreso" <?= $type==='ingreso'?'selected':'' ?>>Ingresos</option><option value="gasto" <?= $type==='gasto'?'selected':'' ?>>Gastos</option></select></div>
  <div class="col-md-2 align-self-end"><button class="btn btn-primary w-100">Filtrar</button></div>
</form>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card card-stat"><div class="card-body"><h2 class="h6 text-muted">Ingresos</h2><p class="display-6 amount-income"><?= money($totals['ingreso']) ?></p></div></div></div>
  <div class="col-md-4"><div class="card card-stat"><div class="card-body"><h2 class="h6 text-muted">Gastos</h2><p class="display-6 amount-expense"><?= money(abs($totals['gasto'])) ?></p></div></div></div>
  <div class="col-md-4"><div class="card card-stat"><div class="card-body"><h2 class="h6 text-muted">Ahorro / balance</h2><p class="display-6"><?= money($totals['ingreso'] + $totals['gasto']) ?></p><a href="?route=pending" class="small">Pendientes: <?= $pending ?></a></div></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h5">Resumen por categoría</h2><table class="table table-sm"><tbody><?php foreach ($byCategory as $row): ?><tr><td><?= e($row['categoria']) ?></td><td><?= e($row['tipo']) ?></td><td class="text-end"><?= money($row['total']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
  <div class="col-lg-7"><div class="card"><div class="card-body"><h2 class="h5">Últimos movimientos</h2><table class="table table-sm"><thead><tr><th>Fecha</th><th>Concepto</th><th>Categoría</th><th class="text-end">Importe</th></tr></thead><tbody><?php foreach ($latest as $m): ?><tr><td><?= e($m['fecha']) ?></td><td><?= e($m['concepto_banco']) ?></td><td><?= e(($m['categoria'] ?? '-') . ' / ' . ($m['subcategoria'] ?? '-')) ?></td><td class="text-end <?= $m['tipo']==='ingreso'?'amount-income':'amount-expense' ?>"><?= money($m['importe']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
