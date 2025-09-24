<?php
/**
 * Grant Insight Perfect - Excel Import/Export Functions
 *
 * 助成金投稿のエクセル（Excel）エクスポート・インポート機能
 * CSVベース実装で、幅広いExcelアプリケーションと互換性を持つ
 *
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * =============================================================================
 * 1. Excel エクスポート機能
 * =============================================================================
 */

/**
 * 助成金データをExcel形式でエクスポート
 */
function gi_export_grants_to_excel() {
    // 権限チェックなし - 誰でも使用可能

    // nonceチェック
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'gi_export_excel')) {
        wp_die('セキュリティチェックに失敗しました');
    }

    // エクスポート対象の取得
    $export_type = sanitize_text_field($_GET['export_type'] ?? 'all');
    $args = array(
        'post_type' => 'grant',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    // フィルター処理
    if ($export_type === 'published') {
        $args['post_status'] = 'publish';
    } elseif ($export_type === 'draft') {
        $args['post_status'] = 'draft';
    }

    $grants = get_posts($args);

    if (empty($grants)) {
        wp_redirect(admin_url('edit.php?post_type=grant&message=no_data'));
        exit;
    }

    // ファイル名生成
    $filename = 'grant_export_' . date('Y-m-d_H-i-s') . '.csv';

    // HTTPヘッダー設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // CSV出力開始
    $output = fopen('php://output', 'w');

    // BOM追加（Excel日本語対応）
    fputs($output, "\xEF\xBB\xBF");

    // ヘッダー行
    $headers = gi_get_excel_headers();
    fputcsv($output, $headers);

    // データ行
    foreach ($grants as $grant) {
        $row_data = gi_prepare_grant_row_data($grant);
        fputcsv($output, $row_data);
    }

    fclose($output);
    exit;
}

/**
 * エクセルヘッダー定義
 */
function gi_get_excel_headers() {
    return array(
        'ID',
        'タイトル',
        'ステータス',
        '実施組織',
        '組織タイプ',
        '最大金額（万円）',
        '最小金額（万円）',
        '補助率（%）',
        '金額備考',
        '申請期限',
        '募集開始日',
        '申請ステータス',
        '対象都道府県',
        'カテゴリー',
        'タグ',
        '助成金対象',
        '対象経費',
        '難易度',
        '成功率（%）',
        '対象者・応募要件',
        '申請手順',
        '申請方法',
        '必要書類',
        '連絡先情報',
        '公式URL',
        '概要',
        '本文',
        '作成日',
        '更新日',
        '作成者',
    );
}

/**
 * 助成金データを1行分に変換
 */
function gi_prepare_grant_row_data($grant) {
    $post_id = $grant->ID;
    
    // 基本情報
    $title = get_the_title($post_id);
    $status = get_post_status($post_id);
    $author = get_the_author_meta('display_name', $grant->post_author);
    
    // カスタムフィールド（ACF対応）
    $organization = gi_safe_get_meta($post_id, 'organization', '');
    $organization_type = gi_safe_get_meta($post_id, 'organization_type', '');
    $max_amount = gi_safe_get_meta($post_id, 'max_amount', '');
    $min_amount = gi_safe_get_meta($post_id, 'min_amount', '');
    $subsidy_rate = gi_safe_get_meta($post_id, 'subsidy_rate', '');
    $amount_note = gi_safe_get_meta($post_id, 'amount_note', '');
    $deadline = gi_safe_get_meta($post_id, 'deadline', '');
    $application_start = gi_safe_get_meta($post_id, 'application_start', '');
    $application_status = gi_safe_get_meta($post_id, 'application_status', '');
    $grant_target = gi_safe_get_meta($post_id, 'grant_target', '');
    $eligible_expenses = gi_safe_get_meta($post_id, 'eligible_expenses', '');
    $grant_difficulty = gi_safe_get_meta($post_id, 'grant_difficulty', '');
    $grant_success_rate = gi_safe_get_meta($post_id, 'grant_success_rate', '');
    $target_requirements = gi_safe_get_meta($post_id, 'target_requirements', '');
    $application_steps = gi_safe_get_meta($post_id, 'application_steps', '');
    $application_method = gi_safe_get_meta($post_id, 'application_method', '');
    $required_documents = gi_safe_get_meta($post_id, 'required_documents', '');
    $contact_info = gi_safe_get_meta($post_id, 'contact_info', '');
    $official_url = gi_safe_get_meta($post_id, 'official_url', '');
    $summary = gi_safe_get_meta($post_id, 'summary', '');
    
    // タクソノミー
    $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
    $prefecture = '';
    if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
        $prefecture_names = array();
        foreach ($prefecture_terms as $term) {
            $prefecture_names[] = $term->name;
        }
        $prefecture = implode('、', $prefecture_names);
    }
    
    $category_terms = get_the_terms($post_id, 'grant_category');
    $category = '';
    if ($category_terms && !is_wp_error($category_terms)) {
        $category_names = array();
        foreach ($category_terms as $term) {
            $category_names[] = $term->name;
        }
        $category = implode('、', $category_names);
    }
    
    // 標準タグ対応
    $tag_terms = get_the_terms($post_id, 'post_tag');
    $tags = '';
    if ($tag_terms && !is_wp_error($tag_terms)) {
        $tag_names = array();
        foreach ($tag_terms as $term) {
            $tag_names[] = $term->name;
        }
        $tags = implode('、', $tag_names);
    }
    
    // 日付フォーマット
    $created_date = get_post_time('Y-m-d H:i:s', false, $post_id);
    $modified_date = get_post_modified_time('Y-m-d H:i:s', false, $post_id);
    
    // 期限日フォーマット
    if ($deadline) {
        $deadline = date('Y-m-d', strtotime($deadline));
    }
    if ($application_start) {
        $application_start = date('Y-m-d', strtotime($application_start));
    }
    
    // 本文（改行を除去）
    $content = wp_strip_all_tags($grant->post_content);
    $content = str_replace(array("\r\n", "\n", "\r"), ' ', $content);
    
    // 長いテキストフィールドの改行を除去
    $target_requirements = str_replace(array("\r\n", "\n", "\r"), ' ', $target_requirements);
    $application_steps = str_replace(array("\r\n", "\n", "\r"), ' ', $application_steps);
    $required_documents = str_replace(array("\r\n", "\n", "\r"), ' ', $required_documents);
    $summary = str_replace(array("\r\n", "\n", "\r"), ' ', $summary);
    
    return array(
        $post_id,
        $title,
        $status,
        $organization,
        $organization_type,
        $max_amount,
        $min_amount,
        $subsidy_rate,
        $amount_note,
        $deadline,
        $application_start,
        $application_status,
        $prefecture,
        $category,
        $tags,
        $grant_target,
        $eligible_expenses,
        $grant_difficulty,
        $grant_success_rate,
        $target_requirements,
        $application_steps,
        $application_method,
        $required_documents,
        $contact_info,
        $official_url,
        $summary,
        $content,
        $created_date,
        $modified_date,
        $author,
    );
}

/**
 * =============================================================================
 * 2. Excel インポート機能
 * =============================================================================
 */

/**
 * Excel/CSVファイルから助成金データをインポート
 */
function gi_import_grants_from_excel() {
    // 権限チェックなし - 誰でも使用可能

    // nonceチェック
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'gi_import_excel')) {
        wp_die('セキュリティチェックに失敗しました');
    }

    // ファイルアップロードチェック
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        wp_redirect(admin_url('edit.php?post_type=grant&message=upload_error'));
        exit;
    }

    $file = $_FILES['import_file'];
    $allowed_types = array('text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel');
    
    if (!in_array($file['type'], $allowed_types)) {
        wp_redirect(admin_url('edit.php?post_type=grant&message=invalid_file_type'));
        exit;
    }

    // ファイル読み込み
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        wp_redirect(admin_url('edit.php?post_type=grant&message=file_read_error'));
        exit;
    }

    $import_results = array(
        'success' => 0,
        'errors' => 0,
        'warnings' => array()
    );

    $line_number = 0;
    $headers = array();
    
    while (($data = fgetcsv($handle, 0, ',')) !== false) {
        $line_number++;
        
        // ヘッダー行をスキップ
        if ($line_number === 1) {
            $headers = $data;
            continue;
        }
        
        // データ行処理
        $result = gi_process_import_row($data, $headers, $line_number);
        
        if ($result['success']) {
            $import_results['success']++;
        } else {
            $import_results['errors']++;
            $import_results['warnings'][] = "行 {$line_number}: " . $result['message'];
        }
    }
    
    fclose($handle);

    // インポート結果をセッションに保存
    set_transient('gi_import_results', $import_results, 300);
    
    wp_redirect(admin_url('edit.php?post_type=grant&message=import_complete'));
    exit;
}

/**
 * インポート行処理
 */
function gi_process_import_row($data, $headers, $line_number) {
    try {
        // データの検証
        if (empty($data) || count($data) < 2) {
            return array('success' => false, 'message' => '不正なデータ形式');
        }
        
        // ヘッダーとデータの対応
        $row_data = array();
        for ($i = 0; $i < count($headers) && $i < count($data); $i++) {
            $row_data[$headers[$i]] = $data[$i];
        }
        
        // 必須項目チェック
        if (empty($row_data['タイトル'])) {
            return array('success' => false, 'message' => 'タイトルが必要です');
        }
        
        // 投稿データ準備
        $post_data = array(
            'post_title' => sanitize_text_field($row_data['タイトル']),
            'post_content' => wp_kses_post($row_data['本文'] ?? ''),
            'post_status' => sanitize_text_field($row_data['ステータス'] ?? 'draft'),
            'post_type' => 'grant',
            'post_author' => get_current_user_id()
        );
        
        // 既存投稿のチェック（IDが指定されている場合）
        $post_id = 0;
        if (!empty($row_data['ID']) && is_numeric($row_data['ID'])) {
            $existing_post = get_post($row_data['ID']);
            if ($existing_post && $existing_post->post_type === 'grant') {
                $post_data['ID'] = $row_data['ID'];
                $post_id = wp_update_post($post_data);
            }
        }
        
        // 新規投稿作成
        if ($post_id === 0) {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            return array('success' => false, 'message' => $post_id->get_error_message());
        }
        
        // カスタムフィールドの更新
        gi_update_import_custom_fields($post_id, $row_data);
        
        // タクソノミーの更新
        gi_update_import_taxonomies($post_id, $row_data);
        
        return array('success' => true, 'message' => '');
        
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'エラー: ' . $e->getMessage());
    }
}

/**
 * インポート時のカスタムフィールド更新
 */
function gi_update_import_custom_fields($post_id, $row_data) {
    $field_mappings = array(
        'organization' => '実施組織',
        'organization_type' => '組織タイプ',
        'max_amount' => '最大金額（万円）',
        'min_amount' => '最小金額（万円）',
        'subsidy_rate' => '補助率（%）',
        'amount_note' => '金額備考',
        'deadline' => '申請期限',
        'application_start' => '募集開始日',
        'application_status' => '申請ステータス',
        'grant_target' => '助成金対象',
        'eligible_expenses' => '対象経費',
        'grant_difficulty' => '難易度',
        'grant_success_rate' => '成功率（%）',
        'target_requirements' => '対象者・応募要件',
        'application_steps' => '申請手順',
        'application_method' => '申請方法',
        'required_documents' => '必要書類',
        'contact_info' => '連絡先情報',
        'official_url' => '公式URL',
        'summary' => '概要'
    );
    
    foreach ($field_mappings as $field_key => $excel_header) {
        if (isset($row_data[$excel_header]) && $row_data[$excel_header] !== '') {
            $value = sanitize_text_field($row_data[$excel_header]);
            
            // 日付フィールドの処理
            if (in_array($field_key, array('deadline', 'application_start'))) {
                $value = gi_parse_import_date($value);
            }
            
            update_post_meta($post_id, $field_key, $value);
        }
    }
}

/**
 * インポート時のタクソノミー更新
 */
function gi_update_import_taxonomies($post_id, $row_data) {
    // 都道府県
    if (!empty($row_data['対象都道府県'])) {
        $prefectures = explode('、', $row_data['対象都道府県']);
        $prefecture_ids = array();
        
        foreach ($prefectures as $prefecture_name) {
            $prefecture_name = trim($prefecture_name);
            $term = get_term_by('name', $prefecture_name, 'grant_prefecture');
            
            if (!$term) {
                // 新しいタームを作成
                $new_term = wp_insert_term($prefecture_name, 'grant_prefecture');
                if (!is_wp_error($new_term)) {
                    $prefecture_ids[] = $new_term['term_id'];
                }
            } else {
                $prefecture_ids[] = $term->term_id;
            }
        }
        
        if (!empty($prefecture_ids)) {
            wp_set_post_terms($post_id, $prefecture_ids, 'grant_prefecture');
        }
    }
    
    // カテゴリー
    if (!empty($row_data['カテゴリー'])) {
        $categories = explode('、', $row_data['カテゴリー']);
        $category_ids = array();
        
        foreach ($categories as $category_name) {
            $category_name = trim($category_name);
            $term = get_term_by('name', $category_name, 'grant_category');
            
            if (!$term) {
                // 新しいタームを作成
                $new_term = wp_insert_term($category_name, 'grant_category');
                if (!is_wp_error($new_term)) {
                    $category_ids[] = $new_term['term_id'];
                }
            } else {
                $category_ids[] = $term->term_id;
            }
        }
        
        if (!empty($category_ids)) {
            wp_set_post_terms($post_id, $category_ids, 'grant_category');
        }
    }
    
    // タグ
    if (!empty($row_data['タグ'])) {
        $tags = explode('、', $row_data['タグ']);
        $tag_ids = array();
        
        foreach ($tags as $tag_name) {
            $tag_name = trim($tag_name);
            $term = get_term_by('name', $tag_name, 'post_tag');
            
            if (!$term) {
                // 新しいタグを作成
                $new_term = wp_insert_term($tag_name, 'post_tag');
                if (!is_wp_error($new_term)) {
                    $tag_ids[] = $new_term['term_id'];
                }
            } else {
                $tag_ids[] = $term->term_id;
            }
        }
        
        if (!empty($tag_ids)) {
            wp_set_post_terms($post_id, $tag_ids, 'post_tag');
        }
    }
}

/**
 * 日付文字列をパース
 */
function gi_parse_import_date($date_string) {
    if (empty($date_string)) {
        return '';
    }
    
    // 複数の日付フォーマットに対応
    $formats = array(
        'Y-m-d',
        'Y/m/d',
        'm/d/Y',
        'd/m/Y',
        'Y年m月d日'
    );
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $date_string);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    // strtotime での解析を試行
    $timestamp = strtotime($date_string);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return $date_string; // パースできない場合はそのまま返す
}

/**
 * =============================================================================
 * 3. AJAX エンドポイント登録
 * =============================================================================
 */

// エクスポート用AJAX
add_action('wp_ajax_gi_export_excel', 'gi_export_grants_to_excel');

// インポート用AJAX
add_action('wp_ajax_gi_import_excel', 'gi_import_grants_from_excel');

/**
 * =============================================================================
 * 4. 管理画面メッセージ処理
 * =============================================================================
 */

/**
 * 管理画面メッセージを追加
 */
add_filter('post_updated_messages', function($messages) {
    $messages['grant']['no_data'] = 'エクスポートするデータがありません。';
    $messages['grant']['upload_error'] = 'ファイルのアップロードに失敗しました。';
    $messages['grant']['invalid_file_type'] = '対応していないファイル形式です。CSVファイルをアップロードしてください。';
    $messages['grant']['file_read_error'] = 'ファイルの読み込みに失敗しました。';
    $messages['grant']['import_complete'] = 'インポートが完了しました。';
    
    return $messages;
});

/**
 * インポート結果を表示
 */
add_action('admin_notices', function() {
    if (isset($_GET['message']) && $_GET['message'] === 'import_complete') {
        $results = get_transient('gi_import_results');
        if ($results) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>インポート完了</strong></p>';
            echo '<p>成功: ' . $results['success'] . '件</p>';
            if ($results['errors'] > 0) {
                echo '<p>エラー: ' . $results['errors'] . '件</p>';
                if (!empty($results['warnings'])) {
                    echo '<ul>';
                    foreach ($results['warnings'] as $warning) {
                        echo '<li>' . esc_html($warning) . '</li>';
                    }
                    echo '</ul>';
                }
            }
            echo '</div>';
            
            delete_transient('gi_import_results');
        }
    }
});

/**
 * =============================================================================
 * 5. ヘルパー関数
 * =============================================================================
 */

/**
 * サンプルCSVファイルのダウンロード
 */
function gi_download_sample_csv() {
    // 権限チェックなし - 誰でも使用可能

    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'gi_sample_csv')) {
        wp_die('セキュリティチェックに失敗しました');
    }

    $filename = 'grant_import_sample.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // BOM追加
    fputs($output, "\xEF\xBB\xBF");

    // ヘッダー行
    fputcsv($output, gi_get_excel_headers());

    // サンプルデータ行
    $sample_data = array(
        '', // ID（新規作成時は空）
        '令和6年度IT導入支援助成金',
        'publish',
        '東京都産業労働局',
        'prefecture',
        '1000',
        '100',
        '50',
        'ただし上限1000万円まで',
        '2024-12-31',
        '2024-04-01',
        'open',
        '東京都',
        'IT・デジタル、設備投資',
        'IT導入、デジタル化、生産性向上、中小企業支援',
        '中小企業・ベンチャー企業',
        'ソフトウェア導入費、システム開発費',
        'medium',
        '65',
        '東京都内に本社を有する中小企業、従業員50名以下',
        '1. 申請書類の準備 2. オンライン申請システムにログイン 3. 必要書類のアップロード 4. 審査 5. 採択通知',
        'online',
        '申請書、事業計画書、見積書、会社概要、決算書類（直近2期分）',
        '東京都産業労働局 助成金担当窓口 TEL:03-1234-5678 MAIL:joseikin@tokyo.lg.jp',
        'https://www.sangyo-rodo.metro.tokyo.lg.jp/josei/it-support/',
        'IT導入を支援する助成金です。デジタル化推進により生産性向上を図る中小企業を支援します。',
        'この助成金は東京都内の中小企業のIT導入を支援し、デジタル化による生産性向上を促進することを目的としています。対象となるのは、ソフトウェア導入、システム開発、クラウドサービス利用などIT関連の設備投資です。',
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s'),
        get_current_user()->display_name ?? 'admin'
    );

    fputcsv($output, $sample_data);

    fclose($output);
    exit;
}

// サンプルCSVダウンロード用AJAX
add_action('wp_ajax_gi_sample_csv', 'gi_download_sample_csv');

/**
 * =============================================================================
 * 6. AI機能統合
 * =============================================================================
 */

/**
 * AI一括処理用AJAX
 */
function gi_bulk_ai_process() {
    // 権限チェックなし - 誰でも使用可能
    
    // nonceチェック
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ai_bulk_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $type = sanitize_text_field($_POST['type'] ?? 'summary');
    $fields = array_map('sanitize_text_field', $_POST['fields'] ?? array());
    
    if (empty($fields)) {
        wp_send_json_error('処理対象フィールドが選択されていません');
    }
    
    // OpenAI API キーの確認
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('gi_openai_api_key', '');
    if (empty($api_key)) {
        wp_send_json_error('OpenAI API キーが設定されていません');
    }
    
    // 助成金投稿を取得
    $grants = get_posts(array(
        'post_type' => 'grant',
        'post_status' => 'any',
        'posts_per_page' => 50, // 一度に処理する件数を制限
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if (empty($grants)) {
        wp_send_json_error('処理対象の投稿が見つかりません');
    }
    
    $processed = 0;
    $errors = array();
    
    foreach ($grants as $grant) {
        try {
            $result = gi_process_single_post_ai($grant->ID, $type, $fields, $api_key);
            if ($result) {
                $processed++;
            }
        } catch (Exception $e) {
            $errors[] = "投稿ID {$grant->ID}: " . $e->getMessage();
        }
        
        // API制限対応（1秒待機）
        sleep(1);
    }
    
    wp_send_json_success(array(
        'processed' => $processed,
        'total' => count($grants),
        'errors' => $errors,
        'type' => $type,
        'fields' => $fields
    ));
}

/**
 * 個別投稿のAI処理
 */
function gi_process_single_post_ai($post_id, $type, $fields, $api_key) {
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }
    
    $updated = false;
    
    foreach ($fields as $field) {
        try {
            $current_content = '';
            $new_content = '';
            
            // 現在の内容を取得
            if ($field === 'content') {
                $current_content = $post->post_content;
            } elseif ($field === 'summary') {
                $current_content = get_post_meta($post_id, 'summary', true);
            } else {
                $current_content = get_post_meta($post_id, $field, true);
            }
            
            // AI処理を実行
            if ($type === 'summary') {
                $new_content = gi_generate_ai_summary($post, $field, $api_key);
            } elseif ($type === 'improve') {
                $new_content = gi_improve_content_with_ai($current_content, $post, $field, $api_key);
            }
            
            if (!empty($new_content) && $new_content !== $current_content) {
                // 内容を更新
                if ($field === 'content') {
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_content' => $new_content
                    ));
                } else {
                    update_post_meta($post_id, $field, $new_content);
                }
                $updated = true;
            }
            
        } catch (Exception $e) {
            error_log("AI処理エラー (投稿ID: {$post_id}, フィールド: {$field}): " . $e->getMessage());
        }
    }
    
    return $updated;
}

/**
 * AI要約生成
 */
function gi_generate_ai_summary($post, $field, $api_key) {
    $title = $post->post_title;
    $content = wp_strip_all_tags($post->post_content);
    $organization = get_post_meta($post->ID, 'organization', true);
    $max_amount = get_post_meta($post->ID, 'max_amount', true);
    
    $prompt = "以下の助成金情報から、{$field}フィールド用の適切な要約を日本語で生成してください：\n\n";
    $prompt .= "タイトル: {$title}\n";
    $prompt .= "実施組織: {$organization}\n";
    $prompt .= "最大金額: {$max_amount}万円\n";
    $prompt .= "詳細内容: " . substr($content, 0, 500) . "\n\n";
    
    if ($field === 'summary') {
        $prompt .= "100-200文字の魅力的な概要を生成してください。";
    } elseif ($field === 'target_requirements') {
        $prompt .= "対象者・応募要件を箇条書きで生成してください。";
    } elseif ($field === 'application_steps') {
        $prompt .= "申請手順を分かりやすいステップで生成してください。";
    }
    
    return gi_call_openai_api($prompt, $api_key);
}

/**
 * AI内容改善
 */
function gi_improve_content_with_ai($content, $post, $field, $api_key) {
    if (empty($content)) {
        return gi_generate_ai_summary($post, $field, $api_key);
    }
    
    $prompt = "以下の助成金の{$field}フィールドの内容を改善してください。より分かりやすく、魅力的で実用的な内容にしてください：\n\n";
    $prompt .= "現在の内容: {$content}\n\n";
    $prompt .= "改善要求: より具体的で分かりやすく、読みやすい日本語に改善してください。";
    
    return gi_call_openai_api($prompt, $api_key);
}

/**
 * OpenAI API呼び出し
 */
function gi_call_openai_api($prompt, $api_key) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'あなたは助成金情報の専門家です。正確で分かりやすく実用的な日本語コンテンツを生成してください。'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 1000,
            'temperature' => 0.7
        ))
    ));
    
    if (is_wp_error($response)) {
        throw new Exception('API呼び出しエラー: ' . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        throw new Exception('AI応答の解析に失敗しました');
    }
    
    return trim($data['choices'][0]['message']['content']);
}

// AI処理用AJAX
add_action('wp_ajax_gi_bulk_ai_process', 'gi_bulk_ai_process');

?>