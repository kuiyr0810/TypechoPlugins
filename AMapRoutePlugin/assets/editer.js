/**
 * 高德地图路径规划插件编辑器扩展
 */

// 路径点计数器
let routePointCount = 2;

$(function () {
    // 等待编辑器加载完成
    setTimeout(function() {
        if ($('#wmd-button-row').length > 0) {
            // 添加路径规划按钮到工具栏
            $('#wmd-button-row').append(
                '<li class="wmd-spacer wmd-spacer1"></li><li class="wmd-button" id="route-add" title="插入路径规划">🚗</li>'
            );

            // 绑定点击事件
            $('#route-add').click(function () {
                showRouteModal();
            });
        }

        // 初始化模态框事件绑定
        initRouteModalEvents();
    }, 100);
});

/**
 * 初始化路径规划模态框事件
 */
function initRouteModalEvents() {
    // 添加途经点按钮
    $('#addRoutePointBtn').off('click').on('click', function() {
        addRoutePointField();
    });

    // 确认插入按钮
    $('#routeConfirm').off('click').on('click', function() {
        insertRouteShortcode();
    });

    // 取消按钮
    $('#routeCancel').off('click').on('click', function() {
        hideRouteModal();
    });

    // 遮罩层点击
    $('#amapRouteModalOverlay').off('click').on('click', function() {
        hideRouteModal();
    });
}

/**
 * 显示路径规划模态框
 */
function showRouteModal() {
    $('#amapRouteModal').show();
    $('#amapRouteModalOverlay').show();
    $('.point-lng').first().focus();
    routePointCount = 2; // 重置计数器（起点和终点）
}

/**
 * 隐藏路径规划模态框
 */
function hideRouteModal() {
    $('#amapRouteModal').hide();
    $('#amapRouteModalOverlay').hide();
    resetRouteForm();
}

/**
 * 重置路径规划表单
 */
function resetRouteForm() {
    $('#routePointsContainer').html(`
        <div class="route-point-group" style="border:1px solid #eee; padding:15px; margin:10px 0; border-radius:4px; background:#f9f9f9;">
            <h4 style="margin:0 0 10px 0; color:#555;">起点</h4>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">经度：</label>
                <input type="text" class="point-lng" placeholder="经度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:180px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">纬度：</label>
                <input type="text" class="point-lat" placeholder="纬度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:180px;">
            </div>
        </div>

        <div class="route-point-group" style="border:1px solid #eee; padding:15px; margin:10px 0; border-radius:4px; background:#fff3cd;">
            <h4 style="margin:0 0 10px 0; color:#555;">终点</h4>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">经度：</label>
                <input type="text" class="point-lng" placeholder="经度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:180px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">纬度：</label>
                <input type="text" class="point-lat" placeholder="纬度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:180px;">
            </div>
        </div>
    `);
}

/**
 * 添加新的途经点输入框
 */
function addRoutePointField() {
    routePointCount++;

    // 限制最大数量
    if (routePointCount > 16) {
        alert('最多只能添加16个路径点');
        routePointCount--;
        return;
    }

    const newPoint = `
        <div class="route-point-group" style="border:1px solid #eee; padding:15px; margin:10px 0; border-radius:4px; background:#e7f3ff;">
            <h4 style="margin:0 0 10px 0; color:#555;">
                途经点 ${routePointCount - 2}
                <button type="button" class="remove-route-point" style="background:#ff4757; color:white; border:none; padding:2px 8px; border-radius:3px; cursor:pointer; font-size:12px; margin-left:10px;">删除</button>
            </h4>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">经度：</label>
                <input type="text" class="point-lng" placeholder="经度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:180px;">
            </div>
            <div class="form-group">
                <label style="display:inline-block; width:80px;">纬度：</label>
                <input type="text" class="point-lat" placeholder="纬度" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:180px;">
            </div>
        </div>
    `;

    // 在终点之前插入途经点
    $('#routePointsContainer .route-point-group:last').before(newPoint);
}

/**
 * 删除路径点输入框
 */
function removeRoutePointField(button) {
    if (routePointCount > 2) {
        $(button).closest('.route-point-group').remove();
        routePointCount--;
        // 重新编号途经点
        updateRoutePointsNumbering();
    }
}

/**
 * 更新途经点编号
 */
function updateRoutePointsNumbering() {
    $('.route-point-group').each(function(index) {
        if (index === 0) {
            $(this).find('h4').text('起点');
        } else if (index === $('.route-point-group').length - 1) {
            $(this).find('h4').text('终点');
        } else {
            $(this).find('h4').html(`途经点 ${index} <button type="button" class="remove-route-point" style="background:#ff4757; color:white; border:none; padding:2px 8px; border-radius:3px; cursor:pointer; font-size:12px; margin-left:10px;">删除</button>`);
        }
    });
}

/**
 * 插入路径规划短代码到编辑器
 */
function insertRouteShortcode() {
    const routeType = $('#routeType').val();
    const theme = $('#routeTheme').val();
    const zoom = $('#routeZoom').val().trim() || '12';
    const showTraffic = $('#showTraffic').is(':checked') ? '1' : '0';
    const width = $('#routeWidth').val().trim() || '100%';
    const height = $('#routeHeight').val().trim() || '400px';

    // 验证缩放级别
    const zoomNum = parseInt(zoom);
    if (isNaN(zoomNum) || zoomNum < 1 || zoomNum > 18) {
        alert('缩放级别必须是1-18之间的整数！');
        return false;
    }

    // 验证宽度和高度格式
    if (!width || !height) {
        alert('请输入地图宽度和高度！');
        return false;
    }

    // 收集所有路径点数据
    const points = [];
    let hasValidPoints = false;

    $('.route-point-group').each(function() {
        const lng = $(this).find('.point-lng').val().trim();
        const lat = $(this).find('.point-lat').val().trim();

        // 验证经纬度
        if (lng && lat) {
            if (isNaN(parseFloat(lng)) || isNaN(parseFloat(lat))) {
                alert('请输入有效的经纬度数值！');
                return false;
            }

            points.push({
                lng: lng,
                lat: lat
            });
            hasValidPoints = true;
        }
    });

    if (!hasValidPoints || points.length < 2) {
        alert('路径规划需要至少2个有效的路径点（起点和终点）！');
        return false;
    }

    // 生成短代码
    let shortcode = `[route type=${routeType} zoom=${zoom} theme=${theme} traffic=${showTraffic} width=${width} height=${height}`;

    points.forEach((point, index) => {
        const pointStr = `${point.lng},${point.lat}`;
        shortcode += ` point${index + 1}="${pointStr}"`;
    });

    shortcode += ']';

    // 插入到编辑器
    insertRouteIntoEditor(shortcode);
    hideRouteModal();
    return true;
}

/**
 * 插入路径规划文本到编辑器
 */
function insertRouteIntoEditor(text) {
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
    // 事件委托：删除路径点按钮
    $(document).on('click', '.remove-route-point', function() {
        removeRoutePointField(this);
    });

    // ESC键关闭
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) {
            hideRouteModal();
        }
    });

    // 输入框回车键支持 - 事件委托
    $(document).on('keypress', '.point-lng, .point-lat', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const $next = $(this).closest('.form-group').next().find('input');
            if ($next.length) {
                $next.focus();
            } else {
                $(this).closest('.route-point-group').next().find('.point-lng').focus();
            }
        }
    });
});