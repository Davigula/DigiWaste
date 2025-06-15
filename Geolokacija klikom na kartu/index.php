<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="UTF-8" />
  <title>GeoTag Otpad</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    body, html { margin: 0; padding: 0; height: 100%; }
    #map { height: 100vh; }
    #controls {
      position: absolute;
      top: 10px;
      left: 40px;
      z-index: 1000;
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
      max-width: 300px;
    }
    img.thumbnail {
      max-width: 100%;
      max-height: 400px;
      cursor: pointer;
    }
    select {
      width: 100%;
      margin-bottom: 8px;
    }
    #imageModal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.8);
      justify-content: center;
      align-items: center;
    }
    #imageModal img {
      max-width: 90%;
      max-height: 90%;
      border: 4px solid white;
      border-radius: 10px;
    }
    #imageModal:target {
      display: flex;
    }
    #closeModal {
      position: absolute;
      top: 20px;
      right: 30px;
      color: white;
      font-size: 40px;
      cursor: pointer;
    }
  </style>
</head>
<body>
	<?php
	session_start();
	echo "path".is_writable(session_save_path());
	if (!is_writable(session_save_path())) {
		echo 'Session path "'.session_save_path().'" is not writable for PHP!'; 
	}
	var_dump($_SESSION);
	//phpinfo();
	$prijavljen = isset($_SESSION['google_email']);
	var_dump($_SESSION);
	?>

	<div id="controls">
	  <h3 style="margin-top: 0; color: #333;">GeoTag Otpad</h3>
	  
	  <?php if ($prijavljen): ?>
		<p style="color: green; font-weight: bold;">✓ Prijavljeni ste</p>
		<p><a href="dodaj.php" style="background-color:#28a745; color:white; text-decoration:none; padding:8px 16px; border-radius:5px; font-weight:bold;">
		  Dodaj novi otpad
		</a></p>
	  <?php else: ?>
		<p style="color: #666;">Prijavite se za dodavanje otpada</p>
	  <?php endif; ?>

	  <hr>

	  <label for="filter" style="color: green; font-weight: bold;">Prikaz po kategoriji:</label>
	  <select id="filter">
		<option value="sve">Sve kategorije</option>
		<option value="plastika">Plastika</option>
		<option value="staklo">Staklo</option>
		<option value="metal">Metal</option>
		<option value="elektronika">Elektronika</option>
		<option value="opasni">Opasni otpad</option>
		<option value="glomazni">Glomazni otpad</option>
	  </select>

	  <hr>
	  
	  <?php if (!$prijavljen): ?>
	  <div style="margin-top:10px; text-align:center;">
		<button onclick="window.location.href='google-login/login.php'" style="background-color:#4285F4; color:white; border:none; padding:10px 20px; border-radius:5px; font-weight:bold; cursor:pointer;">
		  Prijava putem Google računa
		</button>
	  </div>
	  <?php else: ?>
	  <div style="margin-top:10px; text-align:center;">
		<button onclick="window.location.href='google-login/logout.php'" style="background-color:#dc3545; color:white; border:none; padding:8px 16px; border-radius:5px; font-weight:bold; cursor:pointer;">
		  Odjava
		</button>
	  </div>
	  <?php endif; ?>
	</div>

  <div id="map"></div>

  <div id="imageModal">
    <span id="closeModal" onclick="closeModal()">&times;</span>
    <img id="modalImage" src="" alt="Povećana slika" />
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const map = L.map("map", {
      center: [44.11, 15.23],
      zoom: 10
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap contributors"
    }).addTo(map);

    const allMarkers = [];

    function addMarker(lat, lng, imageUrl, description, category, created_at) {
      const popup = `
        <strong>${category.toUpperCase()}</strong><br>
        ${description}<br>
        <small>${new Date(created_at).toLocaleString()}</small><br>
        <img src="${imageUrl}" class="thumbnail" onclick="openModal('${imageUrl}')" />
      `;
      const marker = L.marker([lat, lng]).addTo(map).bindPopup(popup);
      marker.category = category;
      allMarkers.push(marker);
    }

    function openModal(imageUrl) {
      document.getElementById("modalImage").src = imageUrl;
      document.getElementById("imageModal").style.display = "flex";
    }

    function closeModal() {
      document.getElementById("imageModal").style.display = "none";
      document.getElementById("modalImage").src = "";
    }

    function loadMarkers() {
      console.log("Pokušavam učitati markere...");
      
      fetch("get_markers.php")
        .then(res => {
          console.log("Response status:", res.status);
          if (!res.ok) {
            throw new Error('HTTP error! status: ' + res.status);
          }
          return res.json();
        })
        .then(data => {
          console.log("Učitani podaci:", data);
          
          if (!Array.isArray(data)) {
            console.error("Podaci nisu array:", data);
            return;
          }
          
          if (data.length === 0) {
            console.log("Nema markera u bazi podataka");
            return;
          }
          
          data.forEach(({ lat, lng, image_url, description, category, created_at }) => {
            if (lat && lng) {
              addMarker(lat, lng, image_url, description, category, created_at);
            } else {
              console.warn("Nevažeći marker podaci:", { lat, lng, image_url, description, category });
            }
          });
          
          console.log(`Uspješno učitano ${data.length} markera`);
        })
        .catch(error => {
          console.error("Greška pri učitavanju markera:", error);
          // Ne prikazuj alert korisniku, samo logiraj
        });
    }

    // Filter funkcionalnost
    document.getElementById("filter").addEventListener("change", function () {
      const value = this.value;
      console.log("Filter promijenjen na:", value);
      
      allMarkers.forEach(marker => {
        if (value === "sve" || marker.category === value) {
          if (!map.hasLayer(marker)) {
            map.addLayer(marker);
          }
        } else {
          if (map.hasLayer(marker)) {
            map.removeLayer(marker);
          }
        }
      });
    });

    // Učitaj markere kada se mapa potpuno učita
    map.whenReady(function() {
      console.log("Mapa je spremna, učitavam markere...");
      loadMarkers();
    });
  </script>
</body>
</html>