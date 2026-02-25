<?php
/**
 * 批量处理器类
 * 
 * 负责AJAX批量转换功能
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

class AIC_Batch_Processor {
    /**
     * 转换器实例
     */
    private $converter;

    /**
     * 构造函数
     */
    public function __construct($converter) {
        $this->converter = $converter;
        
        // AJAX处理批量转换
        add_action('wp_ajax_aic_batch_convert', array($this, 'ajax_batch_convert'));
    }

    /**
     * AJAX处理批量转换
     */
    public function ajax_batch_convert() {
        check_ajax_referer('aic_batch_convert_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('权限不足');
        }

        $attachment_id = intval($_POST['attachment_id']);
        $file_path = get_attached_file($attachment_id);

        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error('文件不存在');
        }

        // 获取附件元数据
        $metadata = wp_get_attachment_metadata($attachment_id);
        $keep_original = get_option('aic_keep_original', false);
        $dirname = dirname($file_path);
        
        // 1. 处理原始大图（如果存在original_image）
        $original_webp_filename = null;
        if (!empty($metadata['original_image'])) {
            $original_file = $dirname . '/' . $metadata['original_image'];
            
            if (file_exists($original_file)) {
                $original_converted = $this->converter->convert_single_image($original_file, !$keep_original);
                
                if ($original_converted) {
                    $original_webp_filename = basename($original_converted['path']);
                }
            }
        }
        
        // 2. 删除旧的缩略图文件
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $size_data) {
                $thumbnail_path = $dirname . '/' . $size_data['file'];
                if (file_exists($thumbnail_path)) {
                    @unlink($thumbnail_path);
                }
            }
        }

        // 3. 转换主图（scaled版本或普通图片）
        $saved_image = $this->converter->convert_single_image($file_path, !$keep_original);

        if (!$saved_image) {
            wp_send_json_error('主图转换失败');
        }

        // 4. 更新附件的文件路径和MIME类型
        update_attached_file($attachment_id, $saved_image['path']);
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_mime_type' => 'image/webp'
        ));
        
        // 5. 重新生成所有缩略图（会自动使用WebP格式）
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $new_metadata = wp_generate_attachment_metadata($attachment_id, $saved_image['path']);
        
        // 6. 更新original_image字段为转换后的WebP文件
        if ($original_webp_filename) {
            $new_metadata['original_image'] = $original_webp_filename;
        }
        
        wp_update_attachment_metadata($attachment_id, $new_metadata);
        
        $message = '转换成功（含所有缩略图';
        if ($original_webp_filename) {
            $message .= '和原始大图';
        }
        $message .= '）';
        
        wp_send_json_success($message);
    }
}

