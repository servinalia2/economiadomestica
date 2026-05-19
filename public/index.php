<?php
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}
require __DIR__ . '/../src/bootstrap.php';

$route = $_GET['route'] ?? 'dashboard';

if ($route === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['username'] === config('auth.username') && $_POST['password'] === config('auth.password')) {
            $_SESSION['logged_in'] = true;
            redirect('dashboard');
        }
        flash('danger', 'Usuario o contraseña incorrectos.');
    }
    render('login');
    exit;
}

if ($route === 'logout') {
    session_destroy();
    redirect('login');
}

require_login();
$pdo = db();
$movementService = new App\Services\MovementService($pdo);

function categories(): array { return db()->query('SELECT * FROM categorias ORDER BY nombre')->fetchAll(); }
function subcategories(): array { return db()->query('SELECT s.*, c.nombre categoria FROM subcategorias s JOIN categorias c ON c.id = s.categoria_id ORDER BY c.nombre, s.nombre')->fetchAll(); }
function accounts(): array { return db()->query('SELECT * FROM cuentas WHERE activa = 1 ORDER BY nombre')->fetchAll(); }
function selectedAccountName(): string { return $_POST['cuenta_bancaria'] ?? $_GET['cuenta_bancaria'] ?? 'Cuenta principal'; }

try {
    match ($route) {
        'dashboard' => dashboard($pdo),
        'categories' => categoriesPage($pdo),
        'category_save' => categorySave($pdo),
        'category_delete' => deleteById($pdo, 'categorias', 'categories'),
        'subcategory_save' => subcategorySave($pdo),
        'subcategory_delete' => deleteById($pdo, 'subcategorias', 'categories'),
        'movements' => movementsPage($pdo),
        'movement_form' => movementForm($pdo),
        'movement_save' => movementSave($movementService),
        'movement_delete' => deleteById($pdo, 'movimientos', 'movements'),
        'pending' => pendingPage($pdo),
        'pending_update' => pendingUpdate($pdo),
        'rules' => rulesPage($pdo),
        'rule_save' => ruleSave($pdo),
        'rule_delete' => deleteById($pdo, 'reglas_clasificacion', 'rules'),
        'import' => importPage($pdo, $movementService),
        default => dashboard($pdo),
    };
} catch (Throwable $e) {
    flash('danger', $e->getMessage());
    render('error');
}

function dashboard(PDO $pdo): void
{
    $month = (int) ($_GET['month'] ?? date('m'));
    $year = (int) ($_GET['year'] ?? date('Y'));
    $account = $_GET['cuenta_bancaria'] ?? '';
    $type = $_GET['tipo'] ?? 'todos';
    $where = ['MONTH(fecha) = :month', 'YEAR(fecha) = :year'];
    $params = ['month' => $month, 'year' => $year];
    if ($account !== '') { $where[] = 'cuenta_bancaria = :account'; $params['account'] = $account; }
    if (in_array($type, ['ingreso', 'gasto'], true)) { $where[] = 'tipo = :type'; $params['type'] = $type; }
    $sqlWhere = implode(' AND ', $where);
    $summary = $pdo->prepare("SELECT tipo, SUM(importe) total FROM movimientos WHERE $sqlWhere GROUP BY tipo");
    $summary->execute($params);
    $totals = ['ingreso' => 0.0, 'gasto' => 0.0];
    foreach ($summary as $row) { $totals[$row['tipo']] = (float) $row['total']; }
    $byCategory = $pdo->prepare("SELECT m.tipo, COALESCE(c.nombre, 'Sin clasificar') categoria, SUM(m.importe) total FROM movimientos m LEFT JOIN categorias c ON c.id = m.categoria_id WHERE $sqlWhere GROUP BY m.tipo, c.nombre ORDER BY ABS(total) DESC");
    $byCategory->execute($params);
    $latest = $pdo->query('SELECT m.*, c.nombre categoria, s.nombre subcategoria FROM movimientos m LEFT JOIN categorias c ON c.id=m.categoria_id LEFT JOIN subcategorias s ON s.id=m.subcategoria_id ORDER BY fecha DESC, id DESC LIMIT 8')->fetchAll();
    $pending = (int) $pdo->query('SELECT COUNT(*) FROM movimientos WHERE pendiente_revision = 1')->fetchColumn();
    render('dashboard', compact('month', 'year', 'account', 'type', 'totals', 'byCategory', 'latest', 'pending'));
}

function categoriesPage(PDO $pdo): void
{
    render('categories', ['categories' => categories(), 'subcategories' => subcategories()]);
}

function categorySave(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $data = [trim($_POST['nombre'] ?? ''), $_POST['tipo_default'] ?? 'ambos'];
    if ($data[0] === '') { throw new RuntimeException('El nombre de la categoría es obligatorio.'); }
    if ($id) {
        $stmt = $pdo->prepare('UPDATE categorias SET nombre=?, tipo_default=? WHERE id=?'); $stmt->execute([...$data, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO categorias (nombre, tipo_default) VALUES (?, ?)'); $stmt->execute($data);
    }
    flash('success', 'Categoría guardada.'); redirect('categories');
}

function subcategorySave(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $data = [(int) $_POST['categoria_id'], trim($_POST['nombre'] ?? '')];
    if ($data[1] === '') { throw new RuntimeException('El nombre de la subcategoría es obligatorio.'); }
    if ($id) {
        $stmt = $pdo->prepare('UPDATE subcategorias SET categoria_id=?, nombre=? WHERE id=?'); $stmt->execute([...$data, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO subcategorias (categoria_id, nombre) VALUES (?, ?)'); $stmt->execute($data);
    }
    flash('success', 'Subcategoría guardada.'); redirect('categories');
}

function movementsPage(PDO $pdo): void
{
    $where = ['1=1']; $params = [];
    if (($q = trim($_GET['q'] ?? '')) !== '') { $where[] = 'm.concepto_banco LIKE :q'; $params['q'] = "%$q%"; }
    foreach (['categoria_id', 'subcategoria_id'] as $field) { if (!empty($_GET[$field])) { $where[] = "m.$field = :$field"; $params[$field] = $_GET[$field]; } }
    if (!empty($_GET['desde'])) { $where[] = 'm.fecha >= :desde'; $params['desde'] = $_GET['desde']; }
    if (!empty($_GET['hasta'])) { $where[] = 'm.fecha <= :hasta'; $params['hasta'] = $_GET['hasta']; }
    if (!empty($_GET['pendiente'])) { $where[] = 'm.pendiente_revision = 1'; }
    $stmt = $pdo->prepare('SELECT m.*, c.nombre categoria, s.nombre subcategoria FROM movimientos m LEFT JOIN categorias c ON c.id=m.categoria_id LEFT JOIN subcategorias s ON s.id=m.subcategoria_id WHERE ' . implode(' AND ', $where) . ' ORDER BY m.fecha DESC, m.id DESC LIMIT 200');
    $stmt->execute($params);
    render('movements', ['movements' => $stmt->fetchAll(), 'categories' => categories(), 'subcategories' => subcategories()]);
}

function movementForm(PDO $pdo): void
{
    $movement = null;
    if (!empty($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM movimientos WHERE id=?'); $stmt->execute([$_GET['id']]); $movement = $stmt->fetch();
    }
    render('movement_form', ['movement' => $movement, 'categories' => categories(), 'subcategories' => subcategories(), 'accounts' => accounts()]);
}

function movementSave(App\Services\MovementService $service): void
{
    $id = (int) ($_POST['id'] ?? 0);
    foreach (['fecha', 'concepto_banco', 'importe', 'tipo'] as $field) { if (($_POST[$field] ?? '') === '') { throw new RuntimeException('Falta el campo ' . $field); } }
    if ($id) { $service->update($id, $_POST); flash('success', 'Movimiento actualizado.'); }
    else { $service->save($_POST + ['origen' => 'manual']); flash('success', 'Movimiento creado.'); }
    redirect('movements');
}

function pendingPage(PDO $pdo): void
{
    $rows = $pdo->query('SELECT m.*, c.nombre categoria, s.nombre subcategoria FROM movimientos m LEFT JOIN categorias c ON c.id=m.categoria_id LEFT JOIN subcategorias s ON s.id=m.subcategoria_id WHERE m.pendiente_revision = 1 ORDER BY m.fecha DESC, m.id DESC')->fetchAll();
    render('pending', ['movements' => $rows, 'categories' => categories(), 'subcategories' => subcategories()]);
}

function pendingUpdate(PDO $pdo): void
{
    $stmt = $pdo->prepare('UPDATE movimientos SET categoria_id=?, subcategoria_id=?, pendiente_revision=0 WHERE id=?');
    $stmt->execute([(int) $_POST['categoria_id'], (int) $_POST['subcategoria_id'], (int) $_POST['id']]);
    if (!empty($_POST['crear_regla']) && trim($_POST['palabra_clave'] ?? '') !== '') {
        $rule = $pdo->prepare('INSERT INTO reglas_clasificacion (palabra_clave, categoria_id, subcategoria_id, tipo, prioridad, activa) VALUES (?, ?, ?, ?, 10, 1)');
        $rule->execute([trim($_POST['palabra_clave']), (int) $_POST['categoria_id'], (int) $_POST['subcategoria_id'], $_POST['tipo']]);
    }
    flash('success', 'Movimiento revisado.'); redirect('pending');
}

function rulesPage(PDO $pdo): void
{
    $rules = $pdo->query('SELECT r.*, c.nombre categoria, s.nombre subcategoria FROM reglas_clasificacion r JOIN categorias c ON c.id=r.categoria_id JOIN subcategorias s ON s.id=r.subcategoria_id ORDER BY r.activa DESC, r.prioridad DESC, r.palabra_clave')->fetchAll();
    render('rules', ['rules' => $rules, 'categories' => categories(), 'subcategories' => subcategories()]);
}

function ruleSave(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $data = [trim($_POST['palabra_clave'] ?? ''), (int) $_POST['categoria_id'], (int) $_POST['subcategoria_id'], $_POST['tipo'], (int) ($_POST['prioridad'] ?? 10), isset($_POST['activa']) ? 1 : 0];
    if ($data[0] === '') { throw new RuntimeException('La palabra clave es obligatoria.'); }
    if ($id) { $stmt = $pdo->prepare('UPDATE reglas_clasificacion SET palabra_clave=?, categoria_id=?, subcategoria_id=?, tipo=?, prioridad=?, activa=? WHERE id=?'); $stmt->execute([...$data, $id]); }
    else { $stmt = $pdo->prepare('INSERT INTO reglas_clasificacion (palabra_clave, categoria_id, subcategoria_id, tipo, prioridad, activa) VALUES (?, ?, ?, ?, ?, ?)'); $stmt->execute($data); }
    flash('success', 'Regla guardada.'); redirect('rules');
}

function importPage(PDO $pdo, App\Services\MovementService $movementService): void
{
    $preview = null; $stats = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_FILES['bank_file']['tmp_name'])) { throw new RuntimeException('Selecciona un archivo.'); }
        $target = __DIR__ . '/../storage/uploads/' . uniqid('import_', true) . '_' . basename($_FILES['bank_file']['name']);
        move_uploaded_file($_FILES['bank_file']['tmp_name'], $target);
        $service = new App\Services\ImportService($pdo, $movementService);
        if (isset($_POST['preview'])) { $preview = $service->preview($target); }
        else { $stats = $service->import($target, selectedAccountName()); }
    }
    render('import', ['preview' => $preview, 'stats' => $stats, 'accounts' => accounts()]);
}

function deleteById(PDO $pdo, string $table, string $redirect): void
{
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?"); $stmt->execute([(int) ($_POST['id'] ?? $_GET['id'] ?? 0)]);
    flash('success', 'Registro eliminado.'); redirect($redirect);
}
