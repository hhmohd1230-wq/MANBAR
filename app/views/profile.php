<?php
$title = $p['full_name'];
$own = (int) $p['id'] === (int) $u['id'];
$tabs = ['posts' => 'Posts', 'projects' => 'Projects', 'services' => 'Services', 'badges' => 'Badges'];
if ($courses) $tabs['courses'] = 'Courses';
?>
<div class="page-grid">
  <div class="stack gap-l">
    <div class="card profile-card">
      <div class="cover" style="<?= theme_css((int) $p['theme']) ?>"></div>
      <div class="profile-top">
        <?= avatar($p, 112) ?>
        <div class="info">
          <h1><?= e($p['full_name']) ?> <?= verified_badge($p) ?></h1>
          <div class="muted"><?= e($p['headline'] ?: ($p['role'] === 'student' ? 'Student at ' . $p['uni_name'] : role_label($p['role']) . ' at ' . $p['uni_name'])) ?></div>
          <div class="row wrap gap-s" style="margin-top:8px">
            <span class="role-pill <?= e($p['role']) ?>"><?= e(role_label($p['role'])) ?></span>
            <?php if ($p['major']): ?><span class="chip sm"><?= icon('cap', 13) ?> <?= e($p['major']) ?><?= $p['year_level'] ? ' · Year ' . (int) $p['year_level'] : '' ?></span><?php endif ?>
            <?php if ($p['department']): ?><span class="chip sm"><?= icon('building', 13) ?> <?= e($p['department']) ?></span><?php endif ?>
            <span class="chip sm"><?= icon('building', 13) ?> <?= e($p['uni_short']) ?></span>
            <?php if ($p['student_id'] && ($own || is_admin())): ?><span class="chip sm outline">ID <?= e($p['student_id']) ?></span><?php endif ?>
          </div>
        </div>
        <div class="row">
          <?php if ($own): ?><a class="btn btn-primary" href="<?= e(url('profile/edit')) ?>"><?= icon('edit', 16) ?> Edit profile</a>
          <?php else: ?>
            <button class="btn <?= $p['is_following'] ? '' : 'btn-primary' ?>" data-follow="<?= (int) $p['id'] ?>" data-primary="1"><?= $p['is_following'] ? 'Following' : 'Follow' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('messages/' . $p['id'])) ?>"><?= icon('message', 16) ?> Message</a>
            <?php if ($mentor): ?><a class="btn btn-ghost" href="<?= e(url('mentors/' . $p['id'])) ?>"><?= icon('compass', 16) ?> Mentor</a><?php endif ?>
          <?php endif ?>
        </div>
      </div>
      <div class="stats">
        <div class="stat"><b data-followers><?= (int) $p['followers'] ?></b><span>Followers</span></div>
        <div class="stat"><b><?= (int) $p['following'] ?></b><span>Following</span></div>
        <div class="stat"><b><?= count($posts) ?></b><span>Posts</span></div>
        <div class="stat"><b><?= count($projects) ?></b><span>Projects</span></div>
        <div class="stat"><b><?= (int) $p['points'] ?></b><span>Points</span></div>
        <div class="stat"><b><?= count($badges) ?></b><span>Badges</span></div>
      </div>
    </div>

    <div class="tabs"><?php foreach ($tabs as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= e(url('profile/' . $p['id'] . '?tab=' . $k)) ?>"><?= $l ?></a><?php endforeach ?></div>

    <?php if ($tab === 'posts'): ?>
      <div class="feed">
        <?php foreach ($posts as $post) partial('post_card', ['p' => $post, 'u' => $u]) ?>
        <?php if (!$posts): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No posts yet</h3><?php if ($own): ?><a class="btn btn-primary" href="<?= e(url('feed?compose=idea')) ?>">Share your first idea</a><?php endif ?></div><?php endif ?>
      </div>
    <?php elseif ($tab === 'projects'): ?>
      <div class="cards">
        <?php foreach ($projects as $pr): ?><a class="card" href="<?= e(url('projects/' . $pr['id'])) ?>" style="color:var(--ink)"><div class="row between"><b><?= e($pr['title']) ?></b><span class="status-pill st-<?= e($pr['status']) ?>"><?= e(str_replace('_', ' ', $pr['status'])) ?></span></div><p class="small muted" style="margin:8px 0"><?= e(excerpt($pr['description'], 120)) ?></p><span class="chip sm"><?= icon('users', 13) ?> <?= (int) $pr['members'] ?> members</span></a><?php endforeach ?>
        <?php if (!$projects): ?><div class="card empty" style="grid-column:1/-1"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No projects yet</h3></div><?php endif ?>
      </div>
    <?php elseif ($tab === 'services'): ?>
      <div class="cards">
        <?php foreach ($services as $s): ?><a class="card" href="<?= e(url('marketplace/' . $s['id'])) ?>" style="color:var(--ink)"><span class="chip sm"><?= e(SERVICE_CATEGORIES[$s['category']] ?? 'Other') ?></span><h3 style="margin-top:8px"><?= e($s['title']) ?></h3><div class="price"><?= $s['price'] ? 'AED ' . (int) $s['price'] : 'Free' ?> <small>· <?= (int) $s['delivery_days'] ?> day delivery</small></div></a><?php endforeach ?>
        <?php if (!$services): ?><div class="card empty" style="grid-column:1/-1"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No services listed</h3></div><?php endif ?>
      </div>
    <?php elseif ($tab === 'badges'): ?>
      <div class="badge-grid"><?php foreach ($badges as $b): ?><div class="badge-card tone-<?= e($b['tone']) ?>"><div class="badge-ico"><?= icon($b['icon'], 28) ?></div><b><?= e($b['name']) ?></b><small><?= e($b['description']) ?></small></div><?php endforeach ?></div>
      <?php if (!$badges): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No badges yet</h3><p>Posting, commenting and joining projects unlock badges.</p></div><?php endif ?>
    <?php elseif ($tab === 'courses'): ?>
      <div class="cards"><?php foreach ($courses as $c): ?><a class="card" href="<?= e(url('learn/' . $c['id'])) ?>" style="color:var(--ink)"><b><?= e($c['title']) ?></b><div class="xs muted" style="margin-top:6px"><?= (int) $c['students'] ?> students enrolled</div></a><?php endforeach ?></div>
    <?php endif ?>
  </div>

  <aside class="aside">
    <div class="card">
      <div class="card-title"><h3>About</h3></div>
      <p class="muted" style="margin:0"><?= $p['bio'] ? nl2br(e($p['bio'])) : 'No bio yet.' ?></p>
      <?php if ($p['website'] || $p['linkedin'] || $p['github']): ?><hr class="divider"><div class="row wrap">
        <?php foreach (['website' => ['globe', 'Website'], 'linkedin' => ['link', 'LinkedIn'], 'github' => ['link', 'GitHub']] as $k => [$ic, $lb]) if ($p[$k]): ?><a class="chip outline" href="<?= e($p[$k]) ?>" target="_blank" rel="noopener nofollow"><?= icon($ic, 14) ?> <?= $lb ?></a><?php endif ?>
      </div><?php endif ?>
    </div>
    <div class="card">
      <div class="card-title"><h3>Skills</h3></div>
      <div class="tag-cloud"><?php foreach ($p['skills'] as $s): ?><a class="chip skill-chip" href="<?= e(url('people?skill=' . urlencode($s))) ?>"><?= e($s) ?></a><?php endforeach ?><?php if (!$p['skills']): ?><span class="muted small">No skills added yet.</span><?php endif ?></div>
      <?php if ($p['interests']): ?><hr class="divider"><div class="card-title" style="margin-bottom:8px"><h3 style="font-size:14px">Interests</h3></div><div class="tag-cloud"><?php foreach ($p['interests'] as $s): ?><span class="chip outline"><?= e($s) ?></span><?php endforeach ?></div><?php endif ?>
    </div>
    <div class="card">
      <div class="card-title"><h3>Level</h3><span class="chip on">Lv <?= $lvl['n'] ?></span></div>
      <div class="lvl-ring"><div class="badge-ico tone-green" style="margin:0"><?= icon('trophy', 28) ?></div><div class="grow"><b style="font-size:17px"><?= e($lvl['name']) ?></b><div class="progress" style="margin:6px 0"><i style="width:<?= $lvl['pct'] ?>%"></i></div><div class="xs muted"><?= (int) $p['points'] ?> points<?= $lvl['next'] ? ' · next: ' . e($lvl['next']) : '' ?></div></div></div>
    </div>
    <?php if ($mentor): ?><div class="card"><div class="card-title"><h3><?= icon('compass', 17) ?> Mentor</h3></div><p class="small"><b>Expertise:</b> <?= e($mentor['expertise']) ?></p><?php if (!$own): ?><a class="btn btn-block" href="<?= e(url('mentors/' . $p['id'])) ?>">Request mentorship</a><?php endif ?></div><?php endif ?>
  </aside>
</div>
