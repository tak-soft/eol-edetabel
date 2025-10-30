<!doctype html>
<html lang="et">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>EOL Edetabel - <?php echo htmlspecialchars($viewData['disciplineName']); ?></title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
  <link rel="stylesheet" href="/assets/pp-app-theme-overrides.css">
</head>

<body>
  <?php
  // If viewData['rankings'] is empty or not provided, try to compute via RankCalculator
  $rankings = $viewData['rankings'] ?? [];
  $discipline = $viewData['discipline'] ?? "";
  // Support optional filtering by sex via query param
  $groupFilter = $_GET['group'] ?? null;
  $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
  $groups = ['WOMEN' => 'Naised', 'MEN' => 'Mehed'];

  // filter by discipline code if rankings come from a generic precomputed source
  $filtered = [];
  foreach ($rankings as $r) {
    if ($groupFilter && (($r['group'] ?? null) !== $groupFilter)) continue;
    $filtered[] = $r;
  }

  // Sort by place ascending (1,2,3...)
  usort($filtered, function ($a, $b) {
    return (($a['place'] ?? PHP_INT_MAX) <=> ($b['place'] ?? PHP_INT_MAX));
  });

  ?>

    <header class="site-header">
    <div class="site-header__inner">
      <h1 class="site-logo" role="banner">
        <a class="site-logo__link" href="https://orienteerumine.ee" rel="home"><img src="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/img/eol-new-logo.svg" alt="Estonian Orienteering Federation" height="30"><span class="sr-only">Estonian Orienteering Federation</span></a>
      </h1>
    </div>
  </header>

  <section class="app-title">
    <h1 class="app-title__heading"><?php echo htmlspecialchars($viewData['disciplineName']); ?></h1>
  </section>
  <div class="container-xl disciplines-grid">
    <section class="discipline-card edetabel-card">
      <?php if (empty($filtered)): ?>
        <p class="no-data"><em>Tulemused puuduvad</em></p>
      <?php else: ?>
        <h3 class="group-title"><?php echo $groups[$groupFilter]; ?></h3>
        <?php $leader = $filtered[0]; ?>
        <div class="leader leader-big">
          <div class="leader-rank">1</div>
          <div class="leader-name"> <a href="/athlete/<?php echo urlencode($leader['iofId']); ?>">
              <?php echo htmlspecialchars($leader['firstname'] . ' ' . $leader['lastname']); ?>
            </a></div>
          <div class="leader-info">
            <div class="leader-points sum"><?php echo htmlspecialchars((string)($leader['totalPoints'] ?? 0)); ?> punkti</div>
          </div>
        </div>

        <div class="scoreboard-list top-list compact">
          <?php foreach ($filtered as $r):
            $counted = $r['countedEvents'] ?? ($r['events'] ?? []);
            $parts = [];
            foreach ($counted as $ev) {
              $eid = isset($ev['eventorId']) ? (string)$ev['eventorId'] : null;
              $pts = (int)($ev['points'] ?? 0);
              $title = htmlspecialchars(($ev['date'] ?? '') . ' - ' . ($ev['name'] ?? ''));
              $iofEventUrl = 'https://ranking.orienteering.org/ResultsView?event=' . urlencode($ev['eventorId']) . '&person=' . $r['iofId'] . '&ohow=' . $discipline;

              $parts[] = '<a href="' . $iofEventUrl . '" class="event-points" title="' . $title . '">' . $pts . '</a>';
            }
            $eventsList = implode(' ', $parts);
          ?>
            <div class="scoreboard-list-item">
              <div class="scoreboard-list-item__col-place"><?php echo htmlspecialchars((string)($r['place'] ?? '-')); ?>.</div>
              <div class="scoreboard-list-item__col-name">
                <a class="r-link" href="/athlete/<?php echo urlencode($r['iofId']); ?>">
                  <span class="r-name"><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></span>
                </a>
                <span class="mobile">
                  <?php echo $eventsList; ?>
                </span>
              </div>
              <div class="scoreboard-list-item__col-dob"><?php echo htmlspecialchars(substr($r['birthdate'] ?? '', 0, 4)); ?></div>
              <div class="scoreboard-list-item__col-club"><?php echo ($r['clubname']) ?></div>
              <div class="scoreboard-list-item__col-points"><span class="sum"><?php echo (string)($r['totalPoints'] ?? 0); ?></span>
              </div>
              <div class="scoreboard-list-item__col-points-from"><?php echo $eventsList; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
</body>

</html>