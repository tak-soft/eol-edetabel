<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>EOL Edetabel - Discipline <?php echo htmlspecialchars($viewData['code']); ?></title>
  <link rel="stylesheet" href="https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css">
</head>
<body>
  <h1>Discipline: <?php echo htmlspecialchars($viewData['code']); ?></h1>

  <table border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr><th>Place</th><th>Name</th><th>Points</th><th>IOF</th></tr>
    </thead>
    <tbody>
      <?php foreach ($viewData['rankings'] as $r):
        if (($r['discipline'] ?? '') !== $viewData['code']) continue;
      ?>
      <tr>
        <td><?php echo htmlspecialchars((string)$r['place']); ?></td>
        <td><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></td>
        <td><?php echo htmlspecialchars((string)$r['points']); ?></td>
        <td><a href="/athlete/<?php echo urlencode($r['iofId']); ?>"><?php echo htmlspecialchars((string)$r['iofId']); ?></a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
