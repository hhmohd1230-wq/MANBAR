<?php $title = 'Universities'; ?>
<div class="pagehead"><div><h1>Universities</h1><p>MANBAR starts with Al Ain University. Switch another university on and its students can sign in with their own e-mail domain.</p></div></div>
<div class="cards mb">
<?php foreach ($unis as $x): ?>
  <div class="card"><div class="row between"><div class="row"><span class="badge-ico tone-green" style="margin:0;width:52px;height:52px"><?= icon('building', 26) ?></span><div><h3 style="margin:0"><?= e($x['name']) ?></h3><span class="code">@<?= e($x['domain']) ?></span></div></div><span class="status-pill st-<?= $x['active'] ? 'active' : 'closed' ?>"><?= $x['active'] ? 'Active' : 'Inactive' ?></span></div>
    <div class="row" style="margin:16px 0;gap:26px"><div class="stat"><b><?= (int) $x['users'] ?></b><span>Users</span></div><div class="stat"><b><?= (int) $x['roster'] ?></b><span>Roster</span></div></div>
    <form method="post" action="<?= e(url('admin/universities')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><button class="btn <?= $x['active'] ? 'btn-ghost' : 'btn-primary' ?> btn-block"><?= $x['active'] ? 'Disable sign-in' : 'Enable sign-in' ?></button></form></div>
<?php endforeach ?>
</div>
<form method="post" action="<?= e(url('admin/universities')) ?>" class="card" style="max-width:640px"><?= csrf_field() ?><input type="hidden" name="action" value="add"><div class="card-title"><h3><?= icon('plus', 18) ?> Add a university</h3></div>
  <div class="grid3"><div class="field" style="grid-column:span 2"><label class="f">University name</label><input class="input" name="name" required placeholder="Zayed University"></div><div class="field"><label class="f">Short name</label><input class="input" name="short_name" placeholder="ZU"></div></div>
  <div class="field"><label class="f">E-mail domain</label><input class="input" name="domain" required placeholder="zu.ac.ae"></div>
  <button class="btn btn-primary" type="submit">Add (inactive until enabled)</button></form>
