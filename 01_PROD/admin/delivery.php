<?php
/**
 * Livraison : zones (frais, seuil de gratuité, délai) et devises (taux, symbole, décimales, devise par défaut).
 * L'édition des devises est réservée au module « settings » (super administrateur).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('delivery');
$canCurrencies = AdminAuth::can('settings');

$action = (string)query_param('action');
$id = int_param('id');
$zone = $id ? DB::one('SELECT * FROM delivery_zones WHERE id = :id', ['id' => $id]) : null;
if ($id && !$zone) {
    flash('error', 'Zone introuvable.');
    redirect(admin_url('delivery.php'));
}
$errors = [];
$form = $zone ?: ['name' => '', 'fee' => '', 'free_threshold' => '', 'delay' => '', 'active' => 1, 'sort' => 0];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');

    // ----- Devises
    if ($do === 'currencies' || $do === 'currency_add') {
        if (!$canCurrencies) {
            flash('error', 'Seul le super administrateur peut modifier les devises.');
            redirect(admin_url('delivery.php'));
        }
        if ($do === 'currency_add') {
            $code = strtoupper(trim((string)post('code')));
            $name = (string)post('name');
            $symbol = (string)post('symbol');
            $rate = admin_dec(post('rate'));
            if (!preg_match('/^[A-Z]{3}$/', $code)) flash('error', 'Le code devise doit comporter 3 lettres (ISO 4217).');
            elseif (DB::val('SELECT code FROM currencies WHERE code = :c', ['c' => $code])) flash('error', 'Cette devise existe déjà.');
            elseif ($name === '' || $symbol === '' || !$rate || $rate <= 0) flash('error', 'Nom, symbole et taux (positif) sont obligatoires.');
            else {
                DB::insert('currencies', ['code' => $code, 'name' => $name, 'symbol' => $symbol, 'rate' => $rate, 'decimals' => max(0, min(4, (int)post('decimals'))), 'symbol_after' => post_bool('symbol_after'), 'is_default' => 0, 'active' => 0]);
                AdminAuth::log('currency_create', 'currency', null, $code);
                flash('success', 'Devise ' . $code . ' ajoutée (inactive).');
            }
            redirect(admin_url('delivery.php') . '#devises');
        }
        $rows = (array)($_POST['cur'] ?? []);
        $default = (string)post('default_currency');
        $existing = DB::col('SELECT code FROM currencies');
        $errs = [];
        if (!in_array($default, $existing, true)) $errs[] = 'Choisissez une devise par défaut.';
        elseif (empty($rows[$default]['active'])) $errs[] = 'La devise par défaut doit être active.';
        foreach ($existing as $code) {
            $r = $rows[$code] ?? null;
            if (!$r) continue;
            $rate = admin_dec($r['rate'] ?? '');
            if ($rate === null || $rate <= 0) $errs[] = $code . ' : taux invalide.';
            if (trim((string)($r['symbol'] ?? '')) === '' || trim((string)($r['name'] ?? '')) === '') $errs[] = $code . ' : nom et symbole obligatoires.';
        }
        if ($errs) {
            foreach ($errs as $er) flash('error', $er);
        } else {
            DB::begin();
            try {
                foreach ($existing as $code) {
                    $r = $rows[$code] ?? null;
                    if (!$r) continue;
                    DB::update('currencies', [
                        'name' => mb_substr(trim((string)$r['name']), 0, 50),
                        'symbol' => mb_substr(trim((string)$r['symbol']), 0, 10),
                        'rate' => $code === $default ? 1 : admin_dec($r['rate']), // la devise de base vaut toujours 1
                        'decimals' => max(0, min(4, (int)($r['decimals'] ?? 0))),
                        'symbol_after' => !empty($r['symbol_after']) ? 1 : 0,
                        'active' => !empty($r['active']) ? 1 : 0,
                        'is_default' => $code === $default ? 1 : 0,
                    ], 'code = :c', ['c' => $code]);
                }
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
            AdminAuth::log('currencies_update', 'currency', null, ['default' => $default]);
            flash('success', 'Devises enregistrées.');
        }
        redirect(admin_url('delivery.php') . '#devises');
    }

    // ----- Zones
    if ($do === 'delete' && $zone) {
        DB::exec('DELETE FROM delivery_zones WHERE id = :id', ['id' => $id]);
        DB::exec('UPDATE addresses SET zone_id = NULL WHERE zone_id = :id', ['id' => $id]);
        AdminAuth::log('zone_delete', 'delivery_zone', $id, $zone['name']);
        flash('success', 'Zone supprimée.');
        redirect(admin_url('delivery.php'));
    }
    $in = [
        'name' => (string)post('name'),
        'fee' => admin_dec(post('fee')),
        'free_threshold' => admin_dec(post('free_threshold')),
        'delay' => (string)post('delay') ?: null,
        'active' => post_bool('active'),
        'sort' => (int)post('sort'),
    ];
    if ($in['name'] === '') $errors[] = 'Le nom de la zone est obligatoire.';
    if ($in['fee'] === null || $in['fee'] < 0) $errors[] = 'Les frais doivent être un nombre positif (0 pour gratuit).';
    if (post('free_threshold') !== '' && ($in['free_threshold'] === null || $in['free_threshold'] < 0)) $errors[] = 'Seuil de gratuité invalide.';
    if ($errors) {
        $form = array_merge($form, $in, ['fee' => post('fee'), 'free_threshold' => post('free_threshold')]);
        $action = $zone ? 'edit' : 'new';
    } else {
        if ($zone) {
            DB::update('delivery_zones', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('delivery_zones', $in);
        }
        AdminAuth::log($zone ? 'zone_update' : 'zone_create', 'delivery_zone', $id, $in['name']);
        flash('success', 'Zone enregistrée.');
        redirect(admin_url('delivery.php'));
    }
}

$zones = DB::all('SELECT * FROM delivery_zones ORDER BY sort, name');
$currencies = DB::all('SELECT * FROM currencies ORDER BY is_default DESC, code');
$globalFree = setting('free_shipping_threshold');
$showForm = in_array($action, ['new', 'edit'], true) || $errors;

$pageTitle = 'Livraison & devises';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('delivery.php')) . '">← Retour</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('delivery.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouvelle zone</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" class="card" action="<?= e(admin_url('delivery.php', ['id' => $zone['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <h2><?= $zone ? 'Modifier la zone' : 'Nouvelle zone de livraison' ?></h2>
    <div class="form-grid">
      <?= field_input('name', 'Nom de la zone', $form['name'], ['required' => true, 'attrs' => ['maxlength' => 120, 'placeholder' => 'ex. Dakar Plateau']]) ?>
      <?= field_input('fee', 'Frais de livraison (' . base_currency() . ')', $form['fee'], ['required' => true, 'attrs' => ['inputmode' => 'decimal']]) ?>
      <?= field_input('free_threshold', 'Livraison offerte à partir de', $form['free_threshold'], ['attrs' => ['inputmode' => 'decimal'], 'help' => 'Vide = seuil global' . ($globalFree !== '' ? ' (' . admin_money($globalFree) . ')' : '') . '.']) ?>
      <?= field_input('delay', 'Délai indicatif', $form['delay'], ['attrs' => ['maxlength' => 80, 'placeholder' => 'ex. 24 à 48 h']]) ?>
      <?= field_input('sort', 'Ordre', $form['sort'], ['type' => 'number']) ?>
      <?= field_checkbox('active', 'Active (proposée au paiement)', (bool)$form['active']) ?>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('delivery.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <div class="card flush">
    <div class="card-head"><h2>Zones de livraison</h2><span class="muted small">Seuil global de livraison offerte : <?= $globalFree !== '' ? e(admin_money($globalFree)) : 'aucun' ?></span></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Zone</th><th class="num">Frais</th><th class="num">Offerte dès</th><th>Délai</th><th class="num">Ordre</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($zones as $z): ?>
          <tr class="<?= $z['active'] ? '' : 'is-muted' ?>">
            <td><a href="<?= e(admin_url('delivery.php', ['action' => 'edit', 'id' => $z['id']])) ?>"><strong><?= e($z['name']) ?></strong></a></td>
            <td class="num"><?= (float)$z['fee'] > 0 ? e(admin_money($z['fee'])) : badge('Gratuit', 'success') ?></td>
            <td class="num"><?= $z['free_threshold'] !== null ? e(admin_money($z['free_threshold'])) : '<span class="muted">seuil global</span>' ?></td>
            <td><?= e($z['delay'] ?? '') ?></td>
            <td class="num"><?= (int)$z['sort'] ?></td>
            <td><?= bool_badge($z['active']) ?></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(admin_url('delivery.php', ['action' => 'edit', 'id' => $z['id']])) ?>"><?= aicon('edit') ?></a>
              <form method="post" class="inline" action="<?= e(admin_url('delivery.php', ['id' => $z['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer la zone « <?= e($z['name']) ?> » ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$zones): ?><tr><td colspan="7" class="table-empty">Aucune zone de livraison.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card flush" id="devises">
    <div class="card-head"><h2>Devises</h2><span class="muted small">Taux : 1 unité de la devise par défaut = « taux » unités de la devise.</span></div>
    <?php if (!$canCurrencies): ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Code</th><th>Nom</th><th>Symbole</th><th class="num">Taux</th><th>Statut</th></tr></thead>
        <tbody><?php foreach ($currencies as $c): ?>
          <tr><td><code><?= e($c['code']) ?></code><?= $c['is_default'] ? ' ' . badge('Par défaut', 'primary') : '' ?></td><td><?= e($c['name']) ?></td><td><?= e($c['symbol']) ?></td><td class="num"><?= e(rtrim(rtrim((string)$c['rate'], '0'), '.')) ?></td><td><?= bool_badge($c['active']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <p class="muted small" style="padding:0 18px 14px">La modification des devises est réservée au super administrateur.</p>
    <?php else: ?>
      <form method="post" action="<?= e(admin_url('delivery.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="currencies">
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Par défaut</th><th>Code</th><th>Nom</th><th>Symbole</th><th>Taux</th><th>Décimales</th><th>Symbole après</th><th>Active</th></tr></thead>
            <tbody>
            <?php foreach ($currencies as $c): $k = e($c['code']); ?>
              <tr>
                <td><input type="radio" name="default_currency" value="<?= $k ?>"<?= $c['is_default'] ? ' checked' : '' ?> aria-label="Devise par défaut"></td>
                <td><code><strong><?= $k ?></strong></code></td>
                <td><input type="text" name="cur[<?= $k ?>][name]" value="<?= e($c['name']) ?>" maxlength="50" style="min-width:120px"></td>
                <td><input type="text" name="cur[<?= $k ?>][symbol]" value="<?= e($c['symbol']) ?>" maxlength="10" style="width:80px"></td>
                <td><input type="text" name="cur[<?= $k ?>][rate]" value="<?= e(rtrim(rtrim((string)$c['rate'], '0'), '.')) ?>" inputmode="decimal" style="width:120px"<?= $c['is_default'] ? ' readonly title="La devise par défaut a toujours un taux de 1"' : '' ?>></td>
                <td><input type="number" name="cur[<?= $k ?>][decimals]" value="<?= (int)$c['decimals'] ?>" min="0" max="4" style="width:70px"></td>
                <td><input type="checkbox" name="cur[<?= $k ?>][symbol_after]" value="1"<?= $c['symbol_after'] ? ' checked' : '' ?> aria-label="Symbole après le montant"></td>
                <td><input type="checkbox" name="cur[<?= $k ?>][active]" value="1"<?= $c['active'] ? ' checked' : '' ?> aria-label="Active"></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="bulk-bar">
          <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer les devises</button>
          <span class="muted small">Attention : changer la devise par défaut ne convertit pas les prix existants.</span>
        </div>
      </form>
      <form method="post" class="bulk-bar" action="<?= e(admin_url('delivery.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="currency_add">
        <strong>Ajouter :</strong>
        <input type="text" name="code" placeholder="Code (GBP)" maxlength="3" style="width:110px" required aria-label="Code">
        <input type="text" name="name" placeholder="Nom" maxlength="50" style="width:150px" required aria-label="Nom">
        <input type="text" name="symbol" placeholder="Symbole" maxlength="10" style="width:90px" required aria-label="Symbole">
        <input type="text" name="rate" placeholder="Taux" inputmode="decimal" style="width:110px" required aria-label="Taux">
        <input type="number" name="decimals" value="2" min="0" max="4" style="width:70px" aria-label="Décimales">
        <label class="check small"><input type="checkbox" name="symbol_after" value="1"> Symbole après</label>
        <button class="btn" type="submit"><?= aicon('plus') ?> Ajouter</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
