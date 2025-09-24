<?php
/**
 * 直接Excel管理アクセス用ファイル
 * WordPressの権限システムを完全に回避
 */

// WordPress読み込み
require_once('./wp-config.php');
require_once('./wp-load.php');
require_once('./wp-admin/admin.php');

// テーマファイル読み込み
require_once(get_template_directory() . '/inc/excel-import-export.php');
require_once(get_template_directory() . '/inc/admin-customization.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Excel管理 - 直接アクセス</title>
    <link rel="stylesheet" href="<?php echo admin_url('admin.php'); ?>">
    <style>
        body { font-family: Arial; margin: 20px; }
        .wrap { max-width: 1200px; }
        .button { background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 5px; }
        .button:hover { background: #005a87; }
        .notice { background: #fff; border-left: 4px solid #00a0d2; padding: 12px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>🚀 Excel管理 - 直接アクセス</h1>
    <div class="notice">
        <p><strong>このページはWordPressの権限システムを完全にバイパスしています。</strong></p>
        <p>URL: <?php echo home_url('/excel-direct.php'); ?></p>
    </div>

    <?php
    // Excel管理ページの内容を直接呼び出し
    if (function_exists('gi_excel_management_page')) {
        // 出力バッファリングで内容を取得
        ob_start();
        gi_excel_management_page();
        $content = ob_get_clean();
        
        // WordPressの管理画面スタイルを除去して表示
        echo $content;
    } else {
        echo '<div class="notice">';
        echo '<h2>❌ Excel管理機能が読み込まれていません</h2>';
        echo '<p>テーマファイルに問題がある可能性があります。</p>';
        echo '<p>以下を確認してください：</p>';
        echo '<ul>';
        echo '<li>テーマが正しく有効化されているか</li>';
        echo '<li>inc/excel-import-export.php ファイルが存在するか</li>';
        echo '<li>inc/admin-customization.php ファイルが存在するか</li>';
        echo '</ul>';
        echo '</div>';
    }
    ?>

    <div class="notice">
        <h3>🔗 アクセス方法</h3>
        <p>今後は以下のURLに直接アクセスしてください：</p>
        <p><strong><?php echo home_url('/excel-direct.php'); ?></strong></p>
        <p>ブックマークに保存することをお勧めします。</p>
    </div>
</body>
</html>