<?php
function intTimeToString(?int $timeInSeconds): string
{
  if ($timeInSeconds === null) {
    return '';
  }
  if ($timeInSeconds < 0 || $timeInSeconds >= 10000000) {
    return '-';
  }
  $subSeconds = $timeInSeconds % 10;
  $timeInSeconds = intdiv($timeInSeconds, 10);
  $hours = intdiv($timeInSeconds, 3600);
  $minutes = intdiv($timeInSeconds % 3600, 60);
  $seconds = $timeInSeconds % 60;

  if ($hours > 0) {
    return sprintf('%d:%02d:%02d,%d', $hours, $minutes, $seconds, $subSeconds);
  } else {
    return sprintf('%d:%02d,%d', $minutes, $seconds, $subSeconds);
  }
}
$disciplineNames = ['F' => 'Orienteerumisjooks', 'FS' => 'Orienteerumisjooks - Sprint', 'M' => 'Rattaorienteerumine', 'S' => 'Suusaorienteerumine', 'T' => 'Trail'];

?>

<!doctype html>
<html lang="et">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Võistlus tulemused <?php echo htmlspecialchars($viewData['iofId']); ?></title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
  <link rel="stylesheet" href="/assets/pp-app-theme-overrides.css">
</head>

<body>
  <?php if (!$viewData['athlete']): ?>
    <p>Not found</p>
  <?php else: ?>
    <div class="disciplines-grid">
      <section class="discipline-card edetabel-card">
        <h2 class="discipline-title"><?php echo htmlspecialchars($viewData['athlete']['firstname'] . ' ' . $viewData['athlete']['lastname']); ?></h2>

        <div class="discipline-grid">
          <div class="sex-column">
            <div class="leader leader-big">
              <div class="leader-name">IOFid:<?php echo htmlspecialchars($viewData['iofId']); ?></div>
            </div>

            <ol class="top-list compact">
              <?php foreach ($viewData['athlete']['events'] ?? [] as $e): ?>
                <li>
                  <div style="display:flex; align-items:center; gap:0.6rem; width:100%;">
                    <div style="flex:1 1 auto; min-width:0;">
                      <div style="font-size:0.95rem; font-weight:600;">
                        <?php if (!empty($e['eventorId'])): ?>
                          <?php $iofEventUrl = 'https://ranking.orienteering.org/ResultsView?event=' . urlencode($e['eventorId']) . '&person=' . urlencode($viewData['iofId']) . '&ohow=' . urlencode($e['alatunnus'] ?? ''); ?>
                          <a href="<?php echo htmlspecialchars($iofEventUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($e['name'] ?? ''); ?></a>
                        <?php else: ?>
                          <?php echo htmlspecialchars($e['name'] ?? ''); ?>
                        <?php endif; ?>
                      </div>
                      <div style="font-size:0.85rem; color:#666;">
                        <?php echo htmlspecialchars($e['date'] ?? ''); ?> — tulemus: <?php echo intTimeToString($e['result']); ?>, koht: <?php echo htmlspecialchars((string)($e['place'] ?? '')); ?>, ala: <?php echo $disciplineNames[$e['alatunnus'] ?? '']; ?>
                      </div>
                    </div>
                    <div style="min-width:64px; text-align:right; font-weight:600; color:#0b4d80;">
                      <?php echo htmlspecialchars((string)($e['points'] ?? '0')); ?>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ol>
          </div>

        </div>
        <div class="leader-info">
          <div class="leader-links"><a class="profile-link" href="/api/athlete/<?php echo urlencode($viewData['iofId']); ?>">näita JSON</a></div>
        </div>
      </section>
    </div>
  <?php endif; ?>
</body>

</html>