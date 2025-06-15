<?php
// Povezivanje s bazom
$host = "localhost";
$dbname = "irkhr_digiwaste";
$username = "irkhr_david";
$password = "david1711#";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Greška u povezivanju s bazom: " . $conn->connect_error);
}

// Dohvati sve markere
$result = $conn->query("SELECT * FROM markers ORDER BY created_at DESC");

$markers = [];
while ($row = $result->fetch_assoc()) {
    $markers[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="UTF-8" />
  <title>Admin – GeoTag Otpad</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background-color: #e3e3e3; }
    img { max-height: 100px; }
    select, button {
      margin-top: 10px;
      padding: 6px 10px;
      font-size: 1em;
    }
  </style>
</head>
<body>
  <h2>Admin panel – Evidencija lokacija otpada</h2>

  <label for="categoryFilter">Filtriraj po vrsti otpada:</label>
  <select id="categoryFilter">
    <option value="sve">Sve</option>
    <option value="elektronički">Elektronički</option>
    <option value="građevinski">Građevinski</option>
    <option value="komunalni">Komunalni</option>
    <option value="opasni">Opasni</option>
  </select>

  <button onclick="exportTableToExcel('dataTable', 'otpadi_lokacije')">💾 Izvezi u Excel</button>

  <table id="dataTable">
    <thead>
      <tr>
        <th>Slika</th>
        <th>Vrsta otpada</th>
        <th>Opis</th>
        <th>Lokacija</th>
        <th>Datum</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($markers as $m): ?>
        <tr data-category="<?= htmlspecialchars($m['category']) ?>">
          <td><img src="<?= htmlspecialchars($m['image_url']) ?>" alt="slika" /></td>
          <td><?= htmlspecialchars($m['category']) ?></td>
          <td><?= nl2br(htmlspecialchars($m['description'])) ?></td>
          <td>
            <a href="https://www.google.com/maps?q=<?= $m['lat'] ?>,<?= $m['lng'] ?>" target="_blank">
              <?= round($m['lat'], 5) ?>, <?= round($m['lng'], 5) ?>
            </a>
          </td>
          <td><?= date('d.m.Y H:i', strtotime($m['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <script>
    const filterSelect = document.getElementById('categoryFilter');
    filterSelect.addEventListener('change', () => {
      const value = filterSelect.value;
      document.querySelectorAll('#dataTable tbody tr').forEach(row => {
        row.style.display = (value === 'sve' || row.dataset.category === value) ? '' : 'none';
      });
    });

    function exportTableToExcel(tableID, filename = '') {
      const downloadLink = document.createElement("a");
      const dataType = 'application/vnd.ms-excel';
      const tableSelect = document.getElementById(tableID);
      const tableClone = tableSelect.cloneNode(true);

      [...tableClone.querySelectorAll("tbody tr")].forEach(row => {
        if (row.style.display === "none") {
          row.remove();
          return;
        }

        const imgCell = row.cells[0];
        const img = imgCell.querySelector('img');
        if (img) {
          imgCell.innerText = img.src;
        }
      });

      const tableHTML = tableClone.outerHTML.replace(/ /g, '%20');
      filename = filename ? filename + '.xls' : 'otpadi_lokacije.xls';

      downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
      downloadLink.download = filename;
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }
  </script>
</body>
</html>
