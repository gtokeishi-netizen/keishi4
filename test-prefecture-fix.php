<?php
/**
 * Test Prefecture Fix Script
 * Quick test to verify the prefecture counting system
 */

// WordPress environment
if (file_exists('./wp-config.php')) {
    require_once('./wp-config.php');
}
if (file_exists('./wp-load.php')) {
    require_once('./wp-load.php');
}

if (!defined('ABSPATH')) {
    die('WordPress not loaded properly');
}

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prefecture Fix Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; color: #856404; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; color: #0c5460; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .badge { background: #007cba; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .btn { display: inline-block; padding: 8px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 3px; margin: 5px; }
    </style>
</head>
<body>
    <h1>🗾 Prefecture Fix Test Results</h1>
    
    <?php
    echo "<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    // Test 1: Clear cache and force refresh
    echo "<h2>🧹 Step 1: Clearing Cache</h2>";
    delete_transient('gi_prefecture_counts_v2');
    delete_transient('gi_prefecture_counts');
    echo "<div class='success'>✅ Cache cleared successfully</div>";
    
    // Test 2: Check functions exist
    echo "<h2>🔧 Step 2: Function Availability Check</h2>";
    $functions_check = array(
        'gi_get_all_prefectures' => function_exists('gi_get_all_prefectures'),
        'gi_get_prefecture_counts' => function_exists('gi_get_prefecture_counts'),
        'gi_ensure_prefecture_terms' => function_exists('gi_ensure_prefecture_terms'),
        'gi_check_grant_prefecture_assignments' => function_exists('gi_check_grant_prefecture_assignments')
    );
    
    foreach ($functions_check as $func => $exists) {
        $status = $exists ? "✅ Available" : "❌ Missing";
        $class = $exists ? "success" : "error";
        echo "<div class='{$class}'>{$func}: {$status}</div>";
    }
    
    // Test 3: Check basic data
    echo "<h2>📊 Step 3: Basic Data Check</h2>";
    
    $grant_count = wp_count_posts('grant');
    echo "<p><strong>Grant Posts:</strong> {$grant_count->publish} published, {$grant_count->draft} draft</p>";
    
    $prefecture_terms = get_terms(array(
        'taxonomy' => 'grant_prefecture',
        'hide_empty' => false
    ));
    echo "<p><strong>Prefecture Terms:</strong> " . count($prefecture_terms) . " terms</p>";
    
    // Test 4: Ensure prefecture terms exist
    echo "<h2>🏷️ Step 4: Ensuring Prefecture Terms</h2>";
    if (function_exists('gi_ensure_prefecture_terms')) {
        $missing_count = gi_ensure_prefecture_terms();
        if ($missing_count > 0) {
            echo "<div class='warning'>⚠️ Created {$missing_count} missing prefecture terms</div>";
        } else {
            echo "<div class='success'>✅ All prefecture terms exist</div>";
        }
    }
    
    // Test 5: Get new counts
    echo "<h2>🔢 Step 5: Getting Prefecture Counts</h2>";
    
    if (function_exists('gi_get_prefecture_counts')) {
        $prefecture_counts = gi_get_prefecture_counts(true); // Force refresh
        $total_with_posts = count(array_filter($prefecture_counts));
        echo "<div class='info'>📍 {$total_with_posts} prefectures have posts</div>";
        
        if ($total_with_posts > 0) {
            echo "<h3>📋 Prefectures with Posts:</h3>";
            echo "<table>";
            echo "<tr><th>Prefecture</th><th>Count</th><th>Status</th></tr>";
            
            $all_prefectures = function_exists('gi_get_all_prefectures') ? gi_get_all_prefectures() : array();
            
            foreach ($prefecture_counts as $slug => $count) {
                if ($count > 0) {
                    // Find prefecture name
                    $pref_name = $slug;
                    foreach ($all_prefectures as $pref) {
                        if ($pref['slug'] === $slug) {
                            $pref_name = $pref['name'];
                            break;
                        }
                    }
                    
                    echo "<tr>";
                    echo "<td><strong>{$pref_name}</strong></td>";
                    echo "<td><span class='badge'>{$count}</span></td>";
                    echo "<td>✅ Active</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ No prefectures have posts. You need to assign prefecture taxonomies to your grant posts.</div>";
        }
    }
    
    // Test 6: Assignment statistics
    echo "<h2>📈 Step 6: Assignment Statistics</h2>";
    if (function_exists('gi_check_grant_prefecture_assignments')) {
        $stats = gi_check_grant_prefecture_assignments();
        
        if ($stats['total_grants'] > 0) {
            echo "<table>";
            echo "<tr><th>Metric</th><th>Count</th><th>Percentage</th></tr>";
            echo "<tr><td>Total Grants</td><td>{$stats['total_grants']}</td><td>100%</td></tr>";
            echo "<tr><td>Assigned to Prefecture</td><td>{$stats['assigned_grants']}</td><td>{$stats['assignment_ratio']}%</td></tr>";
            echo "<tr><td>Unassigned</td><td>{$stats['unassigned_grants']}</td><td>" . (100 - $stats['assignment_ratio']) . "%</td></tr>";
            echo "</table>";
            
            if ($stats['assignment_ratio'] < 100) {
                echo "<div class='warning'>";
                echo "<h4>⚠️ Action Required</h4>";
                echo "<p>Some grant posts are not assigned to prefectures. To fix this:</p>";
                echo "<ol>";
                echo "<li>Go to WordPress Admin → Grant Posts</li>";
                echo "<li>Edit each post and assign a prefecture taxonomy</li>";
                echo "<li>Or use bulk edit to assign prefectures to multiple posts at once</li>";
                echo "</ol>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>❌ No grant posts found</div>";
        }
    }
    
    // Test 7: Cache status
    echo "<h2>💾 Step 7: Cache Status</h2>";
    $cache_exists = get_transient('gi_prefecture_counts_v2') !== false;
    if ($cache_exists) {
        echo "<div class='success'>✅ New cache created successfully</div>";
    } else {
        echo "<div class='warning'>⚠️ Cache not created (might be normal for first run)</div>";
    }
    
    // Final recommendations
    echo "<h2>🎯 Final Recommendations</h2>";
    
    if (function_exists('gi_check_grant_prefecture_assignments')) {
        $stats = gi_check_grant_prefecture_assignments();
        
        if ($stats['total_grants'] == 0) {
            echo "<div class='error'>";
            echo "<h4>❌ No Grant Posts</h4>";
            echo "<p>You need to create some grant posts first before the prefecture counting will work.</p>";
            echo "</div>";
        } elseif ($stats['assigned_grants'] == 0) {
            echo "<div class='error'>";
            echo "<h4>❌ No Prefecture Assignments</h4>";
            echo "<p>Your grant posts exist but none are assigned to prefecture taxonomies. This is why all counts show 0.</p>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ol>";
            echo "<li>Go to <a href='" . admin_url('edit.php?post_type=grant') . "' class='btn'>Grant Posts Admin</a></li>";
            echo "<li>Edit posts and assign prefecture taxonomies</li>";
            echo "<li>Return to the <a href='" . home_url() . "' class='btn'>front page</a> to see updated counts</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='success'>";
            echo "<h4>✅ Prefecture Counting Fixed!</h4>";
            echo "<p>The prefecture counting system should now work properly. Visit the <a href='" . home_url() . "' class='btn'>front page</a> to see the updated counts.</p>";
            echo "</div>";
        }
    }
    
    echo "<div class='info'>";
    echo "<h4>🔧 Admin Tools</h4>";
    echo "<p>For ongoing management, use the new debug panel:</p>";
    echo "<p><a href='" . admin_url('edit.php?post_type=grant&page=gi-prefecture-debug') . "' class='btn'>Prefecture Debug Panel</a></p>";
    echo "</div>";
    ?>
    
    <hr>
    <p><small>Test completed at <?php echo date('Y-m-d H:i:s'); ?></small></p>
    
    <script>
    // Auto-refresh after 30 seconds for testing
    setTimeout(function() {
        if (confirm('Test completed. Refresh to run again?')) {
            location.reload();
        }
    }, 30000);
    </script>
</body>
</html>