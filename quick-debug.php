<?php
/**
 * Quick Debug Script for Prefecture Counting Issue
 * Check the current status and identify the exact problem
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

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quick Prefecture Debug</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f0f0f0; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007cba; }
        .error { border-left-color: #dc3545; background: #fff5f5; }
        .success { border-left-color: #28a745; background: #f0fff4; }
        .warning { border-left-color: #ffc107; background: #fffbf0; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; }
        .btn { display: inline-block; padding: 8px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 3px; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>🔍 Quick Prefecture Debug - <?php echo date('H:i:s'); ?></h1>

    <?php
    // 1. Basic WordPress Check
    echo "<div class='box'>";
    echo "<h3>📋 Basic WordPress Check</h3>";
    echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
    echo "<p><strong>Site URL:</strong> " . home_url() . "</p>";
    echo "<p><strong>Current Theme:</strong> " . get_stylesheet() . "</p>";
    echo "</div>";

    // 2. Post Type Check
    echo "<div class='box'>";
    echo "<h3>📰 Grant Post Type Check</h3>";
    
    if (post_type_exists('grant')) {
        echo "<p>✅ Grant post type exists</p>";
        
        $grant_count = wp_count_posts('grant');
        echo "<p><strong>Published:</strong> {$grant_count->publish}</p>";
        echo "<p><strong>Draft:</strong> {$grant_count->draft}</p>";
        echo "<p><strong>Private:</strong> {$grant_count->private}</p>";
        
        if ($grant_count->publish == 0) {
            echo "<div class='error'>";
            echo "<h4>❌ Problem Found: No Published Grant Posts</h4>";
            echo "<p>There are no published grant posts. This is why all counts are 0.</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Grant post type does NOT exist!</p>";
        echo "</div>";
    }
    echo "</div>";

    // 3. Taxonomy Check
    echo "<div class='box'>";
    echo "<h3>🏷️ Prefecture Taxonomy Check</h3>";
    
    if (taxonomy_exists('grant_prefecture')) {
        echo "<p>✅ grant_prefecture taxonomy exists</p>";
        
        $prefecture_terms = get_terms(array(
            'taxonomy' => 'grant_prefecture',
            'hide_empty' => false,
            'number' => 10
        ));
        
        if (!empty($prefecture_terms)) {
            echo "<p><strong>Total Terms:</strong> " . count($prefecture_terms) . "</p>";
            echo "<p><strong>Sample Terms:</strong></p>";
            echo "<ul>";
            foreach (array_slice($prefecture_terms, 0, 5) as $term) {
                echo "<li>{$term->name} (slug: {$term->slug}, count: {$term->count})</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='error'>";
            echo "<p>❌ No prefecture terms found!</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<p>❌ grant_prefecture taxonomy does NOT exist!</p>";
        echo "</div>";
    }
    echo "</div>";

    // 4. Relationship Check
    echo "<div class='box'>";
    echo "<h3>🔗 Post-Taxonomy Relationship Check</h3>";
    
    global $wpdb;
    
    // Check if any grants have prefecture assignments
    $relationships = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        WHERE tt.taxonomy = 'grant_prefecture' 
        AND p.post_type = 'grant' 
        AND p.post_status = 'publish'
    ");
    
    echo "<p><strong>Grant-Prefecture Relationships:</strong> {$relationships}</p>";
    
    if ($relationships == 0) {
        echo "<div class='error'>";
        echo "<h4>❌ Critical Issue: No Relationships Found</h4>";
        echo "<p>Grant posts are not connected to prefecture taxonomies. This is the main problem!</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ Found {$relationships} relationships</p>";
        echo "</div>";
    }
    echo "</div>";

    // 5. Function Check
    echo "<div class='box'>";
    echo "<h3>🛠️ Function Availability Check</h3>";
    
    $functions = [
        'gi_get_all_prefectures',
        'gi_get_prefecture_counts', 
        'gi_ensure_prefecture_terms'
    ];
    
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "<p>✅ {$func} - Available</p>";
        } else {
            echo "<p>❌ {$func} - Missing</p>";
        }
    }
    echo "</div>";

    // 6. Cache Check
    echo "<div class='box'>";
    echo "<h3>💾 Cache Status</h3>";
    
    $old_cache = get_transient('gi_prefecture_counts');
    $new_cache = get_transient('gi_prefecture_counts_v2');
    
    echo "<p><strong>Old Cache (gi_prefecture_counts):</strong> " . ($old_cache !== false ? 'Exists' : 'None') . "</p>";
    echo "<p><strong>New Cache (gi_prefecture_counts_v2):</strong> " . ($new_cache !== false ? 'Exists' : 'None') . "</p>";
    
    if ($new_cache !== false) {
        $sample_counts = array_slice($new_cache, 0, 5, true);
        echo "<p><strong>Sample Cache Data:</strong></p>";
        echo "<div class='code'>" . print_r($sample_counts, true) . "</div>";
    }
    echo "</div>";

    // 7. Quick Fix Actions
    echo "<div class='box'>";
    echo "<h3>🔧 Quick Fix Actions</h3>";
    
    if ($grant_count->publish == 0) {
        echo "<div class='warning'>";
        echo "<h4>Create Sample Grant Posts</h4>";
        echo "<p>Would you like to create sample grant posts for testing?</p>";
        echo "<a href='?action=create_sample' class='btn'>Create 5 Sample Posts</a>";
        echo "</div>";
    } elseif ($relationships == 0) {
        echo "<div class='warning'>";
        echo "<h4>Assign Prefectures to Existing Posts</h4>";
        echo "<p>Your posts exist but need prefecture assignments.</p>";
        echo "<a href='?action=assign_prefectures' class='btn'>Auto-Assign Prefectures</a>";
        echo "</div>";
    }
    
    echo "<a href='?action=clear_cache' class='btn'>Clear All Caches</a>";
    echo "<a href='?action=test_count' class='btn'>Test Count Function</a>";
    echo "</div>";

    // Handle Actions
    if (isset($_GET['action'])) {
        echo "<div class='box success'>";
        echo "<h3>🎯 Action Results</h3>";
        
        switch($_GET['action']) {
            case 'create_sample':
                echo "<p>Creating sample grant posts...</p>";
                
                // Create sample posts with prefecture assignments
                $sample_posts = [
                    ['title' => '東京都IT導入支援助成金', 'prefecture' => 'tokyo'],
                    ['title' => '大阪府ものづくり補助金', 'prefecture' => 'osaka'],
                    ['title' => '愛知県創業支援助成金', 'prefecture' => 'aichi'],
                    ['title' => '神奈川県環境対策補助金', 'prefecture' => 'kanagawa'],
                    ['title' => '福岡県雇用促進助成金', 'prefecture' => 'fukuoka']
                ];
                
                foreach ($sample_posts as $sample) {
                    $post_id = wp_insert_post([
                        'post_title' => $sample['title'],
                        'post_content' => '【テストデータ】この助成金は' . $sample['title'] . 'です。',
                        'post_status' => 'publish',
                        'post_type' => 'grant'
                    ]);
                    
                    if ($post_id) {
                        // Assign prefecture
                        wp_set_post_terms($post_id, $sample['prefecture'], 'grant_prefecture');
                        echo "<p>✅ Created: {$sample['title']} (ID: {$post_id})</p>";
                    }
                }
                
                // Clear cache
                delete_transient('gi_prefecture_counts');
                delete_transient('gi_prefecture_counts_v2');
                echo "<p>✅ Cache cleared</p>";
                break;
                
            case 'assign_prefectures':
                echo "<p>Assigning prefectures to existing posts...</p>";
                
                $posts = get_posts([
                    'post_type' => 'grant',
                    'post_status' => 'publish',
                    'posts_per_page' => -1
                ]);
                
                $prefectures = ['tokyo', 'osaka', 'aichi', 'kanagawa', 'fukuoka'];
                
                foreach ($posts as $i => $post) {
                    $pref = $prefectures[$i % count($prefectures)];
                    wp_set_post_terms($post->ID, $pref, 'grant_prefecture');
                    echo "<p>✅ Assigned {$pref} to: {$post->post_title}</p>";
                }
                
                delete_transient('gi_prefecture_counts');
                delete_transient('gi_prefecture_counts_v2');
                echo "<p>✅ Cache cleared</p>";
                break;
                
            case 'clear_cache':
                delete_transient('gi_prefecture_counts');
                delete_transient('gi_prefecture_counts_v2');
                wp_cache_flush();
                echo "<p>✅ All caches cleared</p>";
                break;
                
            case 'test_count':
                if (function_exists('gi_get_prefecture_counts')) {
                    $counts = gi_get_prefecture_counts(true);
                    $total = array_sum($counts);
                    echo "<p><strong>Test Results:</strong></p>";
                    echo "<p>Total posts found: {$total}</p>";
                    
                    if ($total > 0) {
                        echo "<p>Sample counts:</p>";
                        $sample = array_filter(array_slice($counts, 0, 10, true));
                        echo "<div class='code'>" . print_r($sample, true) . "</div>";
                    }
                } else {
                    echo "<p>❌ Count function not available</p>";
                }
                break;
        }
        echo "</div>";
    }

    // 8. Next Steps
    echo "<div class='box'>";
    echo "<h3>📋 Recommended Next Steps</h3>";
    
    if ($grant_count->publish == 0) {
        echo "<ol>";
        echo "<li>Create sample grant posts using the button above, or</li>";
        echo "<li>Go to WordPress Admin → Grant Posts → Add New to create real posts</li>";
        echo "</ol>";
    } elseif ($relationships == 0) {
        echo "<ol>";
        echo "<li>Use the 'Auto-Assign Prefectures' button above, or</li>";
        echo "<li>Go to WordPress Admin → Grant Posts → Edit each post and assign a prefecture</li>";
        echo "</ol>";
    } else {
        echo "<p>✅ Everything looks good! Check the homepage to see updated counts.</p>";
    }
    echo "</div>";
    ?>

    <div class='box'>
        <h3>🔄 Refresh</h3>
        <a href="?" class="btn">Refresh This Page</a>
        <a href="/" class="btn">Check Homepage</a>
    </div>

    <script>
    // Auto refresh every 60 seconds for testing
    setTimeout(() => {
        console.log('Auto-refreshing in 5 seconds...');
        setTimeout(() => location.reload(), 5000);
    }, 55000);
    </script>
</body>
</html>