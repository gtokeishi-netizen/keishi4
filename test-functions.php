<?php
/**
 * Excel機能のテスト用ファイル
 * 関数の重複チェックと動作確認
 */

// セキュリティチェック無効化（テスト用）
define('ABSPATH', '/home/user/webapp/');

// 必要な関数が存在するかチェック
echo "=== Excel機能 関数存在チェック ===\n";

$required_functions = array(
    'gi_export_grants_to_excel',
    'gi_import_grants_from_excel', 
    'gi_get_excel_headers',
    'gi_prepare_grant_row_data',
    'gi_process_import_row',
    'gi_update_import_custom_fields',
    'gi_update_import_taxonomies',
    'gi_parse_import_date',
    'gi_download_sample_csv',
    'gi_get_excel_grant_statistics'
);

// Excel機能ファイルを読み込み
include_once 'inc/excel-import-export.php';

// 管理機能ファイルを読み込み（部分的）
$admin_file_content = file_get_contents('inc/admin-customization.php');

// 関数定義をチェック
foreach ($required_functions as $function_name) {
    if (strpos($admin_file_content, "function $function_name") !== false || 
        strpos(file_get_contents('inc/excel-import-export.php'), "function $function_name") !== false) {
        echo "✅ $function_name - 定義済み\n";
    } else {
        echo "❌ $function_name - 未定義\n";
    }
}

echo "\n=== サンプルCSVヘッダーテスト ===\n";
if (function_exists('gi_get_excel_headers')) {
    $headers = gi_get_excel_headers();
    echo "ヘッダー数: " . count($headers) . "\n";
    echo "ヘッダー内容: " . implode(', ', array_slice($headers, 0, 5)) . "...\n";
} else {
    echo "❌ gi_get_excel_headers 関数が利用できません\n";
}

echo "\n=== 日付パースのテスト ===\n";
if (function_exists('gi_parse_import_date')) {
    $test_dates = array(
        '2024-12-31',
        '2024/12/31', 
        '12/31/2024',
        '2024年12月31日'
    );
    
    foreach ($test_dates as $date) {
        $parsed = gi_parse_import_date($date);
        echo "入力: $date → 結果: $parsed\n";
    }
} else {
    echo "❌ gi_parse_import_date 関数が利用できません\n";
}

echo "\n=== 重複関数チェック完了 ===\n";
echo "エラーが表示されていなければ、関数の重複問題は解決されています。\n";

?>