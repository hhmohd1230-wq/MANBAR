<?php $title = 'Settings & audit'; ?>
<div class="pagehead"><div><h1>Settings &amp; audit log</h1></div></div>
<div class="admin-grid">
  <form class="card span-4" method="post" action="<?= e(url('admin/settings')) ?>"><?= csrf_field() ?><div class="card-title"><h3><?= icon('settings', 18) ?> Platform</h3></div>
    <label class="row field" style="cursor:pointer"><input type="checkbox" name="registration_open" value="1" <?= setting('registration_open', '1') === '1' ? 'checked' : '' ?>><span><b>Registration open</b><br><span class="small muted">When off, only existing users can sign in.</span></span></label>
    <div class="field"><label class="f">Site-wide notice</label><input class="input" name="site_notice" maxlength="300" value="<?= e(setting('site_notice', '')) ?>" placeholder="e.g. Maintenance tonight 11 pm"><div class="hint">Shown as a green bar at the top of every page. Leave empty to hide.</div></div>
    <button class="btn btn-primary" type="submit">Save settings</button></form>
  <div class="card span-8"><div class="card-title"><h3><?= icon('database', 18) ?> Audit log</h3></div>
    <div class="table-wrap" style="border:0"><table class="t"><thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Detail</th></tr></thead><tbody>
    <?php foreach ($audit as $a): ?><tr><td class="muted small nowrap"><?= e(time_ago($a['created_at'])) ?></td><td><?= e($a['full_name'] ?: '—') ?></td><td><span class="code"><?= e($a['action']) ?></span></td><td class="small"><?= e($a['detail']) ?></td></tr><?php endforeach ?>
    <?php if (!$audit): ?><tr><td colspan="4" class="tc muted" style="padding:24px">No admin actions recorded yet.</td></tr><?php endif ?></tbody></table></div></div>
</div>
