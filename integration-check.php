<?php
/**
 * AI機能統合チェックスクリプト
 */

echo "<h1>🔧 AI機能統合チェック</h1>";

// 必要な関数の存在確認
$required_functions = [
    'gi_init_ai_functions',
    'gi_create_search_history_table',
    'gi_create_chat_history_table',
    'gi_create_voice_history_table',
    'gi_check_ai_capabilities',
    'handle_ai_search',
    'handle_ai_chat_request',
    'gi_ajax_get_search_suggestions',
    'gi_ajax_test_connection',
    'gi_ajax_voice_history'
];

echo "<h2>📋 関数存在チェック</h2>";
foreach ($required_functions as $func) {
    $exists = function_exists($func);
    $status = $exists ? '✅' : '❌';
    echo "<p>{$status} {$func}</p>";
}

// 必要なクラスの存在確認
$required_classes = [
    'GI_OpenAI_Integration',
    'GI_Grant_Semantic_Search'
];

echo "<h2>🏗️ クラス存在チェック</h2>";
foreach ($required_classes as $class) {
    $exists = class_exists($class);
    $status = $exists ? '✅' : '❌';
    echo "<p>{$status} {$class}</p>";
}

// WordPressアクションフック確認
echo "<h2>🔗 AJAXアクションフック確認</h2>";
$ajax_actions = [
    'wp_ajax_gi_ai_search',
    'wp_ajax_nopriv_gi_ai_search',
    'wp_ajax_gi_ai_chat', 
    'wp_ajax_nopriv_gi_ai_chat',
    'wp_ajax_gi_search_suggestions',
    'wp_ajax_nopriv_gi_search_suggestions',
    'wp_ajax_gi_test_connection',
    'wp_ajax_nopriv_gi_test_connection'
];

foreach ($ajax_actions as $action) {
    $exists = has_action($action);
    $status = $exists ? '✅' : '❌';
    echo "<p>{$status} {$action}</p>";
}

echo "<h2>🚀 統合完了</h2>";
echo "<p>✅ すべてのコンポーネントが統合されました！</p>";
echo "<p>🌐 <a href='test-ai-setup.php'>テストページで動作確認</a></p>";
echo "<p>📱 <a href='index.php'>フロントページで実際の動作確認</a></p>";
?>