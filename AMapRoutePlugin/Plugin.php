<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 高德地图路径规划插件
 *
 * @package AMapRoutePlugin
 * @author 王叨叨
 * @version 1.2.0
 * @link https://wangdaodao.com/
 */
class AMapRoutePlugin_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('AMapRoutePlugin_Plugin', 'injectRoute');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('AMapRoutePlugin_Plugin', 'insertRouteButton');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('AMapRoutePlugin_Plugin', 'insertRouteButton');
        return _t('高德地图路径规划插件已激活，请在文章中使用指定格式插入路径规划。');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        return _t('高德地图路径规划插件已被禁用。');
    }

    /**
     * 插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text('apiKey', NULL, '', _t('高德地图API Key'), _t('请输入你在高德开放平台申请到的Web端（JSAPI）Key。'));
        $form->addInput($apiKey->addRule('required', _t('API Key 是必填项')));

        $securityJsCode = new Typecho_Widget_Helper_Form_Element_Text('securityJsCode', NULL, '', _t('高德地图安全密钥'), _t('请输入你在高德开放平台申请到的安全密钥（JSAPI）。<a href="https://lbs.amap.com/api/javascript-api-v2/guide/abc/prepare" target="_blank">如何获取？</a>'));
        $form->addInput($securityJsCode->addRule('required', _t('安全密钥是必填项')));

        $defaultZoom = new Typecho_Widget_Helper_Form_Element_Text('defaultZoom', NULL, '12', _t('默认缩放级别'), _t('地图的默认缩放级别，范围1-18，数字越大缩放级别越高'));
        $form->addInput($defaultZoom->addRule('required', _t('默认缩放级别是必填项')));

        $routeType = new Typecho_Widget_Helper_Form_Element_Select('routeType',
            array(
                'driving' => '驾车',
                'walking' => '步行',
                'bicycling' => '骑行',
                'transit' => '公交'
            ),
            'driving', _t('默认路径类型'), _t('选择默认的路径规划类型'));
        $form->addInput($routeType);

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

        $showTraffic = new Typecho_Widget_Helper_Form_Element_Select('showTraffic',
            array('1' => '显示', '0' => '不显示'),
            '1', _t('显示实时路况'), _t('是否显示实时路况信息'));
        $form->addInput($showTraffic);

        $maxWaypoints = new Typecho_Widget_Helper_Form_Element_Text('maxWaypoints', NULL, '16', _t('最大途经点数量'), _t('路径规划中最多可添加的途经点数量'));
        $form->addInput($maxWaypoints->addRule('isInteger', _t('请输入整数')));

        $defaultWidth = new Typecho_Widget_Helper_Form_Element_Text('defaultWidth', NULL, '100%', _t('默认地图宽度'), _t('地图的默认宽度，可以是百分比(如100%)或像素值(如800px)'));
        $form->addInput($defaultWidth);

        $defaultHeight = new Typecho_Widget_Helper_Form_Element_Text('defaultHeight', NULL, '400px', _t('默认地图高度'), _t('地图的默认高度，建议使用像素值(如400px)'));
        $form->addInput($defaultHeight);
    }

    /**
     * 个人用户的配置面板（可留空）
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 输出路径规划地图HTML和脚本
     */
    public static function renderRoute($routeType, $zoom, $apiKey, $pointsData, $showTraffic = true, $securityJsCode = '', $theme = 'normal', $width = '100%', $height = '400px')
{
    $mapId = 'amap-route-' . uniqid();

    // 计算地图中心点
    $centerLng = 116.397428;
    $centerLat = 39.90923;

    if (!empty($pointsData)) {
        $totalLng = 0;
        $totalLat = 0;
        $count = 0;

        foreach ($pointsData as $point) {
            if (!empty($point['lng']) && !empty($point['lat'])) {
                $totalLng += $point['lng'];
                $totalLat += $point['lat'];
                $count++;
            }
        }

        if ($count > 0) {
            $centerLng = $totalLng / $count;
            $centerLat = $totalLat / $count;
        }
    }

    // 构建点数据
    $pointsJson = json_encode($pointsData);
    $showTraffic = $showTraffic ? 'true' : 'false';

    // 从文件头部注释中动态提取版本号
    $fileContent = file_get_contents(__FILE__);
    if (preg_match('/\*\s*@version\s+([\d\.]+)/', $fileContent, $matches)) {
        $version = $matches[1];
    } else {
        $version = '1.0.0'; // 默认版本号作为后备
    }

    $html = <<<HTML
<div id="{$mapId}" style="width: {$width}; height: {$height}; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;"></div>
<script type="text/javascript">
  window._AMapSecurityConfig = {
    securityJsCode: "{$securityJsCode}",
  };
  console.info('%cAMapRoutePlugin v{$version}%chttps://wangdaodao.com/', 'color: #013821; background: #43bb88; padding:5px; font-size: 12px; font-weight: bold;','color: #fadfa3; background: #030307; padding:5px; font-size: 12px; font-weight: bold;');
</script>
<script id="amap-route-script" src="https://webapi.amap.com/maps?v=2.0&key={$apiKey}"></script>
<script id="amap-route-ui-script" src="https://webapi.amap.com/ui/1.1/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = new AMap.Map('{$mapId}', {
        zoom: {$zoom},
        center: [{$centerLng}, {$centerLat}],
        viewMode: '2D',
        mapStyle: 'amap://styles/{$theme}'
    });

    var pointsData = {$pointsJson};

    // 延迟加载以确保所有组件就绪
    setTimeout(function() {
        createRoutePlanning(map, pointsData, '{$routeType}', {$showTraffic});
    }, 500);
});

// 路径规划核心函数 - 修复版本
function createRoutePlanning(map, pointsData, routeType, showTraffic) {
    if (pointsData.length < 2) {
        console.error('路径规划需要至少2个点');
        return;
    }

    // 提取起点、终点和途经点
    var startPoint = [pointsData[0].lng, pointsData[0].lat];
    var endPoint = [pointsData[pointsData.length - 1].lng, pointsData[pointsData.length - 1].lat];
    var waypoints = [];

    // 添加途经点（排除起点和终点）
    for (var i = 1; i < pointsData.length - 1; i++) {
        waypoints.push([pointsData[i].lng, pointsData[i].lat]);
    }

    // 使用 AMapUI 加载 Driving 插件
    AMapUI.loadUI(['misc/PathSimplifier'], function(PathSimplifier) {
        if (!PathSimplifier.supportCanvas) {
            alert('当前环境不支持 Canvas！');
            return;
        }

        // 加载 Driving 插件
        AMap.plugin('AMap.Driving', function() {
            // 定义策略映射 - 避免直接使用 AMap.DrivingPolicy
            var policyMap = {
                'driving': 0, // AMap.DrivingPolicy.LEAST_TIME
                'walking': 2, // AMap.DrivingPolicy.LEAST_TIME (步行)
                'bicycling': 2, // AMap.DrivingPolicy.LEAST_TIME (骑行)
                'transit': 0 // AMap.DrivingPolicy.LEAST_TIME (公交)
            };

            // 安全的策略获取函数
            function getDrivingPolicy(routeType) {
                // 如果 AMap.DrivingPolicy 可用，使用标准策略
                if (window.AMap && AMap.DrivingPolicy) {
                    switch(routeType) {
                        case 'driving': return AMap.DrivingPolicy.LEAST_TIME;
                        case 'walking': return AMap.DrivingPolicy.LEAST_TIME;
                        case 'bicycling': return AMap.DrivingPolicy.LEAST_TIME;
                        case 'transit': return AMap.DrivingPolicy.LEAST_TIME;
                        default: return AMap.DrivingPolicy.LEAST_TIME;
                    }
                } else {
                    // 备用方案：使用数字策略
                    console.warn('AMap.DrivingPolicy 未定义，使用数字策略');
                    return policyMap[routeType] || 0;
                }
            }

            var policy = getDrivingPolicy(routeType);

            // 创建路线规划实例
            var route = new AMap.Driving({
                map: map,
                policy: policy,
                showTraffic: showTraffic,
                hideMarkers: false
            });

            // 执行路径规划
            route.search(startPoint, endPoint, {waypoints: waypoints}, function(status, result) {
                if (status === 'complete') {
                    console.log('路径规划完成');
                    // 添加自定义标记点
                } else {
                    console.error('路径规划失败: ', result);
                    // 显示错误信息
                    handleRouteError(status, result);
                    // 失败时显示直线连接
                    showDirectRoute(map, pointsData);
                }
            });
        });
    });
}

// 错误处理函数
function handleRouteError(status, result) {
    var errorMessages = {
        'error': '路径规划服务错误',
        'no_data': '没有找到路径规划结果',
        'over_distance': '起点终点距离过长',
        'over_waypoint_distance': '途经点距离过长',
        'invalid_from': '起点坐标错误',
        'invalid_to': '终点坐标错误',
        'invalid_waypoint': '途经点坐标错误'
    };

    var message = errorMessages[status] || '路径规划失败: ' + status;
    console.error(message, result);
}

// 显示直线连接（备用方案）
function showDirectRoute(map, pointsData) {
    var lineArr = [];

    pointsData.forEach(function(point) {
        lineArr.push([point.lng, point.lat]);
    });

    var polyline = new AMap.Polyline({
        path: lineArr,
        strokeColor: "#3366FF",
        strokeWeight: 5,
        strokeStyle: "solid"
    });

    map.add(polyline);
}
</script>
HTML;

    return $html;
}

    /**
     * 解析文章内容并注入路径规划
     */
    public static function injectRoute($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;

        $options = Typecho_Widget::widget('Widget_Options');
        $pluginOptions = $options->plugin('AMapRoutePlugin');
        $apiKey = $pluginOptions->apiKey;
        $securityJsCode = $pluginOptions->securityJsCode;
        $defaultRouteType = isset($pluginOptions->routeType) ? $pluginOptions->routeType : 'driving';
        $defaultShowTraffic = isset($pluginOptions->showTraffic) ? $pluginOptions->showTraffic : true;
        $defaultTheme = isset($pluginOptions->mapTheme) ? $pluginOptions->mapTheme : 'normal';
        $defaultWidth = isset($pluginOptions->defaultWidth) ? $pluginOptions->defaultWidth : '100%';
        $defaultHeight = isset($pluginOptions->defaultHeight) ? $pluginOptions->defaultHeight : '400px';

        // 匹配路径规划短代码格式 [route type=driving zoom=12 theme=normal traffic=1 width=100% height=400px point1="lng,lat,name,desc" point2="lng,lat,name,desc"]
        $pattern = '/\[route(?:\s+type=(\w+))?(?:\s+zoom=(\d+))?(?:\s+theme=(\w+))?(?:\s+traffic=(\d))?(?:\s+width=([^\s]+))?(?:\s+height=([^\s]+))?(.*?)\]/i';

        $content = preg_replace_callback($pattern, function($matches) use ($apiKey, $securityJsCode, $defaultRouteType, $defaultShowTraffic, $defaultTheme, $defaultWidth, $defaultHeight) {
            $routeType = !empty($matches[1]) ? $matches[1] : $defaultRouteType;
            $zoom = !empty($matches[2]) ? intval($matches[2]) : 12;
            $theme = !empty($matches[3]) ? $matches[3] : $defaultTheme;
            $showTraffic = !empty($matches[4]) ? boolval($matches[4]) : $defaultShowTraffic;
            $width = !empty($matches[5]) ? $matches[5] : $defaultWidth;
            $height = !empty($matches[6]) ? $matches[6] : $defaultHeight;
            $attributes = $matches[7];

            // 解析路径点数据
            $pointsData = [];
            preg_match_all('/point(\d+)=([\'"])([^\2]*?)\2/', $attributes, $pointMatches);

            foreach ($pointMatches[3] as $pointStr) {
                $parts = explode(',', $pointStr);
                if (count($parts) >= 2) {
                    $pointsData[] = [
                        'lng' => trim($parts[0]),
                        'lat' => trim($parts[1]),
                    ];
                }
            }

            if (count($pointsData) >= 2) {
                return AMapRoutePlugin_Plugin::renderRoute($routeType, $zoom, $apiKey, $pointsData, $showTraffic, $securityJsCode, $theme, $width, $height);
            }

            return $matches[0]; // 解析失败返回原内容
        }, $content);

        return $content;
    }

    /**
     * 在编辑器页面插入路径规划按钮和资源
     */
    public static function insertRouteButton()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $pluginOptions = $options->plugin('AMapRoutePlugin');
        // 获取插件URL路径
        $pluginUrl = Helper::options()->pluginUrl.'/AMapRoutePlugin/assets/editer.js';

        // 获取插件配置
        $defaultRouteType = isset($pluginOptions->routeType) ? $pluginOptions->routeType : 'driving';
        $defaultTheme = isset($pluginOptions->mapTheme) ? $pluginOptions->mapTheme : 'normal';
        $defaultZoom = isset($pluginOptions->defaultZoom) ? intval($pluginOptions->defaultZoom) : 12;
        $defaultShowTraffic = isset($pluginOptions->showTraffic) ? $pluginOptions->showTraffic : true;
        $defaultWidth = isset($pluginOptions->defaultWidth) ? $pluginOptions->defaultWidth : '100%';
        $defaultHeight = isset($pluginOptions->defaultHeight) ? $pluginOptions->defaultHeight : '400px';

        // 构建路径类型选项
        $routeTypeOptions = '';
        $routeTypes = [
            'driving' => '驾车',
            'walking' => '步行',
            'bicycling' => '骑行',
            'transit' => '公交'
        ];

        foreach ($routeTypes as $value => $label) {
            $selected = $defaultRouteType === $value ? ' selected' : '';
            $routeTypeOptions .= "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
        }

        // 构建主题选项
        $themeOptions = '';
        $themes = [
            'normal' => '标准',
            'dark' => '深色',
            'light' => '浅色',
            'fresh' => '清新',
            'whitesmoke' => '烟灰',
            'grey' => '灰色',
            'graffiti' => '涂鸦',
            'macaron' => '马卡龙',
            'blue' => '蓝色',
            'darkblue' => '深蓝',
            'wine' => '酒红'
        ];

        foreach ($themes as $value => $label) {
            $selected = $defaultTheme === $value ? ' selected' : '';
            $themeOptions .= "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
        }

        // 构建实时路况复选框状态
        $showTrafficChecked = $defaultShowTraffic ? ' checked' : '';

        echo <<<HTML
<div id="amapRouteModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border:1px solid #ccc; border-radius:8px; z-index:10000; box-shadow:0 4px 20px rgba(0,0,0,0.15); width: 550px; max-height: 80vh; overflow-y: auto;">
    <h3 style="margin:0 0 20px 0; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">插入路径规划</h3>
    <p>使用<a href="https://lbs.amap.com/tools/picker" target="_blank">高德地图经纬度选择工具</a>获取经纬度</p>
    <div class="amap-route-form">
        <div class="form-group">
            <label for="routeType" style="display:inline-block; width:100px;">路径类型：</label>
            <select id="routeType" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:150px;">{$routeTypeOptions}</select>
        </div>
        <div class="form-group">
            <label for="routeTheme" style="display:inline-block; width:100px;">地图主题：</label>
            <select id="routeTheme" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:150px;">{$themeOptions}</select>
        </div>
        <div class="form-group">
            <label for="routeZoom" style="display:inline-block; width:100px;">缩放级别：</label>
            <input type="number" id="routeZoom" value="{$defaultZoom}" min="1" max="18" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:80px;">
            <small style="color:#666; margin-left:8px;">1-18</small>
        </div>
        <div class="form-group">
            <label style="display:inline-block; width:100px;">实时路况：</label>
            <input type="checkbox" id="showTraffic"{$showTrafficChecked} style="margin-right:5px;">
            <label for="showTraffic" style="display:inline;">显示实时路况</label>
        </div>
        <div class="form-group">
            <label for="routeWidth" style="display:inline-block; width:100px;">地图宽度：</label>
            <input type="text" id="routeWidth" value="{$defaultWidth}" placeholder="100%" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:150px;">
            <small style="color:#666; margin-left:8px;">如: 100% 或 800px</small>
        </div>
        <div class="form-group">
            <label for="routeHeight" style="display:inline-block; width:100px;">地图高度：</label>
            <input type="text" id="routeHeight" value="{$defaultHeight}" placeholder="400px" style="padding:6px; border:1px solid #ddd; border-radius:4px; width:150px;">
            <small style="color:#666; margin-left:8px;">如: 400px</small>
        </div>

        <div id="routePointsContainer">
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
        </div>

        <button type="button" id="addRoutePointBtn" style="background:#17a2b8; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; margin:5px 0;">+ 添加途经点</button>
        <small style="color:#666; display:block; margin-bottom:10px;">路径规划需要至少2个点（起点和终点）</small>

        <div style="margin-top:20px; padding-top:15px; border-top:1px solid #eee; text-align:right;">
            <button id="routeConfirm" style="background:#28a745; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; margin-left:8px;">确认插入</button>
            <button id="routeCancel" style="background:#f5f5f5; border:1px solid #ddd; padding:8px 16px; border-radius:4px; cursor:pointer; margin-left:8px;">取消</button>
        </div>
    </div>
</div>
<div id="amapRouteModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;"></div>

<script src="{$pluginUrl}"></script>
HTML;
    }
}
?>