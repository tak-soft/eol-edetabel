<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>EOL Edetabel - Overview</title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:1rem} .leader{border:1px solid #ddd;padding:1rem;margin-bottom:1rem}</style>
</head>
<body>
  <h1>EOL Edetabel — Overview</h1>

  <?php $leader = $viewData['rankings'][0] ?? null; ?>
  <?php if ($leader): ?>
    <section class="leader">
      <h2>Leader: <?php echo htmlspecialchars($leader['firstname'] . ' ' . $leader['lastname']); ?></h2>
      <p>Points: <?php echo htmlspecialchars((string)$leader['points']); ?></p>
    </section>
  <?php endif; ?>

  <section>
    <h3>Top 10</h3>
    <ol>
      <?php foreach (array_slice($viewData['rankings'], 0, 10) as $r): ?>
        <li>
          <?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?> — <?php echo htmlspecialchars((string)$r['points']); ?>
          <a href="/athlete/<?php echo urlencode($r['iofId']); ?>">details</a>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
</body>
</html>
