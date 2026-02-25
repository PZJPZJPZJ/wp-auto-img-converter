/**
 * 批量转换前端脚本
 * 
 * 处理批量转换的用户交互和AJAX请求
 */

jQuery(document).ready(function($) {
    $('#batch-convert-btn').on('click', function() {
        var btn = $(this);
        
        var imageList = $('#image-list li');
        var totalImages = imageList.length;
        var converted = 0;
        var failed = 0;

        if (totalImages === 0) {
            alert('没有需要转换的图片');
            return;
        }

        btn.prop('disabled', true);
        $('#conversion-progress').show();

        /**
         * 递归转换图片
         */
        function convertNext(index) {
            if (index >= totalImages) {
                $('#progress-text').html('<strong style="color: green;">转换完成！</strong> 成功: ' + converted + ', 失败: ' + failed);
                btn.prop('disabled', false);
                
                // 移除已转换成功的项目
                $('#image-list li').each(function() {
                    if ($(this).css('color') === 'rgb(0, 128, 0)' || $(this).css('color') === 'green') {
                        $(this).remove();
                    }
                });
                
                // 更新计数
                var remaining = $('#image-list li').length;
                $('#total-images').text(remaining);
                
                if (remaining === 0) {
                    $('#image-list').parent().html('<p style="color: green; font-weight: bold;">所有图片已成功转换为WebP格式！</p>');
                }
                
                return;
            }

            var currentItem = imageList.eq(index);
            var attachmentId = currentItem.data('id');
            var fileName = currentItem.text();

            $('#progress-text').html('正在转换: ' + fileName + ' (' + (index + 1) + '/' + totalImages + ')');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aic_batch_convert',
                    attachment_id: attachmentId,
                    nonce: aicData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        converted++;
                        currentItem.css('color', 'green').append(' ✓');
                    } else {
                        failed++;
                        currentItem.css('color', 'red').append(' ✗ (' + (response.data || '失败') + ')');
                    }
                },
                error: function() {
                    failed++;
                    currentItem.css('color', 'red').append(' ✗');
                },
                complete: function() {
                    var progress = Math.round(((index + 1) / totalImages) * 100);
                    $('#progress-bar').css('width', progress + '%').text(progress + '%');
                    convertNext(index + 1);
                }
            });
        }

        convertNext(0);
    });
});

