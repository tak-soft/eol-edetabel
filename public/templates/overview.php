<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>EOL Edetabel</title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:1rem} .leader{border:1px solid #ddd;padding:1rem;margin-bottom:1rem}</style>
</head>
<body>
  <h1>EOL Edetabel — <?php echo htmlspecialchars((string)$viewData['year']); ?></h1>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;">
  <?php foreach ($viewData['overview'] as $discipline => $bySex): ?>
    <div style="border:1px solid #ddd;padding:0.5rem;">
      <h2><?php echo htmlspecialchars($discipline); ?></h2>
      <?php foreach ($bySex as $sex => $rows): ?>
        <h3><?php echo htmlspecialchars($sex); ?></h3>
        <?php if (empty($rows)): ?>
          <p><em>Puuduvad andmed</em></p>
        <?php else: ?>
          <?php $leader = $rows[0]; ?>
          <div style="background:#f7f7f7;padding:0.5rem;margin-bottom:0.5rem;">
            <strong><?php echo htmlspecialchars($leader['firstname'] . ' ' . $leader['lastname']); ?></strong>
            <div>Points: <?php echo htmlspecialchars((string)($leader['totalPoints'] ?? $leader['points'] ?? 0)); ?></div>
            <a href="/athlete/<?php echo urlencode($leader['iofId'] ?? $leader['iofId']); ?>">vaata profiili</a>
          </div>
          <ol>
            <?php foreach (array_slice($rows, 0, 10) as $r): ?>
              <li><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?> — <?php echo htmlspecialchars((string)($r['totalPoints'] ?? $r['points'] ?? 0)); ?> <a href="/athlete/<?php echo urlencode($r['iofId']); ?>">detail</a></li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  </div>
</body>
</html>
