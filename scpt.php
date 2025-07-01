<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>TikTok Info Scraper (PHP)</title>
    <style>
        body { background: #0e0e0e; color: #00ff88; font-family: monospace; padding: 20px; }
        input, button { padding: 6px; font-size: 1em; margin: 4px; }
        img { max-width: 200px; border: 2px solid #00ff88; margin-top: 10px; }
        .links { margin-top: 10px; }
        a { color: #00ffff; text-decoration: none; }
        hr { border: 1px solid #333; }
    </style>
</head>
<body>
    <h2>🔍 TikTok User Info Scraper (Accurate JSON Method)</h2>
    <form method="GET">
        <label>Enter TikTok Username (without @):</label>
        <input type="text" name="user" required>
        <button type="submit">Scrape</button>
    </form>

<?php
if (isset($_GET['user'])) {
    $username = htmlspecialchars(trim($_GET['user']));
    $url = "https://www.tiktok.com/@$username";

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ]
    ]);

    $html = @file_get_contents($url, false, $context);

    if (!$html) {
        echo "<p>❌ Failed to load TikTok profile. Possibly blocked.</p>";
        exit;
    }

    // Extract TikTok embedded JSON data
    if (preg_match('/<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application\/json">(.+?)<\/script>/', $html, $jsonBlock)) {
        $jsonRaw = html_entity_decode($jsonBlock[1]);
        $data = json_decode($jsonRaw, true);

        $user = $data['__DEFAULT_SCOPE__']['webapp.user-detail']['userInfo']['user'] ?? null;
        $stats = $data['__DEFAULT_SCOPE__']['webapp.user-detail']['userInfo']['stats'] ?? null;

        if (!$user || !$stats) {
            echo "<p>❌ Unable to extract user or stats info.</p>";
            exit;
        }

        echo "<hr><h3>📄 Basic Info</h3>";
        echo "<b>User ID:</b> " . $user['id'] . "<br>";
        echo "<b>Username:</b> " . $user['uniqueId'] . "<br>";
        echo "<b>Nickname:</b> " . $user['nickname'] . "<br>";
        echo "<b>Verified:</b> " . ($user['verified'] ? 'Yes' : 'No') . "<br>";
        echo "<b>Private Account:</b> " . ($user['privateAccount'] ? 'Yes' : 'No') . "<br>";
        echo "<b>Region:</b> " . ($user['region'] ?? 'Unknown') . "<br>";

        echo "<br><b>Followers:</b> " . number_format($stats['followerCount']) . "<br>";
        echo "<b>Following:</b> " . number_format($stats['followingCount']) . "<br>";
        echo "<b>Likes:</b> " . number_format($stats['heartCount']) . "<br>";
        echo "<b>Videos:</b> " . number_format($stats['videoCount']) . "<br>";
        echo "<b>Friends:</b> " . number_format($stats['friendCount']) . "<br>";
        echo "<b>Hearts:</b> " . number_format($stats['heart']) . "<br>";
        echo "<b>Digg Count:</b> " . number_format($stats['diggCount']) . "<br>";

        echo "<hr><h3>🖼️ Profile Picture</h3>";
        $avatar = str_replace('\u002F', '/', $user['avatarLarger']);
        echo "<img src=\"$avatar\" alt=\"Profile Picture\"><br>";

        echo "<hr><h3>📝 Biography</h3>";
        echo nl2br(htmlspecialchars($user['signature']));

        echo "<hr><h3>🔗 Social Links in Bio</h3>";
        $foundLinks = [];

        // Try to extract bio links from raw HTML
        if (preg_match_all('/href="(https:\/\/www\.tiktok\.com\/link\/v2\?[^"]+)"/', $html, $linkMatches)) {
            foreach ($linkMatches[1] as $link) {
                $decoded = urldecode(html_entity_decode($link));
                if (preg_match('/target=([^&]+)/', $decoded, $targetMatch)) {
                    $target = urldecode($targetMatch[1]);
                    if (!in_array($target, $foundLinks)) {
                        $foundLinks[] = $target;
                        echo "🌐 <a href=\"$target\" target=\"_blank\">$target</a><br>";
                    }
                }
            }
        }

        // Emails in bio
        if (preg_match('/[\w\.\-]+@[\w\-]+\.[\w\.\-]+/', $user['signature'], $emailMatch)) {
            echo "<br>📧 Email: " . htmlspecialchars($emailMatch[0]) . "<br>";
        }

        // IG, SC, Telegram from signature
        $signature = $user['signature'];
        if (preg_match('/(?:ig|IG|Insta):?\s*@?([a-zA-Z0-9._]+)/', $signature, $ig)) {
            echo "<br>📸 Instagram: @" . $ig[1];
        }
        if (preg_match('/(?:sc|SC|Snapchat):?\s*@?([a-zA-Z0-9._]+)/', $signature, $sc)) {
            echo "<br>👻 Snapchat: " . $sc[1];
        }
        if (preg_match('/(?:telegram):?\s*@?([a-zA-Z0-9_]+)/i', $signature, $tg)) {
            echo "<br>✈️ Telegram: @" . $tg[1];
        }

        echo "<hr><b>📎 TikTok Profile:</b> <a href=\"https://www.tiktok.com/@$username\" target=\"_blank\">@$username</a><br>";
    } else {
        echo "<p>❌ Could not parse TikTok JSON structure.</p>";
    }
}
?>
</body>
</html>
