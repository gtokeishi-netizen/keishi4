<?php
/**
 * Create Sample Grant Data for Testing
 * This will create sample grant posts with prefecture assignments
 */

// WordPress environment
if (file_exists('./wp-config.php')) {
    require_once('./wp-config.php');
}
if (file_exists('./wp-load.php')) {
    require_once('./wp-load.php');
}

if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

// Security check - only allow if user is admin or if specific parameter is passed
if (!current_user_can('manage_options') && !isset($_GET['force_create'])) {
    die('Permission denied. Add ?force_create=1 to URL if you want to force creation.');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sample Data Creator</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🏗️ Sample Grant Data Creator</h1>

<?php

// Sample grant data with prefecture assignments
$sample_grants = [
    [
        'title' => '東京都IT導入支援助成金2024',
        'content' => 'IT導入によるデジタル化を支援する東京都の助成金制度です。最大300万円まで支給されます。',
        'prefecture' => 'tokyo',
        'amount' => 3000000,
        'organization' => '東京都産業労働局',
        'deadline' => '2024-12-31'
    ],
    [
        'title' => '大阪府ものづくり中小企業支援補助金',
        'content' => '大阪府内の中小製造業者向けの設備投資支援制度です。',
        'prefecture' => 'osaka',
        'amount' => 5000000,
        'organization' => '大阪府商工労働部',
        'deadline' => '2024-11-30'
    ],
    [
        'title' => '愛知県創業・第二創業支援事業',
        'content' => '愛知県内での新規事業立ち上げを支援する補助金です。',
        'prefecture' => 'aichi',
        'amount' => 2000000,
        'organization' => '愛知県産業労働部',
        'deadline' => '2024-10-31'
    ],
    [
        'title' => '神奈川県環境配慮型設備導入助成金',
        'content' => '環境に配慮した設備導入を支援する神奈川県の制度です。',
        'prefecture' => 'kanagawa',
        'amount' => 1500000,
        'organization' => '神奈川県環境農政局',
        'deadline' => '2024-09-30'
    ],
    [
        'title' => '福岡県雇用促進・人材育成助成金',
        'content' => '福岡県内企業の雇用促進と人材育成を支援する制度です。',
        'prefecture' => 'fukuoka',
        'amount' => 1000000,
        'organization' => '福岡県商工部',
        'deadline' => '2024-12-15'
    ],
    [
        'title' => '北海道農業イノベーション支援事業',
        'content' => '北海道の農業分野でのイノベーション創出を支援します。',
        'prefecture' => 'hokkaido',
        'amount' => 4000000,
        'organization' => '北海道農政部',
        'deadline' => '2024-08-31'
    ],
    [
        'title' => '千葉県デジタル変革推進補助金',
        'content' => '千葉県内企業のDXを推進するための補助金制度です。',
        'prefecture' => 'chiba',
        'amount' => 2500000,
        'organization' => '千葉県商工労働部',
        'deadline' => '2024-11-15'
    ],
    [
        'title' => '埼玉県小規模事業者持続化補助金',
        'content' => '埼玉県の小規模事業者の経営力向上を支援します。',
        'prefecture' => 'saitama',
        'amount' => 500000,
        'organization' => '埼玉県産業労働部',
        'deadline' => '2024-10-15'
    ]
];

// Check if we should create the data
if (isset($_GET['create'])) {
    echo "<div class='info'><h3>📝 Creating Sample Data...</h3></div>";
    
    $created_count = 0;
    $errors = [];
    
    foreach ($sample_grants as $grant) {
        // Create the post
        $post_data = [
            'post_title' => $grant['title'],
            'post_content' => $grant['content'],
            'post_status' => 'publish',
            'post_type' => 'grant',
            'meta_input' => [
                'max_amount_numeric' => $grant['amount'],
                'max_amount' => number_format($grant['amount']) . '円',
                'organization' => $grant['organization'],
                'deadline_date' => $grant['deadline'],
                'application_status' => 'active'
            ]
        ];
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            $errors[] = "Failed to create post: {$grant['title']} - " . $post_id->get_error_message();
            continue;
        }
        
        // Assign prefecture taxonomy
        $term_result = wp_set_post_terms($post_id, $grant['prefecture'], 'grant_prefecture');
        
        if (is_wp_error($term_result)) {
            $errors[] = "Failed to assign prefecture to post {$post_id}: " . $term_result->get_error_message();
        } else {
            echo "<div class='success'>✅ Created: {$grant['title']} (ID: {$post_id}, Prefecture: {$grant['prefecture']})</div>";
            $created_count++;
        }
    }
    
    if (!empty($errors)) {
        echo "<div class='warning'><h4>⚠️ Errors encountered:</h4>";
        foreach ($errors as $error) {
            echo "<p>• {$error}</p>";
        }
        echo "</div>";
    }
    
    echo "<div class='success'><h3>✅ Sample Data Creation Complete</h3>";
    echo "<p><strong>Posts Created:</strong> {$created_count}</p></div>";
    
    // Clear caches
    delete_transient('gi_prefecture_counts');
    delete_transient('gi_prefecture_counts_v2');
    wp_cache_flush();
    
    echo "<div class='info'>🗑️ Caches cleared</div>";
    
    // Test the count function
    if (function_exists('gi_get_prefecture_counts')) {
        echo "<div class='info'><h4>🧪 Testing Count Function</h4>";
        $counts = gi_get_prefecture_counts(true);
        $total_posts = array_sum($counts);
        echo "<p><strong>Total posts found by count function:</strong> {$total_posts}</p>";
        
        if ($total_posts > 0) {
            echo "<p><strong>Prefectures with posts:</strong></p>";
            foreach ($counts as $slug => $count) {
                if ($count > 0) {
                    echo "<p>• {$slug}: {$count}</p>";
                }
            }
        }
        echo "</div>";
    }
    
} else {
    // Show information and creation form
    echo "<div class='info'>";
    echo "<h3>📋 About This Tool</h3>";
    echo "<p>This tool will create {count($sample_grants)} sample grant posts with prefecture assignments for testing the counting system.</p>";
    echo "<p>Each post will be assigned to a different prefecture to test the regional counting feature.</p>";
    echo "</div>";
    
    // Check current state
    $current_grants = wp_count_posts('grant');
    echo "<div class='info'>";
    echo "<h4>📊 Current State</h4>";
    echo "<p><strong>Existing Grant Posts:</strong> {$current_grants->publish} published, {$current_grants->draft} draft</p>";
    
    // Check prefecture terms
    if (taxonomy_exists('grant_prefecture')) {
        $prefecture_terms = get_terms(['taxonomy' => 'grant_prefecture', 'hide_empty' => false]);
        echo "<p><strong>Prefecture Terms:</strong> " . count($prefecture_terms) . " terms exist</p>";
    } else {
        echo "<p><strong>Prefecture Taxonomy:</strong> ❌ Not found!</p>";
    }
    echo "</div>";
    
    if ($current_grants->publish > 0) {
        echo "<div class='warning'>";
        echo "<h4>⚠️ Posts Already Exist</h4>";
        echo "<p>You already have {$current_grants->publish} grant posts. Creating sample data will add more posts.</p>";
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>🚀 Ready to Create Sample Data?</h3>";
    echo "<p><a href='?create=1' style='display: inline-block; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;'>Create Sample Grant Posts</a></p>";
    echo "</div>";
}
?>

<div class='info'>
    <h3>🔗 Useful Links</h3>
    <p><a href="quick-debug.php">Quick Debug Tool</a> | <a href="/">Homepage</a> | <a href="/wp-admin/edit.php?post_type=grant">Grant Posts Admin</a></p>
</div>

</body>
</html>