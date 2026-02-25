<?php
/**
 * 核心转换引擎类
 * 
 * 负责实际的图片格式转换工作
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

class AIC_Converter {
    /**
     * 构造函数
     */
    public function __construct() {
        // 添加上传过滤器
        add_filter('wp_handle_upload', array($this, 'convert_to_webp'));
        // 处理上传后的元数据（转换原始大图）
        add_filter('wp_generate_attachment_metadata', array($this, 'process_original_image'), 10, 2);
    }

    /**
     * 核心转换函数：将单个图片文件转换为WebP格式
     * @param string $file_path 源文件路径
     * @param bool $delete_original 是否删除原文件
     * @return array|false 成功返回array('path' => 新路径, 'file' => 文件名)，失败返回false
     */
    public function convert_single_image($file_path, $delete_original = true) {
        if (!file_exists($file_path)) {
            return false;
        }

        // 检查是否支持所需的图像处理扩展
        if (!extension_loaded('imagick') && !extension_loaded('gd')) {
            return false;
        }

        $image_editor = wp_get_image_editor($file_path);
        
        if (is_wp_error($image_editor)) {
            return false;
        }

        // 获取设置的质量值
        $quality = get_option('aic_quality', 80);
        $image_editor->set_quality($quality);

        $file_info = pathinfo($file_path);
        $dirname = $file_info['dirname'];
        $filename = $file_info['filename'];
        
        // 生成WebP文件路径
        $webp_filename = $filename . '.webp';
        $webp_path = $dirname . '/' . $webp_filename;

        // 保存为WebP格式
        $saved_image = $image_editor->save($webp_path, 'image/webp');

        if (is_wp_error($saved_image) || !file_exists($saved_image['path'])) {
            return false;
        }

        // 根据参数决定是否删除原图
        if ($delete_original) {
            @unlink($file_path);
        }

        return $saved_image;
    }

    /**
     * 转换图片为 WebP 格式（上传钩子）
     */
    public function convert_to_webp($upload) {
        // 检查功能是否启用
        $enabled = get_option('aic_enabled', true);
        if (!$enabled) {
            return $upload;
        }

        // 根据用户设置构建允许转换的 MIME 类型列表
        $convert_formats = get_option('aic_convert_formats', array('jpg', 'png', 'gif'));
        $format_mime_map = array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        );
        $allowed_mimes = array();
        foreach ((array)$convert_formats as $fmt) {
            if (isset($format_mime_map[$fmt])) {
                $allowed_mimes[] = $format_mime_map[$fmt];
            }
        }

        if (in_array($upload['type'], $allowed_mimes)) {
            $file_path = $upload['file'];
            $keep_original = get_option('aic_keep_original', false);
            
            // 使用核心转换函数
            $saved_image = $this->convert_single_image($file_path, !$keep_original);
            
            if ($saved_image) {
                // 更新上传信息
                $upload['file'] = $saved_image['path'];
                $upload['url'] = str_replace(basename($upload['url']), basename($saved_image['path']), $upload['url']);
                $upload['type'] = 'image/webp';
            }
        }
        return $upload;
    }

    /**
     * 处理原始大图转换（在生成缩略图后）
     * 当WordPress检测到大图并创建scaled版本时，转换原始大图
     */
    public function process_original_image($metadata, $attachment_id) {
        // 检查功能是否启用
        $enabled = get_option('aic_enabled', true);
        if (!$enabled) {
            return $metadata;
        }

        // 检查是否存在original_image（说明WordPress创建了scaled版本）
        if (empty($metadata['original_image'])) {
            return $metadata;
        }

        $file_path = get_attached_file($attachment_id);
        $dirname = dirname($file_path);
        $original_file = $dirname . '/' . $metadata['original_image'];

        // 如果原始文件存在且不是WebP格式，进行转换
        if (file_exists($original_file) && !preg_match('/\.webp$/i', $original_file)) {
            $keep_original = get_option('aic_keep_original', false);
            $original_converted = $this->convert_single_image($original_file, !$keep_original);
            
            if ($original_converted) {
                // 更新元数据中的original_image为WebP文件名
                $metadata['original_image'] = basename($original_converted['path']);
            }
        }

        return $metadata;
    }
}

