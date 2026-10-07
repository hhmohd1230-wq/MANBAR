<?php
$title = $p['full_name'];
$own = (int) $p['id'] === (int) $u['id'];
$tabs = ['posts' => 'Posts', 'projects' => 'Projects', 'services' => 'Services', 'badges' => 'Badges', 'recommendations' => 'Recommendations'];
if ($courses) $tabs['courses'] = 'Courses';
$nameStyle = in_array($p['name_style'] ?? 'classic', PROFILE_NAME_STYLES, true) ? $p['name_style'] : 'classic';
$profileEffect = in_array($p['profile_effect'] ?? 'none', PROFILE_EFFECTS, true) ? $p['profile_effect'] : 'none';
$hasCover = !empty($p['cover_image']);
$coverStyle = $hasCover ? profile_cover_style($p) : theme_css((int) $p['theme']);
$whatsappDigits = preg_replace('/\D/', '', (string) ($p['whatsapp'] ?? ''));
$contacts = array_filter([
  ['website', 'Website', 'globe', $p['website'] ?? null, $p['website'] ? (parse_url($p['website'], PHP_URL_HOST) ?: 'Open website') : null],
  ['linkedin', 'LinkedIn', 'linkedin', $p['linkedin'] ?? null, $p['linkedin'] ? (parse_url($p['linkedin'], PHP_URL_HOST) ?: 'Professional profile') : null],
  ['github', 'GitHub', 'github', $p['github'] ?? null, $p['github'] ? (parse_url($p['github'], PHP_URL_HOST) ?: 'Code and projects') : null],
  ['discord', 'Discord', 'discord', $p['discord'] ?? null, $p['discord'] ? (parse_url($p['discord'], PHP_URL_HOST) ?: 'Discord profile') : null],
  ['whatsapp', 'WhatsApp', 'whatsapp', $whatsappDigits ? 'https://wa.me/' . $whatsappDigits : null, $p['whatsapp'] ?? null],
  ['phone', 'Phone', 'phone', !empty($p['phone']) ? 'tel:' . preg_replace('/[^0-9+]/', '', $p['phone']) : null, $p['phone'] ?? null],
], fn($contact) => !empty($contact[3]));
?>
<div class="page-grid profile-page">
  <div class="stack gap-l">
    <div class="card profile-card profile-effect-<?= e($profileEffect) ?>">
      <div class="cover profile-cover <?= $hasCover ? 'has-image' : '' ?>" style="<?= e($coverStyle) ?>">
        <div class="profile-cover-shine" aria-hidden="true"></div>
      </div>
      <div class="profile-top">
        <div class="profile-avatar-stage profile-rank-<?= e(rank_key((int) $lvl['n'])) ?>" aria-label="<?= e($p['full_name']) ?> profile photo">
          <span class="profile-vfx profile-vfx-a" aria-hidden="true"></span><span class="profile-vfx profile-vfx-b" aria-hidden="true"></span>
          <?= avatar($p, 112) ?>
          <span class="profile-rank-emblem rank-emblem rank-<?= e(rank_key((int) $lvl['n'])) ?>" title="<?= e($lvl['name']) ?> rank" aria-label="<?= e($lvl['name']) ?> rank"></span>
        </div>
        <div class="info">
          <h1 class="profile-name profile-name-<?= e($nameStyle) ?>"><?= e($p['full_name']) ?> <?= verified_badge($p) ?></h1>
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
        <button class="stat stat-button" type="button" data-people-dialog="followersDialog"><b data-followers><?= (int) $p['followers'] ?></b><span>Followers</span></button>
        <button class="stat stat-button" type="button" data-people-dialog="followingDialog"><b><?= (int) $p['following'] ?></b><span>Following</span></button>
        <div class="stat"><b><?= count($posts) ?></b><span>Posts</span></div>
        <div class="stat"><b><?= count($projects) ?></b><span>Projects</span></div>
        <div class="stat"><b><?= number_format((int) $rankScore) ?></b><span>Rank XP</span></div>
        <div class="stat"><b><?= count($badges) ?></b><span>Badges</span></div>
      </div>
    </div>

    <?php partial('profile_about', ['p' => $p, 'contacts' => $contacts, 'own' => $own, 'placement' => 'profile-about-mobile']) ?>

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
      <section class="profile-badge-vault" aria-labelledby="profile-badge-title">
        <div class="achievement-section-head"><div><h2 id="profile-badge-title">Earned badge collection</h2><p>Verified milestones from <?= e($p['full_name']) ?>’s MANBAR journey.</p></div><span><?= icon('award', 15) ?> <?= count($badges) ?> earned</span></div>
        <div class="achievement-badge-grid">
          <?php foreach ($badges as $b): ?>
            <article class="achievement-badge profile-earned-badge tier-<?= e($b['tier']) ?> is-earned">
              <div class="achievement-badge-top"><span class="badge-tier"><?= e(['standard' => 'Core', 'rare' => 'Rare', 'epic' => 'Epic', 'legendary' => 'Legendary'][$b['tier']] ?? 'Core') ?></span><span class="badge-state"><?= icon('check-circle', 15) ?> Earned</span></div>
              <div class="achievement-badge-medal tone-<?= e($b['tone']) ?>"><?= icon($b['icon'], 28) ?></div>
              <h3><?= e($b['name']) ?></h3><p><?= e($b['description']) ?></p>
              <small class="badge-earned-date">Unlocked <?= e(time_ago($b['earned'])) ?></small>
            </article>
          <?php endforeach ?>
        </div>
      </section>
      <?php if (!$badges): ?><div class="card empty"><img src="<?= e(asset('img/empty.svg')) ?>" alt=""><h3>No badges yet</h3><p>Posting, commenting and joining projects unlock badges.</p></div><?php endif ?>
    <?php elseif ($tab === 'recommendations'): ?>
      <section class="profile-reputation-summary">
        <div class="profile-rank-display"><span class="rank-emblem rank-<?= e(rank_key((int) $lvl['n'])) ?>"></span><div><small>Current reputation rank</small><h2><?= e($lvl['name']) ?></h2><p><?= number_format((int) $rankScore) ?> rank XP · <?= (int) $reputation['recommendation_count'] ?> verified recommendations</p></div></div>
        <div class="profile-trust-display"><span><?= icon('shield', 22) ?></span><div><small>Trust score</small><strong><?= $reputation['trust_percent'] === null ? 'New' : (int) $reputation['trust_percent'] . '%' ?></strong><p>Based only on completed work and verified ratings.</p></div></div>
      </section>
      <?php if ($canRecommend): ?>
        <form class="card recommendation-form" method="post" action="<?= e(url('profile/' . $p['id'] . '/recommend')) ?>">
          <?= csrf_field() ?>
          <div class="card-title"><div><h3><?= $myRecommendation ? 'Update your recommendation' : 'Recommend ' . e($p['full_name']) ?></h3><p class="small muted" style="margin:2px 0 0"><?= e($canRecommend['label']) ?> verifies this recommendation.</p></div><span class="recommendation-verified"><?= icon('check-circle', 14) ?> Verified work</span></div>
          <div class="recommendation-fields"><label><span>Your rating</span><select class="select" name="rating"><?php for ($stars = 5; $stars >= 1; $stars--): ?><option value="<?= $stars ?>" <?= (int) ($myRecommendation['rating'] ?? 5) === $stars ? 'selected' : '' ?>><?= $stars ?> / 5<?= $stars === 5 ? ' · Exceptional' : '' ?></option><?php endfor ?></select></label><label class="grow"><span>What was it like to work together?</span><textarea class="textarea" name="body" minlength="20" maxlength="800" required placeholder="Describe their reliability, communication and quality of work…"><?= e($myRecommendation['body'] ?? '') ?></textarea></label></div>
          <button class="btn btn-primary" type="submit"><?= icon('award', 16) ?> <?= $myRecommendation ? 'Save recommendation' : 'Publish recommendation' ?></button>
        </form>
      <?php elseif (!$own): ?>
        <div class="recommendation-locked"><?= icon('lock', 21) ?><div><b>Recommendations are earned through real work</b><p>Complete a project, marketplace order or mentoring session together before recommending this member.</p></div></div>
      <?php endif ?>
      <div class="recommendation-list">
        <?php foreach ($recommendations as $recommendation): ?>
          <article class="card recommendation-card"><div class="person"><?= avatar($recommendation, 46) ?><div class="grow"><a class="nm" href="<?= e(url('profile/' . $recommendation['author_id'])) ?>"><?= e($recommendation['full_name']) ?></a><?= verified_badge($recommendation) ?><div class="xs muted"><?= e(ucfirst($recommendation['context_type'])) ?> collaboration · <?= e(time_ago($recommendation['created_at'])) ?></div></div><span class="recommendation-stars"><?= str_repeat('★', (int) $recommendation['rating']) ?><i><?= str_repeat('★', 5 - (int) $recommendation['rating']) ?></i></span></div><p>“<?= e($recommendation['body']) ?>”</p></article>
        <?php endforeach ?>
        <?php if (!$recommendations): ?><div class="card empty"><span class="badge-ico tone-green"><?= icon('award', 28) ?></span><h3>No recommendations yet</h3><p>Verified recommendations appear after completed work with another MANBAR member.</p></div><?php endif ?>
      </div>
    <?php elseif ($tab === 'courses'): ?>
      <div class="cards"><?php foreach ($courses as $c): ?><a class="card" href="<?= e(url('learn/' . $c['id'])) ?>" style="color:var(--ink)"><b><?= e($c['title']) ?></b><div class="xs muted" style="margin-top:6px"><?= (int) $c['students'] ?> students enrolled</div></a><?php endforeach ?></div>
    <?php endif ?>
  </div>

  <aside class="aside">
    <?php partial('profile_about', ['p' => $p, 'contacts' => $contacts, 'own' => $own, 'placement' => 'profile-about-desktop']) ?>
    <div class="card">
      <div class="card-title"><h3>Skills</h3></div>
      <div class="tag-cloud"><?php foreach ($p['skills'] as $s): ?><a class="chip skill-chip" href="<?= e(url('people?skill=' . urlencode($s))) ?>"><?= e($s) ?></a><?php endforeach ?><?php if (!$p['skills']): ?><span class="muted small">No skills added yet.</span><?php endif ?></div>
      <?php if ($p['interests']): ?><hr class="divider"><div class="card-title" style="margin-bottom:8px"><h3 style="font-size:14px">Interests</h3></div><div class="tag-cloud"><?php foreach ($p['interests'] as $s): ?><span class="chip outline"><?= e($s) ?></span><?php endforeach ?></div><?php endif ?>
    </div>
    <div class="card">
      <div class="card-title"><h3>Reputation rank</h3><span class="chip on">Rank <?= $lvl['n'] ?></span></div>
      <div class="lvl-ring"><span class="rank-emblem rank-<?= e(rank_key((int) $lvl['n'])) ?> profile-side-rank"></span><div class="grow"><b style="font-size:17px"><?= e($lvl['name']) ?></b><div class="progress" style="margin:6px 0"><i style="width:<?= $lvl['pct'] ?>%"></i></div><div class="xs muted"><?= number_format((int) $rankScore) ?> XP<?= $lvl['next'] ? ' · next: ' . e($lvl['next']) : '' ?></div></div></div>
    </div>
    <?php if ($mentor): ?><div class="card"><div class="card-title"><h3><?= icon('compass', 17) ?> Mentor</h3></div><p class="small"><b>Expertise:</b> <?= e($mentor['expertise']) ?></p><?php if (!$own): ?><a class="btn btn-block" href="<?= e(url('mentors/' . $p['id'])) ?>">Request mentorship</a><?php endif ?></div><?php endif ?>
  </aside>
</div>

<?php foreach ([['followersDialog', 'Followers', $followers, 'People who follow ' . $p['full_name']], ['followingDialog', 'Following', $following, $p['full_name'] . ' follows these people']] as [$dialogId, $dialogTitle, $peopleList, $dialogCopy]): ?>
<dialog class="people-dialog" id="<?= e($dialogId) ?>" aria-labelledby="<?= e($dialogId) ?>Title">
  <div class="people-dialog-head"><div><h2 id="<?= e($dialogId) ?>Title"><?= e($dialogTitle) ?> <span><?= count($peopleList) ?></span></h2><p><?= e($dialogCopy) ?></p></div><button class="icon-btn" type="button" data-dialog-close aria-label="Close"><?= icon('x', 19) ?></button></div>
  <div class="people-dialog-list">
    <?php foreach ($peopleList as $person): ?><a class="people-dialog-person" href="<?= e(url('profile/' . $person['id'])) ?>"><?= avatar($person, 46) ?><span class="grow"><b><?= e($person['full_name']) ?></b><?= verified_badge($person) ?><small><?= e($person['headline'] ?: $person['major'] ?: role_label($person['role'])) ?></small></span><span class="people-dialog-view">View <?= icon('arrow-right', 14) ?></span></a><?php endforeach ?>
    <?php if (!$peopleList): ?><div class="people-dialog-empty"><?= icon('users', 27) ?><b>No one here yet</b><span>This list will grow as connections are made.</span></div><?php endif ?>
  </div>
</dialog>
<?php endforeach ?>
