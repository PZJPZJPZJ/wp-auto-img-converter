<?php
/**
 * 设置页面类
 * 
 * 负责插件设置页面和配置管理
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

class AIC_Settings {
    /**
     * 扫描器实例
     */
    private $scanner;

    /**
     * 构造函数
     */
    public function __construct($scanner) {
        $this->scanner = $scanner;
        
        // 添加设置菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // 注册设置
        add_action('admin_init', array($this, 'register_settings'));
        // 加载前端资源
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        add_options_page(
            'Auto Img Converter Settings', // 页面标题
            'ImgConverter',      // 菜单标题
            'manage_options',    // 权限
            'auto-img-converter', // 菜单标识
            array($this, 'display_settings_page') // 回调函数
        );
    }

    /**
     * 注册插件设置
     */
    public function register_settings() {
        register_setting('auto_img_converter_settings', 'aic_enabled');
        register_setting('auto_img_converter_settings', 'aic_convert_formats', array(
            'type' => 'array',
            'default' => array('jpg', 'png', 'gif'),
            'sanitize_callback' => array($this, 'sanitize_convert_formats'),
        ));
        register_setting('auto_img_converter_settings', 'aic_quality');
        register_setting('auto_img_converter_settings', 'aic_keep_original');
        
        add_settings_section(
            'aic_main_section',
            '基本设置',
            array($this, 'section_callback'),
            'auto-img-converter'
        );

        add_settings_field(
            'aic_enabled',
            '启用自动转换',
            array($this, 'enabled_field_callback'),
            'auto-img-converter',
            'aic_main_section'
        );

        add_settings_field(
            'aic_convert_formats',
            '转换格式',
            array($this, 'convert_formats_field_callback'),
            'auto-img-converter',
            'aic_main_section'
        );
        
        add_settings_field(
            'aic_quality',
            '图片质量',
            array($this, 'quality_field_callback'),
            'auto-img-converter',
            'aic_main_section'
        );
        
        add_settings_field(
            'aic_keep_original',
            '保留原图',
            array($this, 'keep_original_field_callback'),
            'auto-img-converter',
            'aic_main_section'
        );
    }

    /**
     * 设置页面区块说明
     */
    public function section_callback() {
        echo '<p>配置 WebP 转换的相关设置</p>';
    }

    /**
     * 质量设置字段回调
     */
    public function quality_field_callback() {
        $quality = get_option('aic_quality', 80);
        echo '<input type="number" name="aic_quality" value="' . esc_attr($quality) . '" min="1" max="100" step="1" />';
        echo '<p class="description">设置 WebP 图片的质量（1-100）。建议范围：70-90，默认值：80</p>';
    }

    /**
     * 启用/禁用设置字段回调
     */
    public function enabled_field_callback() {
        $enabled = get_option('aic_enabled', true);
        echo '<input type="checkbox" name="aic_enabled" value="1" ' . checked(1, $enabled, false) . '/>';
        echo '<p class="description">上传图片时自动转换为 WebP 格式</p>';
    }

    /**
     * 转换格式多选框回调
     */
    public function convert_formats_field_callback() {
        $formats = get_option('aic_convert_formats', array('jpg', 'png', 'gif'));
        $available_formats = array(
            'jpg' => 'JPG',
            'png' => 'PNG',
            'gif' => 'GIF',
        );
        echo '<fieldset>';
        foreach ($available_formats as $value => $label) {
            $checked = in_array($value, (array)$formats) ? 'checked' : '';
            echo '<label>';
            echo '<input type="checkbox" name="aic_convert_formats[]" value="' . esc_attr($value) . '" ' . $checked . ' />';
            echo '&nbsp;' . esc_html($label) . '&nbsp;';
            echo '</label>';
        }
        echo '<p class="description">选择需要自动转换为 WebP 的图片格式</p>';
        echo '</fieldset>';
    }

    /**
     * 清理转换格式选项
     */
    public function sanitize_convert_formats($input) {
        $valid = array('jpg', 'png', 'gif');
        if (!is_array($input)) {
            return array();
        }
        return array_values(array_intersect($input, $valid));
    }

    /**
     * 保留原图设置字段回调
     */
    public function keep_original_field_callback() {
        $keep_original = get_option('aic_keep_original', false);
        echo '<input type="checkbox" name="aic_keep_original" value="1" ' . checked(1, $keep_original, false) . '/>';
        echo '<p class="description">选中此项将在转换为 WebP 格式后保留原始图片文件</p>';
    }

    /**
     * 加载管理页面脚本
     */
    public function enqueue_admin_scripts($hook) {
        // 只在插件设置页面加载
        if ($hook !== 'settings_page_auto-img-converter') {
            return;
        }

        wp_enqueue_script(
            'aic-batch-convert',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/batch-convert.js',
            array('jquery'),
            time(),
            true
        );

        // 传递nonce到JavaScript
        wp_localize_script('aic-batch-convert', 'aicData', array(
            'nonce' => wp_create_nonce('aic_batch_convert_nonce')
        ));
    }

    /**
     * 显示设置页面
     */
    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // 处理扫描请求
        if (isset($_POST['scan_images'])) {
            $scan_results = $this->scanner->scan_non_webp_images();
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('auto_img_converter_settings');
                do_settings_sections('auto-img-converter');
                submit_button('保存设置');
                ?>
            </form>

            <hr />
            <h2>扫描现有图片</h2>
            <form method="post" action="">
                <?php wp_nonce_field('scan_images_nonce', 'scan_images_nonce'); ?>
                <p>点击下面的按钮扫描媒体库中需要转换格式的图片：</p>
                <input type="submit" name="scan_images" class="button button-primary" value="开始扫描" />
            </form>

            <?php if (isset($scan_results)): ?>
                <?php
                $total_images = 0;
                foreach ($scan_results as $path => $images) {
                    $total_images += count($images);
                }
                ?>
                <div class="scan-results" style="margin-top: 20px;">
                    <h3>扫描结果</h3>
                    <p>转换 <span id="total-images"><?php echo $total_images; ?></span> 个图片到WebP格式：</p>
                    <?php if (!empty($scan_results)): ?>
                        <div style="margin-bottom: 10px;">
                            <label><input type="checkbox" id="select-all-dirs" checked> 全选所有</label>
                        </div>
                        <div style="max-height: 400px; overflow-y: auto; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                            <ul style="margin: 0;" id="dir-list">
                                <?php foreach ($scan_results as $path => $images): ?>
                                    <?php $dir_count = count($images); ?>
                                    <li class="dir-item" style="margin-bottom: 10px; border: 1px solid #eee; background: #fff; padding: 10px;">
                                        <div class="dir-header" style="font-weight: bold; display: flex; align-items: center;">
                                            <input type="checkbox" class="dir-checkbox" checked style="margin-right: 10px;">
                                            <div style="flex-grow: 1; cursor: pointer; display: flex; justify-content: space-between;" onclick="jQuery(this).parent().next('.image-list').slideToggle();">
                                                <span class="dir-path">📁 <?php echo esc_html($path); ?></span>
                                                <span class="dir-progress js-dir-progress" data-total="<?php echo esc_attr($dir_count); ?>" data-remaining="<?php echo esc_attr($dir_count); ?>">
                                                    剩余: <span class="remaining-count"><?php echo esc_html($dir_count); ?></span> / <?php echo esc_html($dir_count); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <ul class="image-list js-image-list" style="margin: 10px 0 0 35px; display: none;">
                                            <?php foreach ($images as $attachment_id => $image): ?>
                                                <li class="image-item" data-id="<?php echo esc_attr($attachment_id); ?>" style="display: flex; align-items: center; margin-bottom: 4px;">
                                                    <input type="checkbox" class="image-checkbox" checked style="margin-right: 8px;">
                                                    <span><?php echo esc_html($image); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="button" id="batch-convert-btn" class="button button-primary">开始批量转换格式</button>
                            <div id="conversion-progress" style="display: none; margin-top: 15px;">
                                <div class="progress" style="background: #f0f0f0; border: 1px solid #ddd; height: 30px; border-radius: 3px; overflow: hidden;">
                                    <div id="progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; line-height: 30px; text-align: center; color: white; font-weight: bold;">0%</div>
                                </div>
                                <p id="progress-text" style="margin-top: 10px;">准备开始转换...</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

