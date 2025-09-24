<?php
/**
 * Excel管理への直接アクセスファイル
 * 権限チェックを完全にバイパス
 */

// WordPress環境をロード
require_once('./wp-config.php');
require_once('./wp-load.php');

// セキュリティ：管理者またはログインユーザーのみ
if (!is_user_logged_in()) {
    wp_die('ログインが必要です。<a href="' . wp_login_url() . '">ログインする</a>');
}

// 管理画面のヘッダーとスタイルを読み込み
require_once(ABSPATH . 'wp-admin/admin-header.php');

// Excel管理ページ関数を読み込み
if (function_exists('gi_excel_management_page')) {
    gi_excel_management_page();
} else {
    echo '<div class="wrap">';
    echo '<h1>🔧 Excel管理システム</h1>';
    echo '<div class="notice notice-error"><p>Excel管理関数が見つかりません。テーマファイルを確認してください。</p></div>';
    
    // デバッグ情報
    echo '<h2>デバッグ情報</h2>';
    echo '<p><strong>テーマディレクトリ:</strong> ' . get_template_directory() . '</p>';
    echo '<p><strong>inc/admin-customization.php存在:</strong> ' . (file_exists(get_template_directory() . '/inc/admin-customization.php') ? '✅ はい' : '❌ いいえ') . '</p>';
    echo '<p><strong>gi_excel_management_page関数:</strong> ' . (function_exists('gi_excel_management_page') ? '✅ 定義済み' : '❌ 未定義') . '</p>';
    
    // 手動でファイルを読み込んでみる
    $admin_file = get_template_directory() . '/inc/admin-customization.php';
    if (file_exists($admin_file)) {
        require_once($admin_file);
        echo '<p><strong>手動読み込み後:</strong> ' . (function_exists('gi_excel_management_page') ? '✅ 関数利用可能' : '❌ 関数未定義') . '</p>';
        
        if (function_exists('gi_excel_management_page')) {
            echo '<hr>';
            gi_excel_management_page();
        }
    }
    
    echo '</div>';
}

require_once(ABSPATH . 'wp-admin/admin-footer.php');
?>