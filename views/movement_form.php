<h1 class="h3 mb-3"><?= $movement ? 'Editar movimiento' : 'Añadir movimiento manual' ?></h1>
<form method="post" action="?route=movement_save" class="card" data-dependent-scope>
  <div class="card-body row g-3">
    <input type="hidden" name="id" value="<?= e($movement['id'] ?? '') ?>">
    <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" name="fecha" value="<?= e($movement['fecha'] ?? date('Y-m-d')) ?>" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Concepto</label><input name="concepto_banco" value="<?= e($movement['concepto_banco'] ?? '') ?>" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Importe</label><input type="number" step="0.01" name="importe" value="<?= e($movement['importe'] ?? '') ?>" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo" class="form-select"><option value="gasto" <?= ($movement['tipo'] ?? '')==='gasto'?'selected':'' ?>>Gasto</option><option value="ingreso" <?= ($movement['tipo'] ?? '')==='ingreso'?'selected':'' ?>>Ingreso</option></select></div>
    <div class="col-md-3"><label class="form-label">Categoría</label><select name="categoria_id" class="form-select" data-category-select><option value="">Sin clasificar</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($movement['categoria_id'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Subcategoría</label><select name="subcategoria_id" class="form-select" data-subcategory-select data-subcategories-source="movement-subcategories-data" data-placeholder="Sin clasificar"><option value="">Sin clasificar</option><?php foreach ($subcategories as $s): ?><option value="<?= $s['id'] ?>" data-categoria-id="<?= $s['categoria_id'] ?>" <?= ($movement['subcategoria_id'] ?? '')==$s['id']?'selected':'' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Cuenta</label><input name="cuenta_bancaria" value="<?= e($movement['cuenta_bancaria'] ?? 'Cuenta principal') ?>" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Saldo</label><input type="number" step="0.01" name="saldo" value="<?= e($movement['saldo'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-9"><label class="form-label">Observaciones / más datos</label><input name="mas_datos" value="<?= e($movement['mas_datos'] ?? '') ?>" class="form-control"></div>
    <div class="col-12"><button class="btn btn-primary">Guardar</button><a href="?route=movements" class="btn btn-link">Cancelar</a></div>
  </div>
</form>
<script type="application/json" id="movement-subcategories-data"><?= json_encode(array_map(fn ($s) => ['id' => (int) $s['id'], 'nombre' => $s['nombre'], 'categoria_id' => (int) $s['categoria_id']], $subcategories), JSON_UNESCAPED_UNICODE) ?></script>
