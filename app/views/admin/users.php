<?php $title = 'Users'; ?>
<div class="pagehead"><div><h1>Users</h1><p><?= count($users) ?> shown. Open someone’s control panel to ban, warn, change Rank XP, give badges or clean up their posts.</p></div><a class="btn" href="<?= e(url('admin/users?' . http_build_query(array_filter(['q' => $q, 'role' => $role, 'status' => $st, 'export' => 'csv'])))) ?>"><?= icon('download', 16) ?> Export CSV</a></div>
<form class="row wrap mb" method="get">
  <div class="search grow" style="min-width:240px"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Name, email or student number"></div>
  <select class="select" name="role" style="width:auto" data-autosubmit><option value="">All roles</option><?php foreach (['student', 'teacher', 'admin'] as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach ?></select>
  <select class="select" name="status" style="width:auto" data-autosubmit><option value="">Any status</option><option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option><option value="suspended" <?= $st === 'suspended' ? 'selected' : '' ?>>Suspended</option></select>
  <label class="chip <?= input('unverified') ? 'on' : 'outline' ?>" style="cursor:pointer"><input type="checkbox" name="unverified" value="1" <?= input('unverified') ? 'checked' : '' ?> data-autosubmit hidden> Unverified only</label>
</form>
<div class="table-wrap"><table class="t ad-users"><thead><tr><th>User</th><th>Student #</th><th>Role</th><th>Status</th><th>Rank XP</th><th>Joined</th><th style="text-align:right">Actions</th></tr></thead><tbody>
<?php foreach ($users as $r): $self = (int) $r['id'] === (int) $u['id']; ?>
<tr class="<?= $r['status'] !== 'active' ? 'is-banned' : '' ?>"><td><div class="row"><?= avatar($r, 38) ?><div><a href="<?= e(url('admin/users/' . $r['id'])) ?>"><b><?= e($r['full_name']) ?></b></a><?= verified_badge($r) ?><div class="xs muted"><?= e($r['email']) ?></div></div></div></td>
<td class="small"><?= e($r['student_id'] ?: '—') ?></td>
<td><form method="post" action="<?= e(url('admin/users/' . $r['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="role"><select class="select" name="role" style="padding:5px 8px;width:auto;font-size:13px" data-autosubmit <?= $self ? 'disabled' : '' ?>><?php foreach (['student', 'teacher', 'admin'] as $x): ?><option <?= $r['role'] === $x ? 'selected' : '' ?>><?= $x ?></option><?php endforeach ?></select></form></td>
<td><span class="status-pill st-<?= $r['status'] === 'active' ? 'active' : 'closed' ?>" <?= $r['status'] !== 'active' && $r['ban_reason'] ? 'title="' . e($r['ban_reason']) . '"' : '' ?>><?= e(ban_label($r)) ?></span></td>
<td><?php $lv = level_for((int) $r['reputation_score']); ?><b><?= number_format((int) $r['reputation_score']) ?></b><div class="xs muted"><?= e($lv['name']) ?> <?= str_repeat('★', $lv['star']) ?></div></td><td class="muted small"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
<td style="text-align:right"><form method="post" action="<?= e(url('admin/users/' . $r['id'])) ?>" class="row" style="justify-content:flex-end;gap:6px"><?= csrf_field() ?>
  <a class="btn btn-sm btn-primary" href="<?= e(url('admin/users/' . $r['id'])) ?>"><?= icon('settings', 14) ?> Manage</a>
  <button class="btn btn-sm" name="action" value="verify" title="Toggle verified"><?= $r['verified'] ? 'Unverify' : 'Verify' ?></button>
  <?php if (!$self): ?><?php if ($r['status'] !== 'active'): ?><button class="btn btn-sm btn-ghost" name="action" value="activate">Lift ban</button><?php endif ?>
  <button class="btn btn-sm btn-danger" name="action" value="delete" onclick="return confirm('Delete this user and all their content?')" aria-label="Delete"><?= icon('trash', 14) ?></button><?php endif ?></form></td></tr>
<?php endforeach ?>
<?php if (!$users): ?><tr><td colspan="7" class="tc muted" style="padding:30px">No users match.</td></tr><?php endif ?>
</tbody></table></div>
