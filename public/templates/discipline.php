<!doctype html>
<html lang="et">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>EOL Edetabel - <?php echo htmlspecialchars($viewData['disciplineName'] ?? $viewData['code']); ?></title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
  <link rel="stylesheet" href="/assets/pp-app-theme-overrides.css">
</head>
<body>
  <h1><?php echo htmlspecialchars($viewData['disciplineName'] ?? $viewData['code']); ?></h1>

  <?php
  // If viewData['rankings'] is empty or not provided, try to compute via RankCalculator
  $rankings = $viewData['rankings'] ?? [];
  // Support optional filtering by sex via query param
  $sexFilter = $_GET['sex'] ?? null;
  $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
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

  // filter by discipline code if rankings come from a generic precomputed source
  $filtered = [];
  foreach ($rankings as $r) {
      if (isset($r['discipline']) && $r['discipline'] !== $viewData['code']) continue;
      if ($sexFilter && (($r['sex'] ?? null) !== $sexFilter)) continue;
      $filtered[] = $r;
  }

  // Sort by place ascending (1,2,3...)
  usort($filtered, function($a,$b){ return (($a['place'] ?? PHP_INT_MAX) <=> ($b['place'] ?? PHP_INT_MAX)); });

  // If we have a PDO instance, batch-resolve eventorId -> alatunnus so we can show only events from this discipline
  $eventDisciplineMap = [];
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
  }
  ?>

  <div class="disciplines-grid">
    <section class="discipline-card edetabel-card">
      <h2 class="discipline-title"><?php echo htmlspecialchars($viewData['disciplineName'] ?? $viewData['code']); ?></h2>
      <div class="discipline-grid">
        <div class="sex-column">
          <?php if (empty($filtered)): ?>
            <p class="no-data"><em>Tulemused puuduvad</em></p>
          <?php else: ?>
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
                  <span class="r-points"><?php echo htmlspecialchars((string)($r['totalPoints'] ?? $r['points'] ?? 0)); ?></span>
                  <div style="margin-top:4px">
                    <?php
                      $counted = $r['countedEvents'] ?? ($r['events'] ?? []);
                      $parts = [];
                      foreach ($counted as $ev) {
                          $eid = isset($ev['eventorId']) ? (string)$ev['eventorId'] : null;
                          if (!empty($eventDisciplineMap) && $eid !== null) {
                              $eventAlat = $eventDisciplineMap[$eid] ?? null;
                              if ($eventAlat !== null && $eventAlat !== ($viewData['code'] ?? null)) {
                                  continue;
                              }
                          }
                          $pts = number_format((float)($ev['points'] ?? 0), 1);
                          $title = htmlspecialchars(($ev['date'] ?? '') . ' - ' . ($ev['name'] ?? ''));
                          $parts[] = '<span class="event-points" title="' . $title . '">' . $pts . '</span>';
                      }
                      echo implode(' ', $parts);
                    ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</body>
</html>
