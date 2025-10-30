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

  <header class="site-header">
    <div class="site-header__inner">
      <h1 class="site-logo" role="banner">
        <a class="site-logo__link" href="https://orienteerumine.ee" rel="home"><img src="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/img/eol-new-logo.svg" alt="Estonian Orienteering Federation" height="30"><span class="sr-only">Estonian Orienteering Federation</span></a>
      </h1>
    </div>
  </header>


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
      $selected = $value == $viewData['year'] ? ' current-menu-item' : '';
      echo '<a class="menu-item menu-item-type-post_type menu-item-object-page menu-item-3707' . $selected . '" hreflang="et" href="/?year=' . $value . '">' . $value . '</a>';
    }
    ?>
  </nav>
  <div class="container-xl disciplines-grid">
    <?php foreach ($viewData['overview'] as $discipline => $bygroup): ?>
      <section class="discipline-card edetabel-card">
        <h3 class="scoreboard-list-subtitle"><?php echo htmlspecialchars($disciplineNames[$discipline] ?? $discipline); ?></h2>
          <div class="discipline-grid">
            <?php foreach (['WOMEN', 'MEN'] as $groupKey): ?>
              <?php $rows = $bygroup[$groupKey] ?? []; ?>
              <div class="group-column">
                <h3 class="group-title"><?php echo $groups[$groupKey]; ?></h3>
                <?php if (empty($rows)): ?>
                  <p class="no-data"><em>Tulemused puuduvad</em></p>
                <?php else: ?>
                  <div class="scoreboard-list">
                    <?php $leader = $rows[0]; ?>
                    <div class="scoreboard-list-item leader">
                      <div class="scoreboard-list-item__col-place"><?php $place = $leader['place'] ?? "-";
                                                                    echo (string)$place;  ?></div>
                      <div class="scoreboard-list-item__col-name">
                        <a href="/athlete/<?php echo urlencode($leader['iofId']); ?>">
                          <span class="r-name"><?php echo htmlspecialchars($leader['firstname'] . ' ' . $leader['lastname']); ?></span>
                        </a>
                      </div>
                      <div class="scoreboard-list-item__col-points"><span class="sum"><?php echo htmlspecialchars((string)($leader['totalPoints'] ?? 0)); ?></span> </div>
                    </div>

                    <?php foreach (array_slice($rows, 1, 10) as $idx => $r): ?>
                      <div class="scoreboard-list-item">
                        <div class="scoreboard-list-item__col-place"><?php $place = $r['place'] ?? "-";
                                                                      echo (string)$place;  ?></div>
                        <div class="scoreboard-list-item__col-name">
                          <a href="/athlete/<?php echo urlencode($r['iofId']); ?>">
                            <span class="r-name"><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></span>
                          </a>
                        </div>
                        <div class="scoreboard-list-item__col-points"><span class="sum"><?php echo htmlspecialchars((string)($r['totalPoints'] ?? $r['points'] ?? 0)); ?></span> </div>
                      </div>
                    <?php endforeach; ?>
                  </div>

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