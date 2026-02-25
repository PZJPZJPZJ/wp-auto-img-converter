<?php
/**
 * 扫描器类
 * 
 * 负责扫描媒体库中的非WebP格式图片
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

class AIC_Scanner {
    /**
     * 扫描媒体库中的非WebP图片
     */
    public function scan_non_webp_images() {
        if (!check_admin_referer('scan_images_nonce', 'scan_images_nonce')) {
            wp_die('安全检查失败');
        }
        
        // 根据用户设置构建需要扫描的 MIME 类型列表
        $convert_formats = get_option('aic_convert_formats', array('jpg', 'png', 'gif'));
        $format_mime_map = array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        );
        $scan_mimes = array();
        foreach ((array)$convert_formats as $fmt) {
            if (isset($format_mime_map[$fmt])) {
                $scan_mimes[] = $format_mime_map[$fmt];
            }
        }

        if (empty($scan_mimes)) {
            return array();
        }

        // 使用WP_Query查询媒体库中的图片
        $query_args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => $scan_mimes,
            'posts_per_page' => -1,
            'fields'         => 'ids'
        );
        
        $query = new WP_Query($query_args);
        $attachment_ids = $query->posts;
        
        $non_webp_images = array();
        
        if ($attachment_ids) {
            foreach ($attachment_ids as $attachment_id) {
                // 使用WordPress函数获取附件信息
                $file_path = get_attached_file($attachment_id);
                $file_name = basename($file_path);
                
                if ($file_path) {
                    // 检查是否已经存在对应的WebP文件
                    $webp_path = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file_path);
                    
                    if (!file_exists($webp_path)) {
                        $non_webp_images[$attachment_id] = $file_name;
                    }
                }
            }
        }

        return $non_webp_images;
    }
}

