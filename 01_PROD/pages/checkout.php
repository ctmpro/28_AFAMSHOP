<?php
/**
 * Tunnel de commande : informations client, livraison, paiement.
 * Commande possible sans compte, avec option de création de compte.
 */
if (!Cart::count()) {
    redirect('panier');
}
$customer = Auth::user();
$addresses = $customer ? DB::all('SELECT * FROM addresses WHERE customer_id = :c ORDER BY is_default DESC, id', ['c' => $customer['id']]) : [];
$zones = Cart::zones();
$pickupEnabled = setting_bool('pickup_enabled', true);

$methods = [];
if (StripeGateway::enabled()) $methods['stripe'] = __('pm_stripe');
if (PayDunyaGateway::enabled()) $methods['paydunya'] = __('pm_paydunya');
if (setting_bool('payment_cod_enabled', true)) $methods['cod'] = __('pm_cod');
if (setting_bool('payment_transfer_enabled')) $methods['transfer'] = __('pm_transfer');

$errors = [];
$defaultAddr = $addresses[0] ?? null;
$form = [
    'first_name' => $customer['first_name'] ?? '',
    'last_name' => $customer['last_name'] ?? '',
    'company' => $customer['company'] ?? '',
    'email' => $customer['email'] ?? '',
    'phone' => $customer['phone'] ?? ($defaultAddr['phone'] ?? ''),
    'address' => $defaultAddr['address'] ?? '',
    'city' => $defaultAddr['city'] ?? '',
    'zone_id' => $defaultAddr['zone_id'] ?? ($zones[0]['id'] ?? ''),
    'delivery_method' => 'delivery',
    'payment_method' => array_key_first($methods) ?? '',
    'notes' => '',
];

if (is_post()) {
    require_csrf();
    foreach ($form as $k => $v) {
        $form[$k] = mb_substr(trim((string)post($k)), 0, $k === 'notes' ? 2000 : 255);
    }
    $form['email'] = strtolower($form['email']);
    if (!$pickupEnabled) $form['delivery_method'] = 'delivery';

    $required = ['first_name' => __('first_name'), 'last_name' => __('last_name'), 'email' => __('email'), 'phone' => __('phone')];
    if ($form['delivery_method'] === 'delivery') {
        $required += ['address' => __('address'), 'city' => __('city'), 'zone_id' => __('delivery_zone')];
    }
    foreach ($required as $k => $label) {
        if ($form[$k] === '') $errors[] = __('field_required', ['field' => $label]);
    }
    if ($form['email'] !== '' && !valid_email($form['email'])) $errors[] = __('invalid_email');
    if (!isset($methods[$form['payment_method']])) $errors[] = __('field_required', ['field' => __('payment_method')]);
    if (!post('terms')) $errors[] = __('must_accept_terms');

    $createAccount = !$customer && post('create_account');
    if ($createAccount) {
        if (DB::val('SELECT id FROM customers WHERE email = :e', ['e' => $form['email']])) {
            $errors[] = __('email_taken');
        } elseif ($err = Auth::passwordError((string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''))) {
            $errors[] = $err;
        }
    }

    $totals = Cart::totals(['zone_id' => $form['zone_id'], 'delivery_method' => $form['delivery_method'], 'email' => $form['email']]);
    if ($totals['coupon_error']) {
        Cart::setCoupon(null);
        $errors[] = $totals['coupon_error'];
    }
    foreach ($totals['items'] as $it) {
        if (!$it['available']) $errors[] = $it['product']['name'] . ' : ' . __('qty_limited', ['n' => max(0, (int)$it['product']['stock'])]);
    }
    if (!$totals['items']) $errors[] = __('cart_empty');

    if (!$errors) {
        if ($createAccount) {
            $cid = DB::insert('customers', [
                'first_name' => $form['first_name'], 'last_name' => $form['last_name'], 'company' => $form['company'] ?: null,
                'account_type' => $form['company'] ? 'business' : 'individual',
                'email' => $form['email'], 'phone' => $form['phone'],
                'password_hash' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
            ]);
            Auth::login($cid);
            Mailer::sendTemplate('welcome', $form['email'], ['first_name' => $form['first_name']]);
        }
        // Enregistre l'adresse dans le compte si nouvelle
        if (Auth::id() && $form['delivery_method'] === 'delivery'
            && !DB::val('SELECT id FROM addresses WHERE customer_id = :c AND address = :a AND city = :v', ['c' => Auth::id(), 'a' => $form['address'], 'v' => $form['city']])) {
            DB::insert('addresses', [
                'customer_id' => Auth::id(), 'full_name' => $form['first_name'] . ' ' . $form['last_name'], 'phone' => $form['phone'],
                'address' => $form['address'], 'city' => $form['city'], 'zone_id' => $form['zone_id'] ?: null,
                'is_default' => DB::val('SELECT COUNT(*) FROM addresses WHERE customer_id = :c', ['c' => Auth::id()]) ? 0 : 1,
            ]);
        }
        try {
            $order = Orders::create($form, $totals, $form['payment_method']);
        } catch (Throwable $e) {
            error_log('checkout: ' . $e->getMessage());
            $errors[] = __('error_generic');
        }
        if (!$errors) {
            Cart::clear();
            $_SESSION['last_order'] = $order['order_number'];
            redirect(url('commande/payer', ['n' => $order['order_number'], 't' => $order['access_token']]));
        }
    }
}

$totals = Cart::totals(['zone_id' => $form['zone_id'], 'delivery_method' => $form['delivery_method'], 'email' => $form['email'] ?: null]);
// Libellés précalculés (frais / total) par zone pour la mise à jour instantanée côté navigateur
$base = $totals['subtotal'] - $totals['discount'];
$extraTax = $totals['tax_included'] ? 0 : $totals['tax'];
$zonesJs = ['pickup' => ['fee' => __('free'), 'total' => money($base + $extraTax)]];
foreach ($zones as $z) {
    $fee = Cart::deliveryFee($z, 'delivery', $base);
    $zonesJs[$z['id']] = ['fee' => $fee > 0 ? money($fee) : __('free'), 'total' => money($base + $fee + $extraTax)];
}

$pageTitle = __('checkout');
$noIndex = true;
$bodyClass = 'page-checkout';
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <h1><?= e(__('checkout')) ?></h1>
  <?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <?php if (!$customer): ?>
    <div class="alert alert-info"><?= e(__('have_account')) ?> <a href="<?= e(url('compte/connexion', ['retour' => url('commande')])) ?>"><?= e(__('login')) ?></a> — <?= e(__('or')) ?> <?= e(mb_strtolower(__('guest_checkout'))) ?> :</div>
  <?php endif; ?>

  <form method="post" class="checkout-layout" data-checkout
        data-zones="<?= e(json_encode($zonesJs, JSON_UNESCAPED_UNICODE)) ?>">
    <?= csrf_field() ?>
    <div class="checkout-main">
      <section class="card checkout-step">
        <h2><span class="step-num">1</span> <?= e(__('customer_info')) ?></h2>
        <div class="form-grid">
          <label><?= e(__('first_name')) ?> *<input type="text" name="first_name" value="<?= e($form['first_name']) ?>" required maxlength="100" autocomplete="given-name"></label>
          <label><?= e(__('last_name')) ?> *<input type="text" name="last_name" value="<?= e($form['last_name']) ?>" required maxlength="100" autocomplete="family-name"></label>
          <label><?= e(__('email')) ?> *<input type="email" name="email" value="<?= e($form['email']) ?>" required maxlength="190" autocomplete="email"></label>
          <label><?= e(__('phone')) ?> *<input type="tel" name="phone" value="<?= e($form['phone']) ?>" required maxlength="40" autocomplete="tel" placeholder="+221 7X XXX XX XX"></label>
          <label class="span-2"><?= e(__('company')) ?><input type="text" name="company" value="<?= e($form['company']) ?>" maxlength="190" autocomplete="organization"></label>
        </div>
        <?php if (!$customer): ?>
        <label class="check"><input type="checkbox" name="create_account" value="1" data-toggle-target="#account-fields" <?= post('create_account') ? 'checked' : '' ?>> <?= e(__('create_account_option')) ?></label>
        <div id="account-fields" class="form-grid" <?= post('create_account') ? '' : 'hidden' ?>>
          <label><?= e(__('password')) ?><input type="password" name="password" minlength="8" autocomplete="new-password"></label>
          <label><?= e(__('password_confirm')) ?><input type="password" name="password_confirm" minlength="8" autocomplete="new-password"></label>
          <small class="span-2 muted"><?= e(__('password_rules')) ?></small>
        </div>
        <?php endif; ?>
      </section>

      <section class="card checkout-step">
        <h2><span class="step-num">2</span> <?= e(__('delivery_method')) ?></h2>
        <div class="choice-list">
          <label class="choice"><input type="radio" name="delivery_method" value="delivery" <?= $form['delivery_method'] !== 'pickup' ? 'checked' : '' ?> data-delivery-method>
            <span><?= icon('truck') ?> <strong><?= e(__('home_delivery')) ?></strong></span></label>
          <?php if ($pickupEnabled): ?>
          <label class="choice"><input type="radio" name="delivery_method" value="pickup" <?= $form['delivery_method'] === 'pickup' ? 'checked' : '' ?> data-delivery-method>
            <span><?= icon('pin') ?> <strong><?= e(__('pickup')) ?></strong> <small><?= e(setting('pickup_address')) ?></small> — <?= e(__('free')) ?></span></label>
          <?php endif; ?>
        </div>
        <div class="delivery-fields" data-delivery-fields <?= $form['delivery_method'] === 'pickup' ? 'hidden' : '' ?>>
          <?php if ($addresses): ?>
          <label><?= e(__('my_addresses')) ?>
            <select data-address-picker>
              <option value="">—</option>
              <?php foreach ($addresses as $a): ?>
                <option value="<?= (int)$a['id'] ?>" data-address="<?= e($a['address']) ?>" data-city="<?= e($a['city']) ?>" data-zone="<?= (int)$a['zone_id'] ?>" data-phone="<?= e($a['phone']) ?>"><?= e(($a['label'] ? $a['label'] . ' — ' : '') . $a['address'] . ', ' . $a['city']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <?php endif; ?>
          <div class="form-grid">
            <label class="span-2"><?= e(__('delivery_zone')) ?> *
              <select name="zone_id" data-zone-select>
                <?php foreach ($zones as $z): ?>
                  <option value="<?= (int)$z['id'] ?>" <?= (string)$form['zone_id'] === (string)$z['id'] ? 'selected' : '' ?>><?= e($z['name']) ?> — <?= e((float)$z['fee'] > 0 ? money($z['fee']) : __('free')) ?><?= $z['delay'] ? ' (' . e($z['delay']) . ')' : '' ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="span-2"><?= e(__('address')) ?> *<input type="text" name="address" value="<?= e($form['address']) ?>" maxlength="255" autocomplete="street-address"></label>
            <label><?= e(__('city')) ?> *<input type="text" name="city" value="<?= e($form['city']) ?>" maxlength="120" autocomplete="address-level2"></label>
          </div>
        </div>
      </section>

      <section class="card checkout-step">
        <h2><span class="step-num">3</span> <?= e(__('payment_method')) ?></h2>
        <?php if (!$methods): ?>
          <div class="alert alert-error"><?= e(__('payment_unavailable')) ?></div>
        <?php endif; ?>
        <div class="choice-list">
          <?php foreach ($methods as $code => $label): ?>
          <label class="choice"><input type="radio" name="payment_method" value="<?= e($code) ?>" <?= $form['payment_method'] === $code ? 'checked' : '' ?>>
            <span><strong><?= e($label) ?></strong>
              <small><?= e(match ($code) {
                  'stripe' => 'Visa, Mastercard — paiement sécurisé par Stripe',
                  'paydunya' => 'Wave, Orange Money, Free Money… — paiement sécurisé par PayDunya',
                  'cod' => 'Réglez en espèces ou mobile money à la réception',
                  'transfer' => 'Les instructions vous seront communiquées après la commande',
                  default => '',
              }) ?></small></span></label>
          <?php endforeach; ?>
        </div>
        <label><?= e(__('order_notes')) ?><textarea name="notes" rows="3" maxlength="2000"><?= e($form['notes']) ?></textarea></label>
        <label class="check"><input type="checkbox" name="terms" value="1" required> <span><?= e(__('accept_terms')) ?> (<a href="<?= e(url('cgv')) ?>" target="_blank">CGV</a>)</span></label>
      </section>
    </div>

    <aside class="checkout-summary card">
      <h2><?= e(__('your_cart')) ?> (<?= (int)$totals['count'] ?>)</h2>
      <ul class="mini-lines">
        <?php foreach ($totals['items'] as $it): ?>
        <li><span><?= (int)$it['qty'] ?> × <?= e($it['product']['name']) ?></span><strong><?= e(money($it['line_total'])) ?></strong></li>
        <?php endforeach; ?>
      </ul>
      <?= currency_note() ?>
      <dl class="totals">
        <div><dt><?= e(__('subtotal')) ?></dt><dd><?= e(money($totals['subtotal'])) ?></dd></div>
        <?php if ($totals['discount'] > 0): ?><div class="discount"><dt><?= e(__('discount')) ?> (<?= e($totals['coupon']['code']) ?>)</dt><dd>-<?= e(money($totals['discount'])) ?></dd></div><?php endif; ?>
        <div><dt><?= e(__('delivery')) ?></dt><dd data-delivery-fee><?= e($totals['delivery_fee'] > 0 ? money($totals['delivery_fee']) : __('free')) ?></dd></div>
        <?php if ($totals['tax_rate'] > 0): ?><div class="muted"><dt><?= e($totals['tax_included'] ? __('tax_included') : __('tax')) ?> (<?= e($totals['tax_rate']) ?> %)</dt><dd><?= e(money($totals['tax'])) ?></dd></div><?php endif; ?>
        <div class="grand"><dt><?= e(__('total')) ?></dt><dd data-grand-total><?= e(money($totals['total'])) ?></dd></div>
      </dl>
      <button class="btn btn-accent btn-lg btn-block" type="submit" <?= $methods ? '' : 'disabled' ?>><?= e(__('place_order')) ?></button>
      <p class="secure-note"><?= icon('shield', 'icon icon-sm') ?> <?= e(__('secure_payment')) ?></p>
    </aside>
  </form>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
