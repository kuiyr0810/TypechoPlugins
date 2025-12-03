/**
 * 高德地图插件编辑器扩展
 */

// 标记点计数器
let markerCount = 1;

$(function () {
    // 等待编辑器加载完成
    setTimeout(function() {
        if ($('#wmd-button-row').length > 0) {
            // 添加地图按钮到工具栏
            $('#wmd-button-row').append(
                '<li class="wmd-spacer wmd-spacer1"></li><li class="wmd-button" id="amap-add" title="插入高德地图">🌍</li>'
            );

            // 绑定点击事件
            $('#amap-add').click(function () {
                amapShowMapModal();
            });
        }

        // 初始化模态框事件绑定
        amapInitModalEvents();
    }, 100);
});

/**
 * 初始化模态框事件
 */
function amapInitModalEvents() {
    // 添加标记点按钮
    $('#addMarkerBtn').off('click').on('click', function() {
        amapAddMarkerField();
    });

    // 确认插入按钮
    $('#amapConfirm').off('click').on('click', function() {
        amapInsertMapShortcode();
    });

    // 取消按钮
    $('#amapCancel').off('click').on('click', function() {
        amapHideMapModal();
    });

    // 遮罩层点击
    $('#amapModalOverlay').off('click').on('click', function() {
        amapHideMapModal();
    });
}

/**
 * 显示地图插入模态框
 */
function amapShowMapModal() {
    $('#amapModal').show();
    $('#amapModalOverlay').show();
    $('.marker-lng').first().focus();
    markerCount = 1; // 重置计数器

    // 重新绑定事件，确保新添加的按钮有效
    amapInitModalEvents();
}

/**
 * 隐藏地图插入模态框
 */
function amapHideMapModal() {
    $('#amapModal').hide();
    $('#amapModalOverlay').hide();
    // 重置表单到插件配置的默认值
    amapResetModalToDefaults();
}

/**
 * 重置模态框到插件配置的默认值
 */
function amapResetModalToDefaults() {
    // 清空所有输入框，只保留第一个标记点
    $('#markersContainer').html(`
        <div class="marker-group" style="border:1px solid #eee; padding:15px; margin:10px 0; border-radius:4px;">
            <h4 style="margin:0 0 10px 0; color:#555;">标记点 1</h4>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">经度：</label>
                <input type="text" class="marker-lng" placeholder="经度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">纬度：</label>
                <input type="text" class="marker-lat" placeholder="纬度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">标记文本：</label>
                <input type="text" class="marker-text" placeholder="位置名称" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">图标URL：</label>
                <input type="text" class="marker-icon" placeholder="图标URL" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
                <small style="color:#666; display:block; margin-left:80px; margin-top:4px;">支持PNG、JPG格式，建议32x32像素</small>
            </div>
        </div>
    `);
}

/**
 * 添加新的标记点输入框
 */
function amapAddMarkerField() {
    markerCount++;

    // 简单限制最大数量
    if (markerCount > 500) {
        alert('最多只能添加500个标记点');
        markerCount--;
        return;
    }

    const newMarker = `
        <div class="marker-group" style="border:1px solid #eee; padding:15px; margin:10px 0; border-radius:4px;">
            <h4 style="margin:0 0 10px 0; color:#555;">
                标记点 ${markerCount}
                <button type="button" class="remove-marker" style="background:#ff4757; color:white; border:none; padding:2px 8px; border-radius:3px; cursor:pointer; font-size:12px; margin-left:10px;">删除</button>
            </h4>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">经度：</label>
                <input type="text" class="marker-lng" placeholder="经度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">纬度：</label>
                <input type="text" class="marker-lat" placeholder="纬度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">标记文本：</label>
                <input type="text" class="marker-text" placeholder="位置名称" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">图标URL：</label>
                <input type="text" class="marker-icon" placeholder="图标URL" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
                <small style="color:#666; display:block; margin-left:80px; margin-top:4px;">支持PNG、JPG格式，建议32x32像素</small>
            </div>
        </div>
    `;

    $('#markersContainer').append(newMarker);
}

/**
 * 删除标记点输入框
 */
function amapRemoveMarkerField(button) {
    if (markerCount > 1) {
        $(button).closest('.marker-group').remove();
        markerCount--;
        // 重新编号
        $('.marker-group').each(function(index) {
            $(this).find('h4').html(`标记点 ${index + 1} ${index > 0 ? '<button type="button" class="remove-marker" style="background:#ff4757; color:white; border:none; padding:2px 8px; border-radius:3px; cursor:pointer; font-size:12px; margin-left:10px;">删除</button>' : ''}`);
        });
    }
}

/**
 * 插入地图短代码到编辑器
 */
function amapInsertMapShortcode() {
    const viewMode = $('#amapViewMode').val();
    const theme = $('#amapTheme').val();
    const zoom = $('#amapZoom').val().trim() || '15';
    const width = $('#amapWidth').val().trim() || '100%';
    const height = $('#amapHeight').val().trim() || '400px';

    // 验证缩放级别
    const zoomNum = parseInt(zoom);
    if (isNaN(zoomNum) || zoomNum < 1 || zoomNum > 18) {
        alert('缩放级别必须是1-18之间的整数！');
        return false;
    }

    // 收集所有标记点数据
    const markers = [];
    let hasValidMarker = false;

    $('.marker-group').each(function() {
        const lng = $(this).find('.marker-lng').val().trim();
        const lat = $(this).find('.marker-lat').val().trim();
        const text = $(this).find('.marker-text').val().trim();
        const icon = $(this).find('.marker-icon').val().trim();

        // 验证经纬度
        if (lng && lat) {
            if (isNaN(parseFloat(lng)) || isNaN(parseFloat(lat))) {
                alert('请输入有效的经纬度数值！');
                return false;
            }

            markers.push({
                lng: lng,
                lat: lat,
                text: text,
                icon: icon
            });
            hasValidMarker = true;
        }
    });

    if (!hasValidMarker) {
        alert('请至少输入一个有效的标记点（经纬度）！');
        return false;
    }

    // 生成短代码
    let shortcode = `[amap view=${viewMode} zoom=${zoom} theme=${theme} width=${width} height=${height}`;

    markers.forEach((marker, index) => {
        // 对文本和图标URL进行编码，避免特殊字符问题
        const text = marker.text || '';
        let icon = marker.icon || '';

        // 去掉图标URL中的 https: 或 http: 前缀
        if (icon) {
            icon = icon.replace(/^https?:/, '');
        }

        // 构建标记字符串，根据是否有文本和图标决定逗号数量
        let markerStr = `${marker.lng},${marker.lat}`;

        // 如果有文本，添加逗号和文本
        if (text) {
            markerStr += ',' + text;

            // 如果有图标，再添加逗号和图标
            if (icon) {
                markerStr += ',' + icon;
            }
        } else if (icon) {
            // 如果没有文本但有图标，添加逗号和图标
            markerStr += ',,' + icon;
        }

        shortcode += ` marker${index + 1}="${markerStr}"`;
    });

    shortcode += ']';

    // 插入到编辑器
    amapInsertIntoEditor(shortcode);
    amapHideMapModal();
    return true;
}

/**
 * 插入文本到编辑器
 */
function amapInsertIntoEditor(text) {
    const myField = document.getElementById('text');
    if (!myField) {
        alert('无法找到编辑器！');
        return false;
    }

    if (document.selection) {
        // IE浏览器
        myField.focus();
        const sel = document.selection.createRange();
        sel.text = text;
        myField.focus();
    } else if (myField.selectionStart || myField.selectionStart === 0) {
        // 现代浏览器
        const startPos = myField.selectionStart;
        const endPos = myField.selectionEnd;
        const cursorPos = startPos;
        myField.value = myField.value.substring(0, startPos) + text + myField.value.substring(endPos, myField.value.length);
        myField.focus();
        myField.selectionStart = cursorPos + text.length;
        myField.selectionEnd = cursorPos + text.length;
    } else {
        // 备用方案
        myField.value += text;
        myField.focus();
    }
}

// 使用事件委托来处理动态添加的元素
$(document).ready(function() {
    // 事件委托：删除标记点按钮
    $(document).on('click', '.remove-marker', function() {
        amapRemoveMarkerField(this);
    });

    // ESC键关闭
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) {
            amapHideMapModal();
        }
    });

    // 输入框回车键支持 - 事件委托
    $(document).on('keypress', '.marker-lng, .marker-lat, .marker-text, .marker-icon', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const $next = $(this).closest('.form-group').next().find('input');
            if ($next.length) {
                $next.focus();
            } else {
                $(this).closest('.marker-group').next().find('.marker-lng').focus();
            }
        }
    });

    // 最后一个输入框回车确认插入 - 事件委托
    $(document).on('keypress', '.marker-group:last .marker-icon', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            amapInsertMapShortcode();
        }
    });
});