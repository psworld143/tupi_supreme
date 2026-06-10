<?php
/**
 * Address Verification Script
 * This script shows what address is actually stored in the database
 */

require_once 'includes/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Address Verification</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Address Verification</h1>
    
    <div class="section info">
        <h2>1. Contact Info Table (contact_info)</h2>
        <?php
        $addresses = getContactInfo('address');
        if (!empty($addresses)) {
            foreach ($addresses as $addr) {
                echo "<p><strong>Label:</strong> " . htmlspecialchars($addr['label']) . "</p>";
                echo "<p><strong>Value:</strong></p>";
                echo "<pre>" . htmlspecialchars($addr['value']) . "</pre>";
                echo "<p><strong>Display:</strong></p>";
                echo "<div style='border: 1px solid #ccc; padding: 10px; background: white;'>" . nl2br(htmlspecialchars($addr['value'])) . "</div>";
            }
        } else {
            echo "<p style='color: red;'>No address found in contact_info table!</p>";
        }
        ?>
    </div>
    
    <div class="section info">
        <h2>2. Site Settings Table (site_settings)</h2>
        <?php
        $site_address = getSiteSetting('contact_address', 'NOT SET');
        echo "<p><strong>contact_address:</strong></p>";
        echo "<pre>" . htmlspecialchars($site_address) . "</pre>";
        ?>
    </div>
    
    <div class="section info">
        <h2>3. Map Embed URL</h2>
        <?php
        $map_url = getPageContent('contact', 'map_embed_url', 'NOT SET');
        echo "<p><strong>Map URL:</strong></p>";
        echo "<pre style='word-break: break-all;'>" . htmlspecialchars($map_url) . "</pre>";
        
        // Check if it contains New York
        if (stripos($map_url, 'New York') !== false || stripos($map_url, 'Park Row') !== false) {
            echo "<p style='color: red;'><strong>⚠️ WARNING: Map URL contains New York address!</strong></p>";
        } else {
            echo "<p style='color: green;'><strong>✅ Map URL is correct (Tupi location)</strong></p>";
        }
        ?>
    </div>
    
    <div class="section success">
        <h2>4. What Should Be Displayed</h2>
        <p>The "Find Us" section should show:</p>
        <div style='border: 1px solid #ccc; padding: 15px; background: white; margin: 10px 0;'>
            <h3>Visit Us</h3>
            <?php
            if (!empty($addresses)) {
                echo nl2br(htmlspecialchars($addresses[0]['value']));
            } else {
                echo "No address configured";
            }
            ?>
        </div>
    </div>
    
    <div class="section">
        <h2>5. Direct Database Query</h2>
        <?php
        $db = getDB();
        if ($db) {
            $result = $db->query("SELECT * FROM contact_info WHERE type = 'address'");
            if ($result && $result->num_rows > 0) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Type</th><th>Label</th><th>Value</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['label']) . "</td>";
                    echo "<td><pre style='margin:0;'>" . htmlspecialchars($row['value']) . "</pre></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: red;'>No records found!</p>";
            }
        } else {
            echo "<p style='color: red;'>Database connection failed!</p>";
        }
        ?>
    </div>
    
    <p style='margin-top: 30px;'>
        <a href="contact.php">← Back to Contact Page</a> | 
        <a href="contact.php?debug=1">View Contact Page with Debug</a>
    </p>
</body>
</html>

