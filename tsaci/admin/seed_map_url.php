<?php
/**
 * Seed Map URL to Database
 * This script updates the contact page map embed URL
 * 
 * Access this file via browser: http://localhost/tupi_supreme/tsaci/admin/seed_map_url.php
 */

// Allow direct access for seeding
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

// The new Google Maps embed URL
$map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7703916584064!2d124.9847734749907!3d6.293877493695194!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f78ee4e9ab4bd5%3A0xa78774472d2fc69d!2sTupi%20Supreme%20Activated%20Carbon%2C%20Inc.!5e0!3m2!1sen!2sph!4v1764687549707!5m2!1sen!2sph';

$message = '';
$success = false;

try {
    $db = getDB();
    
    // Check if record exists
    $stmt = $db->prepare("SELECT id FROM page_content WHERE page_name = 'contact' AND section_name = 'map_embed_url'");
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    
    if ($exists) {
        // Update existing record
        $stmt = $db->prepare("UPDATE page_content SET content = ?, is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE page_name = 'contact' AND section_name = 'map_embed_url'");
        $stmt->bind_param("s", $map_url);
        
        if ($stmt->execute()) {
            $message = "✅ Map embed URL updated successfully!";
            $success = true;
        } else {
            $message = "❌ Error updating: " . $stmt->error;
        }
    } else {
        // Insert new record
        $stmt = $db->prepare("INSERT INTO page_content (page_name, section_name, content_type, content, is_active) VALUES ('contact', 'map_embed_url', 'text', ?, 1)");
        $stmt->bind_param("s", $map_url);
        
        if ($stmt->execute()) {
            $message = "✅ Map embed URL inserted successfully!";
            $success = true;
        } else {
            $message = "❌ Error inserting: " . $stmt->error;
        }
    }
    
    $stmt->close();
    
    // Verify the update
    if ($success) {
        $stmt = $db->prepare("SELECT content FROM page_content WHERE page_name = 'contact' AND section_name = 'map_embed_url'");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $message .= "\n\nCurrent URL in database:\n" . $row['content'];
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    $message = "❌ Error: " . $e->getMessage();
}

// Output result as HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Map URL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Map URL Seeding Result</h1>
        
        <div class="mb-6 p-4 rounded <?php echo $success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <pre class="whitespace-pre-wrap"><?php echo htmlspecialchars($message); ?></pre>
        </div>
        
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                <p class="text-blue-800 mb-2"><strong>✅ Success!</strong></p>
                <p class="text-blue-700">The map URL has been seeded to the database.</p>
            </div>
            
            <div class="flex gap-4">
                <a href="../contact.php" target="_blank" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    View Contact Page
                </a>
                <a href="get_map_embed.php" class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    Admin Map Settings
                </a>
            </div>
        <?php else: ?>
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-yellow-800"><strong>⚠️ Note:</strong> If you see a database connection error, make sure:</p>
                <ul class="list-disc list-inside mt-2 text-yellow-700">
                    <li>XAMPP MySQL is running</li>
                    <li>The database 'tsaci_cms' exists</li>
                    <li>You can also run the SQL file directly: <code class="bg-yellow-100 px-2 py-1 rounded">seed_contact_map_data.sql</code></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

