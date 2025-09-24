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
    // 権限チェック
    if (!current_user_can('upload_files') && !current_user_can('edit_posts')) {
        wp_die('権限がありません');
    }

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
        '申請期限',
        '募集開始日',
        '対象都道府県',
        'カテゴリー',
        '対象者・応募要件',
        '申請手順',
        '必要書類',
        '概要',
        '本文',
        'URL',
        '連絡先',
        '電話番号',
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
    
    // カスタムフィールド
    $organization = gi_safe_get_meta($post_id, 'organization', '');
    $organization_type = gi_safe_get_meta($post_id, 'organization_type', '');
    $max_amount = gi_safe_get_meta($post_id, 'max_amount', '');
    $min_amount = gi_safe_get_meta($post_id, 'min_amount', '');
    $deadline = gi_safe_get_meta($post_id, 'deadline', '');
    $application_start = gi_safe_get_meta($post_id, 'application_start', '');
    $target_requirements = gi_safe_get_meta($post_id, 'target_requirements', '');
    $application_steps = gi_safe_get_meta($post_id, 'application_steps', '');
    $required_documents = gi_safe_get_meta($post_id, 'required_documents', '');
    $summary = gi_safe_get_meta($post_id, 'summary', '');
    $url = gi_safe_get_meta($post_id, 'url', '');
    $contact = gi_safe_get_meta($post_id, 'contact', '');
    $phone = gi_safe_get_meta($post_id, 'phone', '');
    
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
        $deadline,
        $application_start,
        $prefecture,
        $category,
        $target_requirements,
        $application_steps,
        $required_documents,
        $summary,
        $content,
        $url,
        $contact,
        $phone,
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
    // 権限チェック
    if (!current_user_can('edit_posts')) {
        wp_die('権限がありません - インポート機能は編集者権限以上が必要です');
    }

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
        'deadline' => '申請期限',
        'application_start' => '募集開始日',
        'target_requirements' => '対象者・応募要件',
        'application_steps' => '申請手順',
        'required_documents' => '必要書類',
        'summary' => '概要',
        'url' => 'URL',
        'contact' => '連絡先',
        'phone' => '電話番号'
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
    if (!current_user_can('upload_files') && !current_user_can('edit_posts')) {
        wp_die('権限がありません');
    }

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
        '都道府県',
        '1000',
        '100',
        '2024-12-31',
        '2024-04-01',
        '東京都',
        'IT・デジタル',
        '東京都内に本社を有する中小企業',
        '1. 申請書類の準備 2. オンライン申請 3. 審査 4. 採択通知',
        '申請書、事業計画書、見積書',
        'IT導入を支援する助成金です',
        'この助成金は東京都内の中小企業のIT導入を支援します...',
        'https://example.com',
        '東京都産業労働局',
        '03-1234-5678',
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

?>