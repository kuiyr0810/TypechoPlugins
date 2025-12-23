# 抖音视频播放插件

这是一个Typecho插件，用于将抖音视频链接自动转换为可嵌入的播放器。

## 功能特点

- 自动识别Typecho转换后的抖音视频链接
- 将链接替换为抖音官方播放器
- 居中显示视频播放器
- 仅在渲染时替换，不影响原始内容

## 安装方法

1. 将整个`DouYinPlayer`文件夹上传到Typecho的`usr/plugins/`目录下
2. 登录Typecho后台，进入"控制台" -> "插件"
3. 找到"抖音视频播放插件"，点击"启用"

## 使用方法

1. 在文章中直接粘贴抖音视频链接，例如：
   ```
   https://www.douyin.com/video/7554555892894436666

   [王叨叨视频](https://www.douyin.com/video/7554555892894436666)

   [王叨叨视频][1]

   [1]: https://www.douyin.com/video/7554555892894436666
   ```

2. Typecho会自动将链接转换为`<a>`标签，插件会检测并替换为播放器

3. 播放器将居中显示

### 示例

原始链接：
```
https://www.douyin.com/video/7554555892894436666
```

Typecho转换后：
```html
<a href="https://www.douyin.com/video/7554555892894436666">https://www.douyin.com/video/7554555892894436666</a>
```

插件处理后：
```html
<div class="typecho-douyin-video">
  <div class="typecho-douyin-video-wrapper">
    <iframe width="100%" height="100%" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true" src="https://open.douyin.com/player/video?vid=7554555892894436666&autoplay=0" referrerpolicy="unsafe-url"></iframe>
  </div>
</div>
```

## 注意事项

- 插件仅处理Typecho自动转换的`<a>`标签格式的抖音视频链接
- 插件仅在渲染时替换链接，不会修改原始文章内容
- 禁用插件后，视频将恢复为原始链接形式
- 播放器使用抖音官方嵌入代码，确保最佳兼容性

## 反馈与支持

如果您遇到任何问题或有改进建议，请通过以下方式联系：

- [https://wangdaodao.com/](https://wangdaodao.com/)
- [hi@wangdaodao.com](hi@wangdaodao.com)