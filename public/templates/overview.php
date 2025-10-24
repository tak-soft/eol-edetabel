<!doctype html>
<html lang="et">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>EOL Edetabel</title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
  <link rel="stylesheet" href="/assets/pp-app-theme-overrides.css">
</head>

<body>
  <section class="app-title">
    <h1 class="app-title__heading">EOL Edetabel — <?php echo htmlspecialchars((string)$viewData['year']); ?></h1>
  </section>
  <nav class="nav-national-team">
    <?php
    // Use discipline names provided by controller (from edetabli_seaded) when available
    $disciplineNames = $viewData['disciplineNames'];
    $periods = $viewData['periods'] ?? [];
    $groups = ['WOMEN' => 'Naised', 'MEN' => 'Mehed'];
    foreach ($periods as $key => $value) {
      $selected = $key == $viewData['year'] ? ' current-menu-item': '';
      echo '<a class="menu-item menu-item-type-post_type menu-item-object-page menu-item-3707'.$selected.'" hreflang="et" href="/?year=' . $value . '">' . $value . '</a>';
    }
    ?>
  </nav>
  <div class="disciplines-grid">
    <?php foreach ($viewData['overview'] as $discipline => $bygroup): ?>
      <section class="discipline-card edetabel-card">
        <h2 class="discipline-title"><?php echo htmlspecialchars($disciplineNames[$discipline] ?? $discipline); ?></h2>
        <div class="discipline-grid">
          <?php foreach (['WOMEN', 'MEN'] as $groupKey): ?>
            <?php $rows = $bygroup[$groupKey] ?? []; ?>
            <div class="group-column">
              <h3 class="group-title"><?php echo $groups[$groupKey]; ?></h3>
              <?php if (empty($rows)): ?>
                <p class="no-data"><em>Tulemused puuduvad</em></p>
              <?php else: ?>
                <?php $leader = $rows[0]; ?>
                <div class="leader leader-big">
                  <div class="leader-rank"><?php echo htmlspecialchars((string)($leader['place'] ?? $leader['koht'] ?? 1)); ?></div>
                  <div class="leader-name"><?php echo htmlspecialchars($leader['firstname'] . ' ' . $leader['lastname']); ?></div>
                  <div class="leader-info">
                    <div class="leader-points"><?php echo htmlspecialchars((string)($leader['totalPoints'] ?? $leader['points'] ?? 0)); ?></div>
                    <div class="leader-links"><a class="profile-link" href="/athlete/<?php echo urlencode($leader['iofId'] ?? $leader['iofId']); ?>">vaata profiili</a></div>
                  </div>
                </div>
                <ol class="top-list compact">
                  <?php foreach (array_slice($rows, 1, 10) as $idx => $r): ?>
                    <li>
                      <?php $place = $r['place'] ?? "-" ?>
                      <span class="rank-num"><?php echo htmlspecialchars((string)$place); ?>.</span>
                      <a class="r-link" href="/athlete/<?php echo urlencode($r['iofId']); ?>">
                        <span class="r-name"><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></span>
                      </a><span class="r-points"><?php echo htmlspecialchars((string)($r['totalPoints'] ?? $r['points'] ?? 0)); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ol>
                <div class="full-table">
                  <a class="full-table-link" href="/discipline/<?php echo urlencode($discipline); ?>?group=<?php echo urlencode($groupKey); ?>&amp;year=<?php echo urlencode((string)$viewData['year']); ?>">Täielik tabel</a>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
</body>

</html>