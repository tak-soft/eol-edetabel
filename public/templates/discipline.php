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
  /*
  if (empty($rankings) && class_exists('\Eol\Edetabel\RankCalculator') && isset($pdo)) {
      try {
          $calc = new \Eol\Edetabel\RankCalculator($pdo);
          $setting = $calc->loadSettingByAlakoodAndYear($viewData['code'], $year);
          if ($setting) {
              $rankings = $calc->computeForSetting($setting);
          }
      } catch (Throwable $e) {
          // ignore and fall back to provided data
      }
  }
*/
  // Tudu: Filtreeri ala järgi juba RankCalculatoris
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

  // If we have a PDO instance, batch-resolve eventorId -> alatunnus so we can show only events from this discipline
  /*$eventDisciplineMap = [];
  if (isset($pdo)) {
    $allEventIds = [];
    foreach ($filtered as $r) {
      $counted = $r['countedEvents'] ?? ($r['events'] ?? []);
      foreach ($counted as $ev) {
        if (!empty($ev['eventorId'])) $allEventIds[(string)$ev['eventorId']] = true;
      }
    }
    $ids = array_keys($allEventIds);
    if (!empty($ids)) {
      // build placeholders
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      $sql = 'SELECT eventorId, alatunnus FROM iofevents WHERE eventorId IN (' . $placeholders . ')';
      $stmt = $pdo->prepare($sql);
      $stmt->execute($ids);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($rows as $row) {
        $eventDisciplineMap[(string)$row['eventorId']] = $row['alatunnus'] ?? null;
      }
    }
  }*/
  ?>

  <section class="app-title">
    <h1 class="app-title__heading"><?php echo htmlspecialchars($viewData['disciplineName']); ?></h1>
  </section>
  <div class="container-xl disciplines-grid">
    <div class="discipline-grid">
      <section class="discipline-card edetabel-card">
        <?php if (empty($filtered)): ?>
          <p class="no-data"><em>Tulemused puuduvad</em></p>
        <?php else: ?>
          <h2 class="group-title"><?php echo $groups[$groupFilter]; ?></h2>
          <?php $leader = $filtered[0]; ?>
          <div class="leader leader-big">
            <div class="leader-rank"><?php echo htmlspecialchars((string)($leader['place'] ?? '-')); ?></div>
            <div class="leader-name"><?php echo htmlspecialchars($leader['firstname'] . ' ' . $leader['lastname']); ?></div>
            <div class="leader-info">
              <div class="leader-points"><?php echo htmlspecialchars((string)($leader['totalPoints'] ?? $leader['points'] ?? 0)); ?></div>
              <div class="leader-links"><a class="profile-link" href="/athlete/<?php echo urlencode($leader['iofId']); ?>">vaata profiili</a></div>
            </div>
          </div>

          <ol class="top-list compact">
            <?php foreach ($filtered as $r): ?>
              <li>
                <span class="rank-num"><?php echo htmlspecialchars((string)($r['place'] ?? '-')); ?>.</span>
                <a class="r-link" href="/athlete/<?php echo urlencode($r['iofId']); ?>">
                  <span class="r-name"><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></span>
                </a>
                <span class="r-name"><?php echo htmlspecialchars($r['clubname']) ?></span>
                <span class="r-points"><?php echo htmlspecialchars((string)($r['totalPoints'] ?? $r['points'] ?? 0)); ?></span>
                <div>
                  <?php
                  $counted = $r['countedEvents'] ?? ($r['events'] ?? []);
                  //  print_r($counted);
                  $parts = [];
                  foreach ($counted as $ev) {
                    $eid = isset($ev['eventorId']) ? (string)$ev['eventorId'] : null;
                    /*
                          if (!empty($eventDisciplineMap) && $eid !== null) {
                              $eventAlat = $eventDisciplineMap[$eid] ?? null;
                              if ($eventAlat !== null && $eventAlat !== ($viewData['code'] ?? null)) {
                                  continue;
                              }
                          }*/
                    $pts = (int)($ev['points'] ?? 0);
                    $title = htmlspecialchars(($ev['date'] ?? '') . ' - ' . ($ev['name'] ?? ''));
                    $iofEventUrl = 'https://ranking.orienteering.org/ResultsView?event=' . urlencode($ev['eventorId']) . '&person=' . $r['iofId'] . '&ohow=' . $discipline; 
 
                    $parts[] = '<a href="'.$iofEventUrl.'" class="event-points" title="' . $title . '">' . $pts . '</a>';
                  }
                  echo implode(' ', $parts);
                  ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
    </section>
  </div>
</body>

</html>