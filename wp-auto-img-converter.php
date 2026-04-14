<?php
/**
 * Plugin Name: WP Auto Img Converter
 * Description: Automatically converts and compresses images to WebP format during upload process, avoiding complex redirects to enhance website loading speed and performance.
 * Version: 1.0.8
 * Author: AzzDev
 * Requires PHP: 7.4
 * Requires at least: 6.7
 * Text Domain: wp-auto-img-converter
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 主插件类
 */
class Auto_Img_Converter {
    /**
     * 转换器实例
     */
    private $converter;

    /**
     * 扫描器实例
     */
    private $scanner;

    /**
     * 批量处理器实例
     */
    private $batch_processor;

    /**
     * 设置页面实例
     */
    private $settings;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }

    /**
     * 加载依赖文件
     */
    private function load_dependencies() {
        // 加载核心转换类
        require_once plugin_dir_path(__FILE__) . 'includes/class-converter.php';
        
        // 加载扫描器类
        require_once plugin_dir_path(__FILE__) . 'includes/class-scanner.php';
        
        // 加载批量处理器类
        require_once plugin_dir_path(__FILE__) . 'includes/class-batch-processor.php';
        
        // 加载设置页面类
        require_once plugin_dir_path(__FILE__) . 'includes/class-settings.php';
    }

    /**
     * 初始化组件
     */
    private function init_components() {
        // 初始化核心转换器
        $this->converter = new AIC_Converter();
        
        // 初始化扫描器
        $this->scanner = new AIC_Scanner();
        
        // 初始化批量处理器（依赖转换器）
        $this->batch_processor = new AIC_Batch_Processor($this->converter);
        
        // 初始化设置页面（依赖扫描器）
        $this->settings = new AIC_Settings($this->scanner);
    }

    /**
     * 获取转换器实例
     */
    public function get_converter() {
        return $this->converter;
    }

    /**
     * 获取扫描器实例
     */
    public function get_scanner() {
        return $this->scanner;
    }

    /**
     * 获取批量处理器实例
     */
    public function get_batch_processor() {
        return $this->batch_processor;
    }

    /**
     * 获取设置页面实例
     */
    public function get_settings() {
        return $this->settings;
    }
}

/**
 * 插件激活钩子
 */
function aic_activate() {
    // 设置默认的插件选项
    add_option('aic_enabled', true);
    add_option('aic_convert_formats', array('jpg', 'png', 'gif'));
    add_option('aic_quality', 80);
    add_option('aic_keep_original', false);
}
register_activation_hook(__FILE__, 'aic_activate');

/**
 * 插件卸载钩子
 */
function aic_uninstall() {
    // 删除插件设置
    delete_option('aic_enabled');
    delete_option('aic_convert_formats');
    delete_option('aic_quality');
    delete_option('aic_keep_original');
}
register_uninstall_hook(__FILE__, 'aic_uninstall');

/**
 * 初始化插件
 * 
 * @return Auto_Img_Converter 插件实例
 */
function auto_img_converter_init() {
    static $instance = null;
    
    if (null === $instance) {
        $instance = new Auto_Img_Converter();
    }
    
    return $instance;
}

// 启动插件
auto_img_converter_init();

