<?php
/**
 * Bilibili播放器插件
 *
 * @package BilibiliPlayer
 * @author  王叨叨
 * @version 1.0.0
 * @link    https://wangdaodao.com
 * @description 使用Bilibili官方播放器，支持自定义配置
 */

!defined('__TYPECHO_ROOT_DIR__') && exit();

class BilibiliPlayer_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('BilibiliPlayer_Plugin', 'replacePlayer');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('BilibiliPlayer_Plugin', 'replacePlayer');
        return _t('插件已激活，将在内容渲染时使用Bilibili官方播放器');
    }

    public static function deactivate()
    {
        return _t('插件已禁用，Bilibili播放器将恢复默认状态');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $width = new Typecho_Widget_Helper_Form_Element_Text(
            'width',
            null,
            '100%',
            _t('视频宽度'),
            _t('设置视频播放器的宽度，例如：100%, 800px')
        );
        $form->addInput($width);

        $height = new Typecho_Widget_Helper_Form_Element_Text(
            'height',
            null,
            '500px',
            _t('视频高度'),
            _t('设置视频播放器的高度，例如：500px, 400px')
        );
        $form->addInput($height);

        $autoplay = new Typecho_Widget_Helper_Form_Element_Radio(
            'autoplay',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('是否自动播放'),
            _t('设置视频是否自动播放（注意：大多数浏览器会阻止自动播放）')
        );
        $form->addInput($autoplay);

        $danmaku = new Typecho_Widget_Helper_Form_Element_Radio(
            'danmaku',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '1',
            _t('默认弹幕开关'),
            _t('设置弹幕是否默认开启')
        );
        $form->addInput($danmaku);

        $muted = new Typecho_Widget_Helper_Form_Element_Radio(
            'muted',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('是否默认静音'),
            _t('设置视频是否默认静音')
        );
        $form->addInput($muted);

        $poster = new Typecho_Widget_Helper_Form_Element_Radio(
            'poster',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '1',
            _t('是否展示封面'),
            _t('设置是否在播放前展示视频封面')
        );
        $form->addInput($poster);

        $hasMuteButton = new Typecho_Widget_Helper_Form_Element_Radio(
            'hasMuteButton',
            array('0' => _t('不显示'), '1' => _t('显示')),
            '0',
            _t('一键静音按钮是否显示'),
            _t('设置是否显示一键静音按钮')
        );
        $form->addInput($hasMuteButton);

        $hideCoverInfo = new Typecho_Widget_Helper_Form_Element_Radio(
            'hideCoverInfo',
            array('0' => _t('显示'), '1' => _t('隐藏')),
            '0',
            _t('视频封面下方信息显示'),
            _t('设置是否隐藏视频封面下方的播放量、弹幕量等信息')
        );
        $form->addInput($hideCoverInfo);

        $hideDanmakuButton = new Typecho_Widget_Helper_Form_Element_Radio(
            'hideDanmakuButton',
            array('0' => _t('不隐藏'), '1' => _t('隐藏')),
            '0',
            _t('是否隐藏弹幕按钮'),
            _t('设置是否隐藏弹幕按钮')
        );
        $form->addInput($hideDanmakuButton);

        $noFullScreenButton = new Typecho_Widget_Helper_Form_Element_Radio(
            'noFullScreenButton',
            array('0' => _t('显示'), '1' => _t('隐藏')),
            '0',
            _t('是否隐藏全屏按钮'),
            _t('设置是否隐藏全屏按钮')
        );
        $form->addInput($noFullScreenButton);

        $fjw = new Typecho_Widget_Helper_Form_Element_Radio(
            'fjw',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '1',
            _t('是否开始记忆播放'),
            _t('设置是否开启记忆播放功能')
        );
        $form->addInput($fjw);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function replacePlayer($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;

        $options = Helper::options();
        $config = $options->plugin('BilibiliPlayer');

        if (!$config) {
            return $content;
        }

        $width = $config->width ?: '100%';
        $height = $config->height ?: '500px';
        $autoplay = $config->autoplay ?: '0';
        $danmaku = $config->danmaku ?: '1';
        $muted = $config->muted ?: '0';
        $poster = $config->poster ?: '1';
        $hasMuteButton = $config->hasMuteButton ?: '0';
        $hideCoverInfo = $config->hideCoverInfo ?: '0';
        $hideDanmakuButton = $config->hideDanmakuButton ?: '0';
        $noFullScreenButton = $config->noFullScreenButton ?: '0';
        $fjw = $config->fjw ?: '1';

        $pattern = '/<iframe[^>]*src\s*=\s*["\'](?:https?:)?\/\/player\.bilibili\.com\/player\.html([^"\']*)["\'][^>]*>.*?<\/iframe>/is';

        $content = preg_replace_callback($pattern, function($matches) use ($width, $height, $autoplay, $danmaku, $muted, $poster, $hasMuteButton, $hideCoverInfo, $hideDanmakuButton, $noFullScreenButton, $fjw) {
            $originalParams = $matches[1];
            $originalIframe = $matches[0];

            $customParams = array();
            if ($autoplay) {
                $customParams[] = 'autoplay=' . $autoplay;
            } else {
                $customParams[] = 'autoplay=0';
            }
            $customParams[] = 'danmaku=' . $danmaku;
            if ($muted) $customParams[] = 'muted=' . $muted;
            if ($poster) $customParams[] = 'poster=' . $poster;
            if ($hasMuteButton) $customParams[] = 'hasMuteButton=' . $hasMuteButton;
            if ($hideCoverInfo) $customParams[] = 'hideCoverInfo=' . $hideCoverInfo;
            if ($hideDanmakuButton) $customParams[] = 'hideDanmakuButton=' . $hideDanmakuButton;
            if ($noFullScreenButton) $customParams[] = 'noFullScreenButton=' . $noFullScreenButton;
            if ($fjw) $customParams[] = 'fjw=' . $fjw;

            $paramString = '';
            if (!empty($customParams)) {
                $hasQuery = strpos($originalParams, '?') !== false;
                $paramString = ($hasQuery ? '&' : '?') . implode('&', $customParams);
            }

            $newSrc = '//player.bilibili.com/player.html' . $originalParams . $paramString;

            $newIframe = preg_replace('/src\s*=\s*["\'](?:https?:)?\/\/player\.bilibili\.com\/player\.html([^"\']*)["\']/', 'src="' . $newSrc . '"', $originalIframe);

            if (preg_match('/width\s*=\s*["\'][^"\']*["\']/', $newIframe)) {
                $newIframe = preg_replace('/width\s*=\s*["\'][^"\']*["\']/', 'width="' . $width . '"', $newIframe);
            } else {
                $newIframe = preg_replace('/<iframe/', '<iframe width="' . $width . '"', $newIframe);
            }

            if (preg_match('/height\s*=\s*["\'][^"\']*["\']/', $newIframe)) {
                $newIframe = preg_replace('/height\s*=\s*["\'][^"\']*["\']/', 'height="' . $height . '"', $newIframe);
            } else {
                $newIframe = preg_replace('/<iframe/', '<iframe height="' . $height . '"', $newIframe);
            }

            if (!preg_match('/allowfullscreen/i', $newIframe)) {
                $newIframe = preg_replace('/<iframe/', '<iframe allowfullscreen="true"', $newIframe);
            }

            return $newIframe;
        }, $content);

        return $content;
    }
}
