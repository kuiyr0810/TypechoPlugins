<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 高德地图插件
 *
 * @package AMapPlugin
 * @author 王叨叨
 * @version 1.2.0
 * @link https://wangdaodao.com/
 */
class AMapPlugin_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('AMapPlugin_Plugin', 'injectMap');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('AMapPlugin_Plugin', 'insertButton');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('AMapPlugin_Plugin', 'insertButton');
        return _t('高德地图插件已激活，请在文章中使用指定格式插入地图。');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        return _t('高德地图插件已被禁用。');
    }

    /**
     * 插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text('apiKey', NULL, '', _t('高德地图API Key'), _t('请输入你在高德开放平台申请到的Web端（JSAPI）Key。'));
        $form->addInput($apiKey->addRule('required', _t('API Key 是必填项')));

        $defaultZoom = new Typecho_Widget_Helper_Form_Element_Text('defaultZoom', NULL, '15', _t('默认缩放级别'), _t('地图的默认缩放级别，范围1-18，数字越大缩放级别越高'));
        $form->addInput($defaultZoom->addRule('required', _t('默认缩放级别是必填项')));

        $viewMode = new Typecho_Widget_Helper_Form_Element_Select('viewMode',
            array('2D' => '2D模式', '3D' => '3D模式'),
            '2D', _t('默认视图模式'), _t('选择地图默认显示模式'));
        $form->addInput($viewMode);

        $mapTheme = new Typecho_Widget_Helper_Form_Element_Select('mapTheme',
            array(
                'normal' => '标准 (normal)',
                'dark' => '幻影黑 (dark)',
                'light' => '月光银 (light)',
                'whitesmoke' => '远山黛 (whitesmoke)',
                'fresh' => '草色青 (fresh)',
                'grey' => '雅士灰 (grey)',
                'graffiti' => '涂鸦 (graffiti)',
                'macaron' => '马卡龙 (macaron)',
                'blue' => '靛青蓝 (blue)',
                'darkblue' => '极夜蓝 (darkblue)',
                'wine' => '酱籽 (wine)'
            ),
            'normal', _t('默认地图主题'), _t('选择地图的默认主题样式'));
        $form->addInput($mapTheme);

        $maxMarkers = new Typecho_Widget_Helper_Form_Element_Text('maxMarkers', NULL, '500', _t('最大标记点数量'), _t('单次可添加的最大标记点数量'));
        $form->addInput($maxMarkers->addRule('isInteger', _t('请输入整数')));

        $defaultWidth = new Typecho_Widget_Helper_Form_Element_Text('defaultWidth', NULL, '100%', _t('默认地图宽度'), _t('地图的默认宽度，可以是百分比或像素值，如100%或800px'));
        $form->addInput($defaultWidth);

        $defaultHeight = new Typecho_Widget_Helper_Form_Element_Text('defaultHeight', NULL, '400px', _t('默认地图高度'), _t('地图的默认高度，建议使用像素值，如400px'));
        $form->addInput($defaultHeight);
    }

    /**
     * 个人用户的配置面板（可留空）
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 输出地图HTML和脚本
     */
    public static function renderMap($viewMode, $zoom, $apiKey, $markersData, $theme = 'normal', $width = '100%', $height = '400px')
    {
        $mapId = 'amap-container-' . uniqid();

        // 计算地图中心点
        $centerLng = 116.397428; // 默认北京
        $centerLat = 39.90923;

        if (!empty($markersData)) {
            $totalLng = 0;
            $totalLat = 0;
            $count = 0;

            foreach ($markersData as $marker) {
                if (!empty($marker['lng']) && !empty($marker['lat'])) {
                    $totalLng += $marker['lng'];
                    $totalLat += $marker['lat'];
                    $count++;
                }
            }

            if ($count > 0) {
                $centerLng = $totalLng / $count;
                $centerLat = $totalLat / $count;
            }
        }

        // 构建标记点数据
        $markersJson = json_encode($markersData);
        // 从文件头部注释中动态提取版本号
        $fileContent = file_get_contents(__FILE__);
        if (preg_match('/\*\s*@version\s+([\d\.]+)/', $fileContent, $matches)) {
            $version = $matches[1];
        } else {
            $version = '1.0.0'; // 默认版本号作为后备
        }

        $html = <<<HTML
<div id="{$mapId}" style="width: {$width}; height: {$height}; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;"></div>

<script id="amap-script" src="https://webapi.amap.com/maps?v=2.0&key={$apiKey}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.info('%cAMapPlugin v{$version}%chttps://wangdaodao.com/', 'color: #013821; background: #43bb88; padding:5px; font-size: 12px; font-weight: bold;','color: #fadfa3; background: #030307; padding:5px; font-size: 12px; font-weight: bold;');
    var map = new AMap.Map('{$mapId}', {
        zoom: {$zoom},
        center: [{$centerLng}, {$centerLat}],
        viewMode: '{$viewMode}',
        pitch: '{$viewMode}' === '3D' ? 45 : 0,
        mapStyle: 'amap://styles/{$theme}'
    });

    var markersData = {$markersJson};

    // 创建标记点
    function createMarkers() {
        markersData.forEach(function(markerData, index) {
            if (!markerData.lng || !markerData.lat) return;

            var lng = parseFloat(markerData.lng);
            var lat = parseFloat(markerData.lat);
            var text = markerData.text || '';
            var iconUrl = markerData.icon || '';

            // 创建标记点选项
            var markerOptions = {
                position: [lng, lat],
                map: map
            };

            // 如果有文本，添加到标题
            if (text) {
                markerOptions.title = text;
            }

            // 如果有图标URL，创建自定义图标
            if (iconUrl) {
                // 预加载图片
                var img = new Image();
                img.onload = function() {
                    // 图片加载成功，创建自定义图标
                    var customIcon = new AMap.Icon({
                        image: iconUrl,
                        size: new AMap.Size(32, 32),
                        imageSize: new AMap.Size(32, 32)
                    });

                    markerOptions.icon = customIcon;
                    createMarkerWithOptions(markerOptions, text, lng, lat);
                };

                img.onerror = function() {
                    // 图片加载失败，使用默认图标
                    console.warn('图标加载失败: ' + iconUrl);
                    createMarkerWithOptions(markerOptions, text, lng, lat);
                };

                img.src = iconUrl;
            } else {
                // 没有图标，直接创建标记
                createMarkerWithOptions(markerOptions, text, lng, lat);
            }
        });
    }

    // 使用选项创建标记点并添加信息窗口
    function createMarkerWithOptions(options, text, lng, lat) {
        var marker = new AMap.Marker(options);

        // 如果有文本，添加信息窗口
        if (text) {
            var infoWindow = new AMap.InfoWindow({
                content: '<div style="padding: 8px; max-width: 200px; font-size: 14px;">' +
                         '<strong style="color: #333;">' + text + '</strong><br/>' +
                         '</div>',
                offset: new AMap.Pixel(0, -35)
            });

            marker.on('click', function() {
                infoWindow.open(map, marker.getPosition());
            });
        }
    }

    // 初始化标记点
    createMarkers();
});
</script>
HTML;

        return $html;
    }

    /**
     * 解析文章内容并注入地图
     */
    public static function injectMap($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;

        $options = Typecho_Widget::widget('Widget_Options');
        $pluginOptions = $options->plugin('AMapPlugin');
        $apiKey = $pluginOptions->apiKey;
        $defaultViewMode = isset($pluginOptions->viewMode) ? $pluginOptions->viewMode : '2D';
        $defaultTheme = isset($pluginOptions->mapTheme) ? $pluginOptions->mapTheme : 'normal';
        $defaultWidth = isset($pluginOptions->defaultWidth) ? $pluginOptions->defaultWidth : '100%';
        $defaultHeight = isset($pluginOptions->defaultHeight) ? $pluginOptions->defaultHeight : '400px';

        // 匹配新的短代码格式
        $pattern = '/\[amap(?:\s+view=(\w+))?(?:\s+zoom=(\d+))?(?:\s+theme=(\w+))?(?:\s+width=([^\s]+))?(?:\s+height=([^\s]+))?(.*?)\]/i';

        $content = preg_replace_callback($pattern, function($matches) use ($apiKey, $defaultViewMode, $defaultTheme, $defaultWidth, $defaultHeight) {
            $viewMode = !empty($matches[1]) ? $matches[1] : $defaultViewMode;
            $zoom = !empty($matches[2]) ? intval($matches[2]) : 15;
            $theme = !empty($matches[3]) ? $matches[3] : $defaultTheme;
            $width = !empty($matches[4]) ? $matches[4] : $defaultWidth;
            $height = !empty($matches[5]) ? $matches[5] : $defaultHeight;
            $attributes = isset($matches[6]) ? $matches[6] : '';

            // 解析标记点
            $markersData = [];
            preg_match_all('/marker(\d+)=([\'"])([^\2]*?)\2/', $attributes, $markerMatches);

            foreach ($markerMatches[3] as $markerStr) {
                $parts = explode(',', $markerStr);
                if (count($parts) >= 2) {
                    // 处理不同格式的标记点
                    $lng = trim($parts[0]);
                    $lat = trim($parts[1]);
                    $text = '';
                    $icon = '';

                    // 如果有文本部分
                    if (isset($parts[2])) {
                        // 检查是否为空字符串（连续两个逗号的情况）
                        if ($parts[2] === '') {
                            $text = '';

                            // 如果有图标部分（在空文本之后）
                            if (isset($parts[3])) {
                                $icon = urldecode(trim($parts[3]));
                            }
                        } else {
                            // 正常的文本内容
                            $text = urldecode(trim($parts[2]));

                            // 如果有图标部分
                            if (isset($parts[3])) {
                                $icon = urldecode(trim($parts[3]));
                            }
                        }
                    }

                    $markersData[] = [
                        'lng' => $lng,
                        'lat' => $lat,
                        'text' => $text,
                        'icon' => $icon
                    ];
                }
            }

            // 如果没有标记点，尝试解析旧格式
            if (empty($markersData)) {
                preg_match('/lng=([\d\.]+)\s+lat=([\d\.]+)(?:\s+marker=([^\s]+))?/', $attributes, $oldMatches);
                if (!empty($oldMatches)) {
                    $markersData[] = [
                        'lng' => $oldMatches[1],
                        'lat' => $oldMatches[2],
                        'text' => isset($oldMatches[3]) ? $oldMatches[3] : '',
                        'icon' => ''
                    ];
                }
            }

            if (!empty($markersData)) {
                return AMapPlugin_Plugin::renderMap($viewMode, $zoom, $apiKey, $markersData, $theme, $width, $height);
            }

            return $matches[0]; // 解析失败返回原内容
        }, $content);

        return $content;
    }

    /**
     * 在编辑器页面插入资源
     */
    public static function insertButton()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $pluginOptions = $options->plugin('AMapPlugin');
        // 获取插件URL路径
        $pluginUrl = Helper::options()->pluginUrl.'/AMapPlugin/assets/editer.js';

        // 获取插件配置
        $defaultViewMode = isset($pluginOptions->viewMode) ? $pluginOptions->viewMode : '2D';
        $defaultTheme = isset($pluginOptions->mapTheme) ? $pluginOptions->mapTheme : 'normal';
        $defaultZoom = isset($pluginOptions->defaultZoom) ? intval($pluginOptions->defaultZoom) : 15;
        $defaultWidth = isset($pluginOptions->defaultWidth) ? $pluginOptions->defaultWidth : '100%';
        $defaultHeight = isset($pluginOptions->defaultHeight) ? $pluginOptions->defaultHeight : '400px';

        // 构建选项的选中状态
        $viewMode2DSelected = $defaultViewMode === '2D' ? ' selected' : '';
        $viewMode3DSelected = $defaultViewMode === '3D' ? ' selected' : '';

        // 构建主题选项
        $themeOptions = '';
        $themes = [
            'normal' => '标准 (normal)',
            'dark' => '幻影黑 (dark)',
            'light' => '月光银 (light)',
            'whitesmoke' => '远山黛 (whitesmoke)',
            'fresh' => '草色青 (fresh)',
            'grey' => '雅士灰 (grey)',
            'graffiti' => '涂鸦 (graffiti)',
            'macaron' => '马卡龙 (macaron)',
            'blue' => '靛青蓝 (blue)',
            'darkblue' => '极夜蓝 (darkblue)',
            'wine' => '酱籽 (wine)'
        ];

        foreach ($themes as $value => $label) {
            $selected = $defaultTheme === $value ? ' selected' : '';
            $themeOptions .= "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
        }

        echo <<<HTML
<!-- 地图插入模态框 -->
<div id="amapModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border:1px solid #ccc; border-radius:8px; z-index:10000; box-shadow:0 4px 20px rgba(0,0,0,0.15); width: 500px; max-height: 80vh; overflow-y: auto;">
    <h3 style="margin:0 0 20px 0; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">插入高德地图</h3>
    <p>使用<a href="https://lbs.amap.com/tools/picker" target="_blank">高德地图经纬度选择工具</a>获取经纬度</p>
    <div class="amap-form">
        <div class="form-group">
            <label for="amapViewMode" style="display:inline-block; width:80px;">视图模式：</label>
            <select id="amapViewMode" style="padding:6px; border:1px solid #ddd; border-radius:4px;">
                <option value="2D"{$viewMode2DSelected}>2D模式</option>
                <option value="3D"{$viewMode3DSelected}>3D模式</option>
            </select>
        </div>
        <div class="form-group">
            <label for="amapTheme" style="display:inline-block; width:80px;">地图主题：</label>
            <select id="amapTheme" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:200px;">
{$themeOptions}            </select>
        </div>
        <div class="form-group">
            <label for="amapZoom" style="display:inline-block; width:80px;">缩放级别：</label>
            <input type="number" id="amapZoom" value="{$defaultZoom}" min="1" max="18" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:80px;">
            <small style="color:#666; margin-left:8px;">1-18</small>
        </div>
        <div class="form-group">
            <label for="amapWidth" style="display:inline-block; width:80px;">地图宽度：</label>
            <input type="text" id="amapWidth" value="{$defaultWidth}" placeholder="如100%或800px" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:120px;">
            <small style="color:#666; margin-left:8px;">如100%或800px</small>
        </div>
        <div class="form-group">
            <label for="amapHeight" style="display:inline-block; width:80px;">地图高度：</label>
            <input type="text" id="amapHeight" value="{$defaultHeight}" placeholder="如400px" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:120px;">
            <small style="color:#666; margin-left:8px;">如400px</small>
        </div>

        <div id="markersContainer">
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
        </div>

        <button type="button" id="addMarkerBtn" style="background:#f5f5f5; border:1px solid #ddd; padding:6px 12px; border-radius:4px; cursor:pointer; margin:5px 0;">+ 添加标记点</button>

        <div style="margin-top:20px; padding-top:15px; border-top:1px solid #eee; text-align:right;">
            <button id="amapConfirm" style="background:#467B96; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; margin-left:8px;">确认插入</button>
            <button id="amapCancel" style="background:#f5f5f5; border:1px solid #ddd; padding:8px 16px; border-radius:4px; cursor:pointer; margin-left:8px;">取消</button>
        </div>
    </div>
</div>
<div id="amapModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;"></div>

<script src="{$pluginUrl}"></script>
HTML;
    }
}
?>