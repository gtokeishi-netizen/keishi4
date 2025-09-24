<?php
/**
 * Prefecture Counting Debug Script
 * Debug the regional search counting system
 */

// WordPress environment
require_once('./wp-config.php');
require_once('./wp-load.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded properly');
}

echo "<h1>Prefecture Counting Debug Report</h1>\n";
echo "<style>body{font-family:Arial;margin:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>\n";

// 1. Check if grant posts exist
echo "<h2>1. Grant Posts Overview</h2>\n";
$grant_count = wp_count_posts('grant');
echo "<p><strong>Published Grant Posts:</strong> {$grant_count->publish}</p>\n";
echo "<p><strong>Draft Grant Posts:</strong> {$grant_count->draft}</p>\n";
echo "<p><strong>Total Grant Posts:</strong> " . ($grant_count->publish + $grant_count->draft) . "</p>\n";

// 2. Check grant_prefecture taxonomy terms
echo "<h2>2. Prefecture Taxonomy Terms</h2>\n";
$prefecture_terms = get_terms(array(
    'taxonomy' => 'grant_prefecture',
    'hide_empty' => false,
    'orderby' => 'name'
));

if (empty($prefecture_terms)) {
    echo "<p style='color:red;'><strong>ERROR:</strong> No prefecture terms found! The taxonomy might not be properly set up.</p>\n";
} else {
    echo "<p><strong>Total Prefecture Terms:</strong> " . count($prefecture_terms) . "</p>\n";
    echo "<table>\n";
    echo "<tr><th>Term ID</th><th>Name</th><th>Slug</th><th>Count</th></tr>\n";
    foreach ($prefecture_terms as $term) {
        echo "<tr><td>{$term->term_id}</td><td>{$term->name}</td><td>{$term->slug}</td><td>{$term->count}</td></tr>\n";
    }
    echo "</table>\n";
}

// 3. Check if any grant posts have prefecture taxonomy relationships
echo "<h2>3. Grant-Prefecture Relationships</h2>\n";
global $wpdb;

$relationships = $wpdb->get_results("
    SELECT tr.object_id, tr.term_taxonomy_id, tt.term_id, t.name, t.slug 
    FROM {$wpdb->term_relationships} tr
    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
    INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
    WHERE tt.taxonomy = 'grant_prefecture' 
    AND p.post_type = 'grant' 
    AND p.post_status = 'publish'
    LIMIT 20
");

if (empty($relationships)) {
    echo "<p style='color:red;'><strong>ISSUE FOUND:</strong> No published grant posts are linked to any prefecture terms!</p>\n";
    echo "<p>This explains why all counts are 0. Grant posts need to be assigned to prefecture taxonomies.</p>\n";
} else {
    echo "<p><strong>Found Relationships:</strong> " . count($relationships) . " (showing first 20)</p>\n";
    echo "<table>\n";
    echo "<tr><th>Post ID</th><th>Prefecture Name</th><th>Prefecture Slug</th></tr>\n";
    foreach ($relationships as $rel) {
        echo "<tr><td>{$rel->object_id}</td><td>{$rel->name}</td><td>{$rel->slug}</td></tr>\n";
    }
    echo "</table>\n";
}

// 4. Test the current counting function for a few prefectures
echo "<h2>4. Testing Current Count Function</h2>\n";

// Get the prefecture function
if (function_exists('gi_get_all_prefectures')) {
    $all_prefectures = gi_get_all_prefectures();
} else {
    echo "<p style='color:red;'>gi_get_all_prefectures function not found!</p>\n";
    $all_prefectures = array();
}

if (!empty($all_prefectures)) {
    echo "<table>\n";
    echo "<tr><th>Prefecture</th><th>Slug</th><th>Term Exists</th><th>WP_Query Count</th><th>Direct DB Count</th></tr>\n";
    
    $test_prefectures = array_slice($all_prefectures, 0, 10); // Test first 10
    
    foreach ($test_prefectures as $pref) {
        $term = get_term_by('slug', $pref['slug'], 'grant_prefecture');
        $term_exists = $term && !is_wp_error($term) ? 'Yes' : 'No';
        
        // Test WP_Query method (current method)
        if ($term && !is_wp_error($term)) {
            $query = new WP_Query(array(
                'post_type' => 'grant',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => false,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'grant_prefecture',
                        'field' => 'term_id',
                        'terms' => $term->term_id
                    )
                )
            ));
            $wp_query_count = $query->found_posts;
            wp_reset_postdata();
        } else {
            $wp_query_count = 0;
        }
        
        // Direct database count
        if ($term && !is_wp_error($term)) {
            $direct_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE p.post_type = 'grant'
                AND p.post_status = 'publish'
                AND tt.taxonomy = 'grant_prefecture'
                AND tt.term_id = %d
            ", $term->term_id));
        } else {
            $direct_count = 0;
        }
        
        echo "<tr><td>{$pref['name']}</td><td>{$pref['slug']}</td><td>{$term_exists}</td><td>{$wp_query_count}</td><td>{$direct_count}</td></tr>\n";
    }
    echo "</table>\n";
}

// 5. Check current transient cache
echo "<h2>5. Current Cache Status</h2>\n";
$prefecture_counts = get_transient('gi_prefecture_counts');
if (false === $prefecture_counts) {
    echo "<p><strong>Cache Status:</strong> No cache found (transient expired or cleared)</p>\n";
} else {
    echo "<p><strong>Cache Status:</strong> Cache exists with " . count($prefecture_counts) . " entries</p>\n";
    echo "<p><strong>Sample Cache Data:</strong></p>\n";
    echo "<table>\n";
    echo "<tr><th>Prefecture Slug</th><th>Cached Count</th></tr>\n";
    $sample_cache = array_slice($prefecture_counts, 0, 10, true);
    foreach ($sample_cache as $slug => $count) {
        echo "<tr><td>{$slug}</td><td>{$count}</td></tr>\n";
    }
    echo "</table>\n";
}

// 6. Suggest fixes
echo "<h2>6. Recommended Actions</h2>\n";
if (empty($relationships)) {
    echo "<div style='background:#fff3cd;border:1px solid #ffeaa7;padding:15px;margin:10px 0;'>\n";
    echo "<h3>🔧 Primary Issue: No Grant-Prefecture Relationships</h3>\n";
    echo "<p><strong>Problem:</strong> Grant posts are not linked to prefecture taxonomy terms.</p>\n";
    echo "<p><strong>Solutions:</strong></p>\n";
    echo "<ol>\n";
    echo "<li><strong>Manual Assignment:</strong> Go to WordPress admin → Grant posts → Edit posts and assign prefectures</li>\n";
    echo "<li><strong>Bulk Assignment:</strong> Use a script to automatically assign prefectures based on post content or meta fields</li>\n";
    echo "<li><strong>Import Fix:</strong> If using an importer, ensure it's mapping prefecture data correctly</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
}

echo "<div style='background:#d1ecf1;border:1px solid #bee5eb;padding:15px;margin:10px 0;'>\n";
echo "<h3>🔍 Next Steps</h3>\n";
echo "<ol>\n";
echo "<li>Clear the current cache: <code>delete_transient('gi_prefecture_counts');</code></li>\n";
echo "<li>Assign prefecture taxonomies to grant posts</li>\n";
echo "<li>Test the counting system again</li>\n";
echo "<li>Consider improving the counting logic for better performance</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<h2>Debug Complete</h2>\n";
echo "<p>Report generated at: " . date('Y-m-d H:i:s') . "</p>\n";
?>