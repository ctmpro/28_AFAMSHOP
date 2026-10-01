<?php
/**
 * Pied commun du back-office.
 */
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
