<?php
function intTimeToString(?int $timeInSeconds): string
{
  if ($timeInSeconds === null) {
    return '';
  }
  // Eventoris võivad olla 1 sekundilised ajad KO võistlusetel
  if ($timeInSeconds < 11 || $timeInSeconds >= 10000000) {
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
  <title>Edetabeli tulemused:
    <?php echo htmlspecialchars($viewData['athlete']['firstname'] . ' ' . $viewData['athlete']['lastname']); ?>
  </title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
  <link rel="stylesheet" href="/assets/pp-app-theme-overrides.css">
</head>

<body>
  <header class="site-header">
    <div class="site-header__inner">
      <h1 class="site-logo" role="banner">
        <a class="site-logo__link" href="https://orienteerumine.ee" rel="home"><img src="/assets/eol-logo.png"
            alt="Eesti Orienteerumisliit" height="50"><span class="sr-only">Estonian Orienteering Federation</span></a>
      </h1>
    </div>
  </header>

  <?php if (!$viewData['athlete']): ?>
    <p>Ei leidnud</p>
  <?php else:
    $athleteName = htmlspecialchars($viewData['athlete']['firstname'] . ' ' . $viewData['athlete']['lastname']);
    ?>
    <div class="container-xl">
      <section class="discipline-card edetabel-card">
        <div class="athlete-data-summary athlete-data-summary--large">
          <h2 class="athlete-data-summary__name"><?php echo $athleteName; ?></h2>
          <?php if ($viewData['athlete']['age'] ?? null): ?>
            <div class="athlete-data-summary-group">
              <p class="athlete-data-summary-text-label athlete-data-summary__dob">
                <?php echo $viewData['athlete']['age']; ?> <span class="label">aastane</span>
              </p>
            </div>
          <?php endif; ?>
          <div class="athlete-data-summary-group athlete-data-summary-group--has-bottom-margin">
            <p class="athlete-data-summary-text-label athlete-data-summary__code"
              data-athlete-id="<?php echo $viewData['athlete']['eolKood']; ?>">
              <?php echo $viewData['athlete']['eolKood']; ?> <span class="label">EOL kood</span>
            </p>
            <p class="athlete-data-summary-text-label athlete-data-summary__code"
              data-athlete-id="<?php echo $viewData['iofId']; ?>">
              <?php echo $viewData['iofId']; ?> <span class="label">IOF kood</span>
            </p>
          </div>

          <p class="athlete-data-summary-text-label athlete-data-summary__club">
            <?php echo $viewData['athlete']['clubname']; ?>
          </p>
        <nav class="athlete-data-summary-links">
          <!-- Lingid eemaldatud, kas neid saab andmebaasist kätte? -->
          </nav>
          <?php if ($viewData['athlete']['photoUrl'] ?? null): ?>
            <figure class="athlete-data-summary-photo">
              <img src="<?php echo $viewData['athlete']['photoUrl'] ?>" alt="<?php echo $athleteName; ?>">
            </figure>
          <?php endif; ?>
        </div>
        <div class="discipline-card edetabel-card">
          <ol class="top-list athlete-results-list">
            <?php foreach ($viewData['events'] ?? [] as $e): ?>
              <li>
                <div class="athlete-result-item <?php echo !empty($e['isSelected']) ? 'athlete-result-item--selected' : ''; ?>">
                  <div class="athlete-result-layout">
                    <img class="athlete-result-discipline-icon"
                      src="/assets/<?php echo htmlspecialchars($disciplineIcons[$e['alatunnus'] ?? ''] ?? ''); ?>"
                      alt="<?php echo htmlspecialchars($disciplineNames[$e['alatunnus'] ?? ''] ?? ''); ?>">
                    <div class="athlete-result-content">
                      <div class="athlete-result-title">
                        <?php if (!empty($e['eventorId'])): ?>
                          <?php $iofEventUrl = 'https://ranking.orienteering.org/ResultsView?event=' . urlencode($e['eventorId']) . '&person=' . urlencode($viewData['iofId']) . '&ohow=' . urlencode($e['alatunnus'] ?? ''); ?>
                          <a href="<?php echo htmlspecialchars($iofEventUrl); ?>" target="_iof"
                            rel="noopener noreferrer"><?php echo htmlspecialchars($e['name'] ?? ''); ?></a>
                        <?php else: ?>
                          <?php echo htmlspecialchars($e['name'] ?? ''); ?>
                        <?php endif; ?>
                      </div>
                      <div class="athlete-result-details">
                        <?php echo htmlspecialchars($e['date'] ?? ''); ?> — tulemus:
                        <?php echo intTimeToString($e['result']); ?> koht:
                        <?php echo htmlspecialchars((string) ($e['place'] ?? '')); ?> ala:
                        <?php echo $disciplineNames[$e['alatunnus'] ?? '']; ?>
                        <?php if (!empty($e['isSelected'])): ?>
                          <span class="event-points">jooksvas edetabelis</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <div class="summ">
                    <?php echo htmlspecialchars((string) ($e['points'] ?? '0')); ?>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>

      </section>
    </div>
    </div>
  <?php endif; ?>
  <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>