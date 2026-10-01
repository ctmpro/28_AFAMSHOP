<?php
/**
 * Formulaire de demande réutilisable. Attend $formType (quote|rental|maintenance|contact)
 * et optionnellement $formProduct (produit pré-sélectionné), $formErrors.
 */
$u = Auth::user();
$val = fn(string $k, string $default = '') => e(post($k, old($k, $default)));
$formErrors = $formErrors ?? [];
?>
<form method="post" enctype="multipart/form-data" class="request-form card" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="form_type" value="<?= e($formType) ?>">
  <?php if ($formErrors): ?><div class="alert alert-error"><ul><?php foreach ($formErrors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="hp" aria-hidden="true"><label>Site web<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <div class="form-grid">
    <label><?= e(__('full_name')) ?> *<input type="text" name="name" value="<?= $val('name', $u ? $u['first_name'] . ' ' . $u['last_name'] : '') ?>" required maxlength="150"></label>
    <?php if ($formType !== 'contact'): ?>
    <label><?= e(__('company')) ?><input type="text" name="company" value="<?= $val('company', $u['company'] ?? '') ?>" maxlength="190"></label>
    <?php endif; ?>
    <label><?= e(__('email')) ?> *<input type="email" name="email" value="<?= $val('email', $u['email'] ?? '') ?>" required maxlength="190"></label>
    <label><?= e(__('phone')) ?><?= $formType !== 'contact' ? ' *' : '' ?><input type="tel" name="phone" value="<?= $val('phone', $u['phone'] ?? '') ?>" <?= $formType !== 'contact' ? 'required' : '' ?> maxlength="40"></label>

    <?php if ($formType === 'quote'): ?>
      <?php if (!empty($formProduct)): ?><input type="hidden" name="product_id" value="<?= (int)$formProduct['id'] ?>"><?php endif; ?>
      <label class="span-2"><?= e(__('product')) ?><input type="text" name="product_label" value="<?= $val('product_label', !empty($formProduct) ? $formProduct['name'] . ' (' . $formProduct['sku'] . ')' : '') ?>" maxlength="255"></label>
      <label><?= e(__('quantity')) ?><input type="number" name="quantity" min="1" value="<?= $val('quantity', '1') ?>"></label>
    <?php elseif ($formType === 'maintenance'): ?>
      <label><?= e(__('printer_model')) ?> *<input type="text" name="printer_model" value="<?= $val('printer_model') ?>" required maxlength="150" placeholder="Sharp MX-3061"></label>
      <label><?= e(__('serial_number')) ?><input type="text" name="serial_number" value="<?= $val('serial_number') ?>" maxlength="120"></label>
    <?php elseif ($formType === 'contact'): ?>
      <label class="span-2"><?= e(__('subject')) ?><input type="text" name="subject" value="<?= $val('subject') ?>" maxlength="190"></label>
    <?php endif; ?>

    <label class="span-2"><?= e($formType === 'maintenance' ? __('problem_description') : __('message')) ?><?= in_array('message', Requests::REQUIRED[$formType], true) ? ' *' : '' ?>
      <textarea name="message" rows="5" maxlength="5000" <?= in_array('message', Requests::REQUIRED[$formType], true) ? 'required' : '' ?>><?= e(post('message', (string)query_param('message'))) ?></textarea></label>

    <?php if ($formType !== 'contact'): ?>
    <label class="span-2 file-input"><?= e(__('attachment')) ?> <small>(<?= e(__('attachment_hint')) ?>)</small>
      <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"></label>
    <?php endif; ?>
  </div>
  <?= Recaptcha::widget($formType) ?>
  <p class="muted small"><?= e(__('required_fields')) ?></p>
  <button class="btn btn-accent btn-lg" type="submit"><?= e(__('send')) ?></button>
</form>
