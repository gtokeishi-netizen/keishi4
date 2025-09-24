<?php
/**
 * 権限デバッグ用ファイル
 * このファイルを一時的にWebサイトのルートに置いて、直接アクセスして権限を確認
 */

// WordPress環境を読み込み
require_once('./wp-config.php');
require_once('./wp-load.php');

echo "<h2>🔍 権限デバッグ情報</h2>";

// 現在のユーザー情報
$current_user = wp_get_current_user();

if ($current_user->ID == 0) {
    echo "<p style='color: red;'>❌ ログインしていません。WordPressにログインしてからこのページにアクセスしてください。</p>";
    echo "<p><a href='/wp-admin/'>WordPress管理画面へ</a></p>";
    exit;
}

echo "<h3>👤 現在のユーザー情報</h3>";
echo "<ul>";
echo "<li><strong>ユーザーID:</strong> " . $current_user->ID . "</li>";
echo "<li><strong>ユーザー名:</strong> " . $current_user->user_login . "</li>";
echo "<li><strong>表示名:</strong> " . $current_user->display_name . "</li>";
echo "<li><strong>メールアドレス:</strong> " . $current_user->user_email . "</li>";
echo "</ul>";

echo "<h3>🏷️ 権限・役割情報</h3>";
echo "<ul>";
echo "<li><strong>役割:</strong> " . implode(', ', $current_user->roles) . "</li>";
echo "<li><strong>ユーザーレベル:</strong> " . ($current_user->user_level ?? '設定なし') . "</li>";
echo "</ul>";

echo "<h3>✅ 個別権限チェック</h3>";
$permissions_to_check = array(
    'read' => 'サイトの表示',
    'upload_files' => 'ファイルアップロード',
    'edit_posts' => '投稿の編集', 
    'publish_posts' => '投稿の公開',
    'edit_others_posts' => '他人の投稿編集',
    'manage_options' => '管理者権限',
    'manage_categories' => 'カテゴリー管理',
    'moderate_comments' => 'コメント管理',
    'activate_plugins' => 'プラグイン有効化',
    'edit_users' => 'ユーザー編集'
);

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>権限</th><th>説明</th><th>状態</th></tr>";

foreach ($permissions_to_check as $permission => $description) {
    $has_permission = current_user_can($permission);
    $status = $has_permission ? '✅ あり' : '❌ なし';
    $color = $has_permission ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td><code>$permission</code></td>";
    echo "<td>$description</td>";
    echo "<td style='color: $color;'><strong>$status</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>📊 投稿タイプ権限チェック</h3>";
echo "<ul>";

// grant投稿タイプが存在するかチェック
if (post_type_exists('grant')) {
    echo "<li>✅ grant投稿タイプは存在します</li>";
    
    $grant_post_type = get_post_type_object('grant');
    echo "<li><strong>capability_type:</strong> " . $grant_post_type->capability_type . "</li>";
    echo "<li><strong>menu表示権限:</strong> " . $grant_post_type->cap->edit_posts . "</li>";
} else {
    echo "<li>❌ grant投稿タイプが見つかりません</li>";
}

echo "</ul>";

echo "<h3>🔧 管理メニュー権限チェック</h3>";
echo "<ul>";

// Excel管理メニューに必要な権限をチェック
$excel_permissions = array(
    'upload_files' => 'Excel管理基本権限',
    'edit_posts' => '投稿編集権限',
    'manage_options' => '管理者権限'
);

foreach ($excel_permissions as $perm => $desc) {
    $has = current_user_can($perm);
    $status = $has ? '✅ OK' : '❌ NG';
    echo "<li><strong>$desc ($perm):</strong> $status</li>";
}

echo "</ul>";

echo "<h3>💡 推奨対処法</h3>";

if (!current_user_can('manage_options')) {
    echo "<div style='background: #fee; padding: 15px; border: 1px solid #f00; border-radius: 5px;'>";
    echo "<h4 style='color: red;'>⚠️ 管理者権限が不足しています</h4>";
    echo "<p>以下の方法で権限を回復してください：</p>";
    echo "<ol>";
    echo "<li><strong>wp-config.phpに追加:</strong><br><code>define('WP_DEBUG', true);</code></li>";
    echo "<li><strong>データベースから直接権限を確認:</strong> wp_users テーブルと wp_usermeta テーブルを確認</li>";
    echo "<li><strong>権限リセット:</strong> プラグインやテーマで権限が変更されている可能性があります</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #efe; padding: 15px; border: 1px solid #0a0; border-radius: 5px;'>";
    echo "<h4 style='color: green;'>✅ 管理者権限は正常です</h4>";
    echo "<p>Excel管理機能にアクセスできるはずです。以下を試してください：</p>";
    echo "<ul>";
    echo "<li><a href='/wp-admin/edit.php?post_type=grant&page=gi-excel-management' target='_blank'>📊 Excel管理（助成金メニュー）</a></li>";
    echo "<li><a href='/wp-admin/tools.php?page=gi-excel-admin' target='_blank'>🔧 Excel助成金管理（ツールメニュー）</a></li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h3>🛠️ 緊急アクセス方法</h3>";
echo "<p>上記のリンクでもアクセスできない場合は、以下のコードをfunctions.phpに一時的に追加してください：</p>";
echo "<textarea rows='10' cols='80' readonly>";
echo "
// 緊急Excel管理アクセス用（一時的）
add_action('admin_menu', function() {
    add_menu_page(
        '緊急Excel管理',
        '緊急Excel管理', 
        'read',  // 最低権限
        'emergency-excel',
        'gi_excel_management_page',
        'dashicons-table-col-after',
        99
    );
}, 999);
";
echo "</textarea>";

echo "<h3>📞 サポート情報</h3>";
echo "<p>この情報をスクリーンショットして、サポートチームにお送りください。</p>";
echo "<p><strong>WordPress バージョン:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>PHP バージョン:</strong> " . phpversion() . "</p>";
echo "<p><strong>現在時刻:</strong> " . current_time('Y-m-d H:i:s') . "</p>";

?>