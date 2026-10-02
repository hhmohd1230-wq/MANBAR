<?php $title = 'Student roster'; ?>
<div class="pagehead"><div><h1>Student roster</h1><p>The official list that powers sign-in: a student number in the e-mail (e.g. <span class="code">202020280@aau.ac.ae</span>) fills in name, major and year automatically. <b><?= number_format($total) ?></b> entries.</p></div></div>
<div class="admin-grid mb">
  <form class="card span-6" method="post" action="<?= e(url('admin/roster/add')) ?>"><?= csrf_field() ?><div class="card-title"><h3><?= icon('user-plus', 18) ?> Add a student</h3></div>
    <div class="grid2"><div class="field"><label class="f">Student number</label><input class="input" name="student_id" required placeholder="202020280"></div><div class="field"><label class="f">University</label><select class="select" name="university_id"><?php foreach ($unis as $x): ?><option value="<?= (int) $x['id'] ?>"><?= e($x['short_name']) ?></option><?php endforeach ?></select></div></div>
    <div class="field"><label class="f">Full name</label><input class="input" name="full_name" required></div>
    <div class="grid3"><div class="field"><label class="f">Major</label><input class="input" name="major"></div><div class="field"><label class="f">Faculty</label><input class="input" name="faculty"></div><div class="field"><label class="f">Year</label><input class="input" type="number" name="year_level" min="1" max="6"></div></div>
    <button class="btn btn-primary" type="submit">Add to roster</button></form>
  <form class="card span-6" method="post" enctype="multipart/form-data" action="<?= e(url('admin/roster/import')) ?>"><?= csrf_field() ?><div class="card-title"><h3><?= icon('upload', 18) ?> Import CSV</h3></div>
    <p class="small muted">Columns in this order (header row optional):<br><span class="code">student_id, full_name, major, faculty, year_level</span></p>
    <div class="field"><label class="f">University</label><select class="select" name="university_id"><?php foreach ($unis as $x): ?><option value="<?= (int) $x['id'] ?>"><?= e($x['short_name']) ?></option><?php endforeach ?></select></div>
    <div class="field"><input class="input" type="file" name="csv" accept=".csv,text/csv" required></div>
    <button class="btn btn-primary" type="submit">Import</button> <span class="hint">Existing student numbers are updated, not duplicated.</span></form>
</div>
<form class="mb" method="get"><div class="search" style="max-width:420px"><?= icon('search', 18) ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Search roster"></div></form>
<div class="table-wrap"><table class="t"><thead><tr><th>#</th><th>Name</th><th>Major</th><th>Year</th><th>Uni</th><th>Registered</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td class="code"><?= e($r['student_id']) ?></td><td><b><?= e($r['full_name']) ?></b></td><td><?= e($r['major']) ?></td><td><?= $r['year_level'] ? (int) $r['year_level'] : '—' ?></td><td><?= e($r['short_name']) ?></td><td><?= $r['registered'] ? '<span class="status-pill st-active">Yes</span>' : '<span class="muted small">Not yet</span>' ?></td>
<td style="text-align:right"><form method="post" action="<?= e(url('admin/roster/' . $r['id'] . '/delete')) ?>" data-confirm="Remove from roster?"><?= csrf_field() ?><button class="icon-btn" aria-label="Remove"><?= icon('trash', 16) ?></button></form></td></tr><?php endforeach ?>
</tbody></table></div>
