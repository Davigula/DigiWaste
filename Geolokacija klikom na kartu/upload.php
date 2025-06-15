<?php
// Povezivanje s bazom podataka
$host = "localhost";
$dbname = "irkhr_digiwaste";
$username = "irkhr_david";
$password = "david1711#";

$conn = new mysqli($host, $username, $password, $dbname);

// Provjera konekcije
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Greška u povezivanju s bazom: " . $conn->connect_error]));
}

// Provjera da li je slika poslana
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $imageName = time() . "_" . basename($_FILES['image']['name']);
    $uploadDir = 'uploads/';

    // Kreiraj direktorij ako ne postoji
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadPath = $uploadDir . $imageName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        // Podaci s frontenda
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';  // Npr: "glomazni", "plastični", "mješani"
        $imageUrl = 'https://digi.irk.hr/' . $uploadPath; // Prilagodi ako promijeniš domenu

        // SQL upit za unos lokacije otpada
        $stmt = $conn->prepare("
            INSERT INTO markers (lat, lng, image_url, description, category, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ddsss", $lat, $lng, $imageUrl, $description, $category);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'imageUrl' => $imageUrl,
            'created_at' => date("Y-m-d H:i:s")
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Greška prilikom spremanja slike na server.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Slika nije poslana ili je došlo do greške.'
    ]);
}

$conn->close();
?>
