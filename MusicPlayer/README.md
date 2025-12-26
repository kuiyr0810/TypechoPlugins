# MusicPlayer 插件

## 简介

MusicPlayer 是一个为 Typecho 博客系统开发的插件，能够自动将音乐链接转换为可嵌入的播放器。支持网易云音乐和 QQ 音乐的歌曲、歌单和专辑链接的自动转换，让读者可以在博客页面直接播放音乐内容。

## 功能特点

- **自动识别**：自动识别文章内容中的音乐链接
- **多平台支持**：支持网易云音乐和 QQ 音乐
- **多种类型支持**：支持歌曲、歌单和专辑链接
- **无缝集成**：与 Typecho 编辑器无缝集成，无需额外操作
- **响应式设计**：播放器宽度自适应，支持移动设备
- **可配置**：支持设置是否自动播放
- **样式自定义**：支持通过 CSS 自定义播放器样式

## 支持的链接格式

### 网易云音乐

#### 歌曲链接
```
https://music.163.com/#/song?id=12345678
```

#### 歌单链接
```
https://music.163.com/#/playlist?id=12345678
```

#### 专辑链接
```
https://music.163.com/#/album?id=12345678
```

### QQ 音乐

#### 歌曲链接
```
https://i.y.qq.com/v8/playsong.html?songid=127570280&songtype=0
```

## 安装方法

1. 下载插件文件并解压
2. 将 `MusicPlayer` 文件夹上传到 Typecho 的 `usr/plugins/` 目录
3. 登录 Typecho 后台，进入"控制台" → "插件"
4. 找到 MusicPlayer 插件，点击"启用"
5. 在插件设置中配置自动播放选项（可选）

## 配置选项

插件提供以下配置选项：

- **自动播放设置**：
  - 不自动播放（默认）
  - 自动播放

## 使用方法

1. 在 Typecho 编辑器中直接粘贴音乐链接
2. 发布文章后，链接会自动转换为播放器
3. 读者可以直接在博客页面播放音乐

### 示例

#### 网易云音乐示例

在编辑器中输入：
```
https://music.163.com/#/song?id=1383292126
```

发布后会自动转换为：
```html
<div class="typecho-netease-player">
  <iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%" height="86" src="https://music.163.com/outchain/player?type=2&id=1383292126&auto=0&height=66"></iframe>
</div>
```

#### QQ 音乐示例

在编辑器中输入：
```
https://i.y.qq.com/v8/playsong.html?songid=127570280&songtype=0
```

发布后会自动转换为：
```html
<div class="typecho-qq-player">
  <iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%" height="65" src="https://i.y.qq.com/n2/m/outchain/player/index.html?songid=127570280&songtype=0"></iframe>
</div>
```

## 技术实现

### 工作原理

插件通过 Typecho 的内容过滤钩子（`contentEx` 和 `excerptEx`）在文章内容渲染时进行正则匹配，识别音乐链接并替换为相应的 iframe 嵌入代码。同时通过 `header` 钩子加载自定义 CSS 样式文件。

### 正则表达式

插件使用以下正则表达式匹配不同类型的链接：

```php
// 网易云音乐歌曲链接
$neteaseSongPattern = '/<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/song\?id=(\d+)["\'][^>]*>.*?<\/a>/i';

// 网易云音乐歌单链接
$neteasePlaylistPattern = '/<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/playlist\?id=(\d+)["\'][^>]*>.*?<\/a>/i';

// 网易云音乐专辑链接
$neteaseAlbumPattern = '/<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/album\?id=(\d+)["\'][^>]*>.*?<\/a>/i';

// QQ 音乐歌曲链接
$qqSongPattern = '/<a\s+[^>]*href=["\']https:\/\/i\.y\.qq\.com\/v8\/playsong\.html\?[\s\S]*?songid=(\d+)[\s\S]*?["\'][^>]*>.*?<\/a>/i';
```

### 播放器参数

插件根据不同类型生成不同的 iframe 参数：

#### 网易云音乐
- **歌曲**：`type=2`，高度 86px
- **歌单**：`type=0`，高度 480px
- **专辑**：`type=1`，高度 480px

#### QQ 音乐
- **歌曲**：高度 65px

### 样式自定义

插件会自动加载 `assets/style.css` 文件，您可以通过修改该文件来自定义播放器样式。CSS 文件会自动添加版本号，确保更新后浏览器能加载最新样式。

## 常见问题

### Q: 为什么链接没有转换为播放器？
A: 请确保：
1. 插件已正确启用
2. 链接格式正确且包含有效的 ID
3. Typecho 已将链接转换为 `<a>` 标签格式

### Q: 播放器显示跨域错误怎么办？
A: 插件已使用 HTTPS 协议加载播放器，如仍有问题，请确保您的网站也使用 HTTPS 协议。

### Q: 可以自定义播放器样式吗？
A: 可以通过修改 `assets/style.css` 文件来自定义播放器样式，或者通过 CSS 自定义 `.typecho-netease-player` 和 `.typecho-qq-player` 类的样式。

### Q: 为什么有些歌不能播放？
A: 如果歌曲无法生成分享外链，即便是转换了也不会播放。这是音乐平台自身的限制。

### Q: QQ 音乐链接支持哪些格式？
A: 目前支持 `https://i.y.qq.com/v8/playsong.html?songid=xxx&songtype=0` 格式的歌曲链接。

## 版权信息

- 作者：王叨叨
- 许可证：MIT License
- 项目地址：https://wangdaodao.com
