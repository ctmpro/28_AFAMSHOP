<?php
/**
 * Pied commun du back-office.
 */
// Inclusion uniquement (pas d'accès direct)
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

clear_old();
?>
    </main>
    <footer class="admin-footer">
      <?= e(setting('site_name', 'AFAMSHOP')) ?> · Back-office · <?= date('Y') ?>
    </footer>
  </div>
</div>
<script src="<?= e(asset('admin/admin.js')) ?>"></script>
</body>
</html>
