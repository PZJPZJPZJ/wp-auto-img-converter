# AutoImgConverter - WordPress图片格式转换插件

## 🚀 功能特性

- ✅ **自动转换**：上传图片时自动转换为WebP格式
- ✅ **批量处理**：扫描并批量转换现有图片
- ✅ **原始大图支持**：完整处理WordPress的scaled机制
- ✅ **质量可控**：自定义WebP图片质量（1-100）
- ✅ **可选保留原图**：灵活的文件管理策略
- ✅ **进度可视化**：实时显示批量转换进度
- ✅ **模块化设计**：代码结构清晰，易于维护

---

## 📁 项目结构

```
wp-auto-img-converter/
│
├── auto-img-converter.php              # 主入口文件
│   └─ 插件初始化、组件加载、钩子注册
│
├── includes/                          # 核心功能模块
│   ├── class-converter.php            # 核心转换引擎
│   ├── class-scanner.php              # 媒体库扫描器
│   ├── class-batch-processor.php      # 批量处理器
│   └── class-settings.php             # 设置页面管理
│
├── assets/                            # 前端资源
│   └── js/
│       └── batch-convert.js           # 批量转换前端脚本
│
└── README.md                          # 本文件
```

---

## 🏗️ 核心组件

### 1. **AIC_Converter** - 核心转换引擎
**文件：** `includes/class-converter.php`

**职责：**
- 实现图片格式转换核心逻辑
- 处理上传图片的自动转换
- 处理WordPress scaled大图机制
- 提供可复用的转换函数

**关键方法：**
```php
class AIC_Converter {
    convert_single_image()        // 核心：转换单个图片
    convert_to_webp()            // 钩子：上传时转换
    process_original_image()     // 钩子：处理原始大图
}
```

**WordPress钩子：**
- `wp_handle_upload` - 文件上传处理
- `wp_generate_attachment_metadata` - 元数据生成后处理

---

### 2. **AIC_Scanner** - 媒体库扫描器
**文件：** `includes/class-scanner.php`

**职责：**
- 扫描媒体库中的非WebP图片
- 返回待转换图片列表

**关键方法：**
```php
class AIC_Scanner {
    scan_non_webp_images()  // 扫描并返回非WebP图片列表
}
```

---

### 3. **AIC_Batch_Processor** - 批量处理器
**文件：** `includes/class-batch-processor.php`

**职责：**
- 处理AJAX批量转换请求
- 协调转换器完成批量任务
- 更新WordPress附件元数据

**处理流程：**
1. 安全验证（nonce + 权限）
2. 转换原始大图（如果存在）
3. 删除旧缩略图
4. 转换主图
5. 重新生成WebP缩略图
6. 更新元数据
7. 返回JSON响应

**WordPress钩子：**
- `wp_ajax_aic_batch_convert` - AJAX请求处理

---

### 4. **AIC_Settings** - 设置页面
**文件：** `includes/class-settings.php`

**职责：**
- 管理插件设置页面UI
- 注册WordPress设置API
- 处理扫描请求
- 加载前端资源

**设置项：**
- `aic_enabled` - 启用/禁用自动转换
- `aic_quality` - WebP质量（1-100）
- `aic_keep_original` - 是否保留原图

**WordPress钩子：**
- `admin_menu` - 添加设置菜单
- `admin_init` - 注册设置
- `admin_enqueue_scripts` - 加载脚本

---

### 5. **batch-convert.js** - 前端脚本
**文件：** `assets/js/batch-convert.js`

**职责：**
- 处理批量转换按钮点击
- 递归发送AJAX请求
- 更新进度条和状态
- 处理转换结果可视化

---

## 🔄 工作流程

### 📤 上传图片时的自动转换

```
用户上传图片 (例如: vacation.jpg 5000×3000)
    ↓
WordPress检测大图
    ├─ 保存 vacation.jpg (5000×3000, 原始)
    └─ 创建 vacation-scaled.jpg (2560×1536)
    ↓
AIC_Converter::convert_to_webp() 钩子触发
    └─ vacation-scaled.jpg → vacation-scaled.webp
    ↓
WordPress生成缩略图（基于WebP）
    ├─ thumbnail.webp
    ├─ medium.webp
    └─ large.webp
    ↓
AIC_Converter::process_original_image() 钩子触发
    └─ vacation.jpg → vacation.webp
    ↓
更新metadata['original_image'] = 'vacation.webp'
    ↓
✅ 完成：所有文件都是WebP格式
```

### 🔄 批量转换流程

```
用户点击"批量转换为WebP"
    ↓
前端：batch-convert.js 递归处理
    ├─ 显示进度条
    └─ 逐个发送AJAX请求
    ↓
后端：AIC_Batch_Processor::ajax_batch_convert()
    ├─ 转换原始大图
    ├─ 删除旧缩略图
    ├─ 转换主图
    ├─ 重新生成WebP缩略图
    └─ 更新元数据
    ↓
前端：更新进度和结果显示
    ├─ 成功：绿色 ✓
    └─ 失败：红色 ✗
    ↓
✅ 完成：显示统计结果
```

---

## 🔧 安装使用

### 安装
1. 上传插件文件夹到 `/wp-content/plugins/`
2. 在WordPress后台激活插件
3. 进入 `设置 > ImgConverter` 配置

### 配置
- **启用自动转换**：上传图片时自动转换为WebP
- **图片质量**：设置1-100（推荐70-90）
- **保留原图**：可选择是否保留原始文件

### 批量转换现有图片
1. 进入 `设置 > ImgConverter`
2. 点击"开始扫描"
3. 查看扫描结果
4. 点击"批量转换为WebP"
5. 等待进度完成

---

## 💡 设计优势

### 1. **模块化架构**
- 每个类专注单一职责
- 易于维护和扩展
- 降低代码耦合度

### 2. **代码复用**
- 核心转换函数只写一次
- 所有场景共享相同逻辑
- 统一的质量控制

### 3. **WordPress最佳实践**
- 遵循WordPress编码标准
- 正确使用钩子系统
- 安全的AJAX处理
- 完整的元数据管理

### 4. **性能优化**
- 单图转换：避免重复代码
- AJAX队列：避免服务器超载
- 条件检查：只在必要时转换
- 前端优化：无需刷新页面

---

## 🛡️ 安全机制

- ✅ 权限检查：`current_user_can('manage_options')`
- ✅ Nonce验证：防止CSRF攻击
- ✅ 路径验证：使用WordPress API
- ✅ 错误处理：完善的异常处理
- ✅ 文件验证：检查文件存在性

---

## 📊 元数据管理

插件正确处理WordPress附件元数据：

```php
wp_postmeta:
{
    'file': '2024/11/photo-scaled.webp',
    'original_image': 'photo.webp',  // ← 关键字段
    'width': 2560,
    'height': 1536,
    'sizes': {
        'thumbnail': {file: 'photo-150x150.webp'},
        'medium': {file: 'photo-300x200.webp'},
        'large': {file: 'photo-1024x683.webp'}
    }
}
```

**元数据策略：**
- 上传时：自动更新 `original_image`
- 批量转换：手动更新元数据
- 删除时：WordPress自动清理相关文件

---

## 🔍 技术特性

### 支持的图片格式
- JPEG / JPG
- PNG
- GIF

### 转换引擎
- GD扩展
- Imagick扩展

### 兼容性
- WordPress 5.0+
- PHP 7.0+

---

## 📝 开发者指南

### 添加新功能

#### 添加新的转换格式
修改 `includes/class-converter.php`：
```php
if (in_array($upload['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/bmp'])) {
    // 添加新格式处理
}
```

#### 添加新的设置项
1. 在 `includes/class-settings.php` 注册设置
2. 添加字段回调函数
3. 在设置页面显示

#### 修改AJAX处理
编辑 `includes/class-batch-processor.php` 和 `assets/js/batch-convert.js`

---

## 🐛 调试

### 启用WordPress调试
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### 检查转换状态
查看 `wp-content/debug.log`

### 常见问题

**Q: 图片没有转换？**
A: 检查服务器是否安装GD或Imagick扩展

**Q: 批量转换失败？**
A: 检查PHP内存限制和执行时间

**Q: 原始大图没有转换？**
A: 确保WordPress版本 ≥ 5.3（支持scaled机制）

---

## 📄 许可证

GPL v2 or later

---

## 👨‍💻 维护建议

1. **修改核心逻辑**：只需修改对应的类文件
2. **调试**：可以单独测试每个组件
3. **添加功能**：考虑创建新的类文件
4. **版本更新**：更新主文件中的版本号

---

## 🎯 项目目标

提供一个简洁、高效、易于维护的WordPress图片转换解决方案，帮助网站提升加载速度和用户体验。

---