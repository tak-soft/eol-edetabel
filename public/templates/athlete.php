<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Athlete <?php echo htmlspecialchars($viewData['iofId']); ?></title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
</head>
<body>
  <h1>Athlete <?php echo htmlspecialchars($viewData['iofId']); ?></h1>
  <?php if (!$viewData['athlete']): ?>
    <p>Not found</p>
  <?php else: ?>
    <h2><?php echo htmlspecialchars($viewData['athlete']['firstname'] . ' ' . $viewData['athlete']['lastname']); ?></h2>
    <table border="1" cellpadding="6" cellspacing="0">
      <thead><tr><th>Date</th><th>Event</th><th>Result</th><th>Place</th><th>Points</th></tr></thead>
      <tbody>
        <?php foreach ($viewData['athlete']['events'] ?? [] as $e): ?>
          <tr>
            <td><?php echo htmlspecialchars($e['date'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($e['name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars((string)($e['result'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($e['place'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($e['points'] ?? '')); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
