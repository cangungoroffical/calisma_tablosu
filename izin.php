<?php
session_start(); 
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dataFile = 'data.json';
$schedule = [];

$correctPassword = "1234sifre";
$isPdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;

if (!$isPdf && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
    if ($_POST['password'] === $correctPassword) {
        $_SESSION['authenticated'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('Hatalı şifre!')</script>";
    }
}

if (file_exists($dataFile)) {
    $jsonData = file_get_contents($dataFile);
    $schedule = json_decode($jsonData, true);
}

$readonly = !isset($_SESSION['authenticated']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save']) && !$readonly) {
    $newSchedule = [];

    for ($i = 1; $i <= 40; $i++) {
        $name = $_POST['name_' . $i] ?? '';
        $position = $_POST['position_' . $i] ?? '';
        $shifts = [];

        for ($j = 1; $j <= 7; $j++) {
            $shifts[] = $_POST['shift_' . $j . '_' . $i] ?? '';
        }

        if ($name || $position || !$readonly) {
            $newSchedule[] = [
                'name' => $name,
                'position' => $position,
                'shifts' => $shifts
            ];
        }
    }

    $jsonData = json_encode($newSchedule, JSON_PRETTY_PRINT);
    if ($jsonData === false) {
        echo "JSON encode hatası: " . json_last_error_msg();
        exit;
    }

    $writeResult = file_put_contents($dataFile, $jsonData);
    if ($writeResult === false) {
        echo "Veri dosyaya kaydedilemedi.";
        exit;
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

echo "<h2 style='text-align: center;'>Steward Personeli Haftalık Çalışma Programı</h2>";

if (!$isPdf && $readonly) {
    echo '<form method="post">
            <h2>Tabloyu düzenlemek için şifre giriniz</h2>
            <input type="password" name="password">
            <input type="submit" value="Giriş Yap">
          </form>';
}

echo '<form method="post">
        <table border="1" style="margin: auto; text-align: center;">
            <thead>
                <tr>
                    <th>Ad Soyad</th><th>Pozisyon</th>
                    <th>Pazartesi</th><th>Salı</th><th>Çarşamba</th>
                    <th>Perşembe</th><th>Cuma</th><th>Cumartesi</th><th>Pazar</th>
                </tr>
            </thead>
            <tbody>';

for ($i = 1; $i <= 40; $i++) {
    $dataIndex = $i - 1;
    $name = $schedule[$dataIndex]['name'] ?? '';
    $position = $schedule[$dataIndex]['position'] ?? '';
    $shifts = $schedule[$dataIndex]['shifts'] ?? array_fill(0, 7, '');

    if (!$name && !$position && $readonly) {
        continue;
    }

    echo "<tr>
            <td><input type='text' name='name_$i' value='" . htmlspecialchars($name) . "' " . ($readonly ? "readonly" : "") . "></td>
            <td><select name='position_$i' " . ($readonly ? "disabled" : "") . ">";

    $positions = [
        '' => '',
        'Steward Chief' => 'Steward Chief',
        'Asst. Steward Chief' => 'Asst. Steward Chief',
        'Supervisor' => 'Supervisor',
        'Formen' => 'Formen',
        'Kazan' => 'Kazan',
        'Steward' => 'Steward',
        'Steward Müdürü' => 'Steward Müdürü',
        'Joker' => 'Joker',
        'Joker Chief' => 'Joker Chief'
    ];

    foreach ($positions as $value => $label) {
        $selected = ($value == $position) ? 'selected' : '';
        echo "<option value='$value' $selected>$label</option>";
    }

    echo "</select></td>";

    for ($j = 1; $j <= 7; $j++) {
        $shiftIndex = $j - 1;
        $currentShift = $shifts[$shiftIndex] ?? '';
        echo "<td><select name='shift_{$j}_{$i}' " . ($readonly ? "disabled" : "") . ">";

        $shiftOptions = [
            '' => '',
            '08:00-16:00' => '08:00-16:00',
            '16:00-00:00' => '16:00-00:00',
            '00:00-08:00' => '00:00-08:00',
            'OFF' => 'OFF',
            'AFK' => 'AFK'
        ];

        foreach ($shiftOptions as $value => $label) {
            $selected = ($value == $currentShift) ? 'selected' : '';
            echo "<option value='$value' $selected>$label</option>";
        }

        echo "</select></td>";
    }

    echo "</tr>";
}

echo '</tbody></table><br>';

if (!$isPdf && !$readonly) {
    echo '<input type="submit" name="save" value="Kaydet">
          <a href="?logout=true"><button type="button">Çıkış Yap</button></a>';
}

// Açıklamalar
echo '<h4>Vardiya Açıklamaları:</h4>
<ul>
<li><strong>OFF:</strong> O gün izinli.</li>
<li><strong>AFK:</strong> Görev başında değil.</li>
</ul>';

echo '<h4>Pozisyon Açıklamaları:</h4>
<ul>
<li><strong>Steward Chief:</strong> Steward departmanının en yetkili kişisidir.</li>
<li><strong>Asst. Steward Chief:</strong> Steward Chief’in yardımcısıdır.</li>
<li><strong>Supervisor:</strong> Günlük operasyonları denetler.</li>
<li><strong>Formen:</strong> Mutfak ve yıkama alanlarını yönetir.</li>
<li><strong>Kazan:</strong> Mutfak kazanlarını temizler.</li>
<li><strong>Steward:</strong> Genel mutfak hijyeninden sorumludur.</li>

</ul>';

if (!$isPdf) {
    echo '<br><br><a href="?pdf=1" target="_blank"><button type="button">PDF Göster</button></a>';
}

echo '</form>';

ob_end_flush();
?>
