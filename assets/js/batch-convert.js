/**
 * 批量转换前端脚本
 *
 * 处理批量转换的用户交互和AJAX请求
 */

jQuery(document).ready(function($) {
    // 全选/取消全选
    $('#select-all-dirs').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.dir-checkbox, .image-checkbox').prop('checked', isChecked);
        updateTotalCheckedCount();
    });

    // 文件夹勾选/取消勾选
    $('.dir-checkbox').on('change', function() {
        var isChecked = $(this).prop('checked');
        var dirItem = $(this).closest('.dir-item');
        dirItem.find('.image-checkbox').prop('checked', isChecked);
        checkAllDirsSelected();
        updateTotalCheckedCount();
    });

    // 个别图片勾选/取消勾选
    $('.image-checkbox').on('change', function() {
        var dirItem = $(this).closest('.dir-item');
        var allChecked = dirItem.find('.image-checkbox:checked').length === dirItem.find('.image-checkbox').length;
        dirItem.find('.dir-checkbox').prop('checked', allChecked);
        checkAllDirsSelected();
        updateTotalCheckedCount();
    });

    function checkAllDirsSelected() {
        var allChecked = $('.dir-checkbox:checked').length === $('.dir-checkbox').length;
        $('#select-all-dirs').prop('checked', allChecked);
    }

    function updateTotalCheckedCount() {
        var count = $('.image-checkbox:checked').length;
        $('#total-images').text(count);
    }

    // 初始运行一次，更新总数统计
    updateTotalCheckedCount();

    $('#batch-convert-btn').on('click', function() {
        var btn = $(this);

        // 仅获取已勾选的图片元素
        var imageListItems = $('.image-item').filter(function() {
            return $(this).find('.image-checkbox').is(':checked');
        });

        var totalImages = imageListItems.length;
        var converted = 0;
        var failed = 0;

        if (totalImages === 0) {
            alert('请先勾选需要转换的图片');
            return;
        }

        btn.prop('disabled', true);
        $('.dir-checkbox, .image-checkbox, #select-all-dirs').prop('disabled', true);
        $('#conversion-progress').show();

        /**
         * 递归转换图片
         */
        function convertNext(index) {
            if (index >= totalImages) {
                $('#progress-text').html('<strong style="color: green;">转换完成！</strong> 成功: ' + converted + ', 失败: ' + failed);
                btn.prop('disabled', false);
                $('.dir-checkbox, .image-checkbox, #select-all-dirs').prop('disabled', false);

                // 移除已转换成功的项目和空文件夹
                $('.image-item').each(function() {
                    if ($(this).css('color') === 'rgb(0, 128, 0)' || $(this).css('color') === 'green') {
                        $(this).remove();
                    }
                });

                $('.dir-item').each(function() {
                    var remainingItems = $(this).find('.image-item').length;
                    if (remainingItems === 0) {
                        $(this).remove();
                    } else {
                        // 更新文件夹显示的剩余数量
                        var dirProgress = $(this).find('.js-dir-progress');
                        dirProgress.attr('data-total', remainingItems);
                        dirProgress.attr('data-remaining', remainingItems);
                        $(this).find('.remaining-count').text(remainingItems);
                        // 修改总数显示
                        dirProgress.html('剩余: <span class="remaining-count">' + remainingItems + '</span> / ' + remainingItems);
                    }
                });

                // 更新整体计数
                updateTotalCheckedCount();

                if ($('.image-item').length === 0) {
                    $('#dir-list').parent().html('<p style="color: green; font-weight: bold;">所有选中的图片已成功转换为WebP格式！</p>');
                }

                return;
            }

            var currentItem = imageListItems.eq(index);
            var attachmentId = currentItem.data('id');
            var fileName = currentItem.find('span').text(); // 由于加了checkbox，需要找span

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
                        currentItem.css('color', 'green').find('span').append(' ✓');

                        // 更新当前文件夹统计
                        var dirItem = currentItem.closest('.dir-item');
                        var dirProgress = dirItem.find('.js-dir-progress');
                        var remaining = parseInt(dirProgress.attr('data-remaining')) - 1;
                        dirProgress.attr('data-remaining', remaining);
                        dirItem.find('.remaining-count').text(remaining);

                    } else {
                        failed++;
                        currentItem.css('color', 'red').find('span').append(' ✗ (' + (response.data || '失败') + ')');
                    }
                },
                error: function() {
                    failed++;
                    currentItem.css('color', 'red').find('span').append(' ✗');
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

