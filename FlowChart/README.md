# FlowChart 插件使用说明

## 插件简介

FlowChart 是一个 Typecho 博客系统的插件，用于在文章中渲染流程图。它使用 flowchart.js 库将文本格式的流程图描述转换为可视化的 SVG 流程图。

## 功能特点

- 支持在文章中嵌入流程图
- 自动识别并渲染特定代码块中的流程图语法
- 支持中英文界面切换
- 支持亮色/暗色主题适配
- 提供自定义CSS样式支持
- 自动处理依赖库（jQuery、Raphael.js、flowchart.js）

## 安装与配置

### 安装步骤

1. 将 FlowChart 插件文件夹上传到 Typecho 的 `usr/plugins/` 目录下
2. 登录 Typecho 后台，进入"控制台" → "插件"
3. 找到 FlowChart 插件，点击"启用"

### 配置选项

启用插件后，可在插件设置页面配置以下选项：

#### 1. 语言设置
- **英文**：判断语句显示为 "Yes" 和 "No"
- **中文**：判断语句显示为 "是" 和 "否"（默认选项）

#### 2. 引入jQuery
- **是**：自动引入 jQuery 库（默认选项）
- **否**：不引入 jQuery 库（如果你的网站已经加载了 jQuery）

#### 3. 线条宽度
- 设置流程图线条的宽度，单位为像素（默认值：2）

#### 4. 线条长度
- 设置流程图线条的长度，单位为像素（默认值：50）

#### 5. 文本边距
- 设置流程图文本的边距，单位为像素（默认值：12）

#### 6. 字体大小
- 设置流程图文本的字体大小，单位为像素（默认值：14）

## 使用方法

### 基本语法

在文章编辑器中，使用以下格式嵌入流程图代码：

```
```flow
// 流程图代码
```
```

或者

```
```language-flow
// 流程图代码
```
```

### 流程图语法示例

#### 简单流程图

```
```flow
st=>start: 开始
op=>operation: 处理
cond=>condition: 是否继续?
e=>end: 结束

st->op->cond
cond(yes)->op
cond(no)->e
```
```

#### 复杂流程图

```
```flow
st=>start: 开始|past:>http://www.google.com
e=>end: 结束:>http://www.google.com
op1=>operation: 操作1|past
op2=>operation: 操作2|current
sub1=>subroutine: 子程序1|invalid
cond=>condition: 是否继续?|approved:>http://www.google.com
c2=>condition: 二级判断|rejected
io=>inputoutput: 输入输出|future

st->op1(right)->sub1->c2
c2(true)->io->e
c2(false)->op2->cond
cond(true)->sub1(right)->op1
cond(false)->e
```
```

### 语法元素说明

- `start`: 开始节点
- `end`: 结束节点
- `operation`: 操作/处理节点
- `condition`: 条件判断节点
- `subroutine`: 子程序节点
- `inputoutput`: 输入/输出节点

### 连接语法

- `->`: 普通连接
- `(right)`: 从右侧连接
- `(left)`: 从左侧连接
- `(top)`: 从顶部连接
- `(bottom)`: 从底部连接

### 条件分支

- `cond(yes)`: 条件为真时的分支
- `cond(no)`: 条件为假时的分支

## 注意事项

1. **依赖库**：插件依赖 jQuery、Raphael.js 和 flowchart.js，会自动从 CDN 加载
2. **代码块识别**：插件只会识别 `class="language-flow"` 或 `class="lang-flow"` 的代码块
3. **CSS样式**：插件会自动引入自定义 CSS 样式文件，提供基础样式和响应式设计，确保流程图在不同设备上都能正常显示
4. **页面加载**：插件仅在包含流程图代码块的内容页面加载资源，提高页面加载速度
5. **兼容性**：插件兼容大多数现代浏览器

## 常见问题

### Q: 流程图不显示怎么办？
A: 请检查以下几点：
1. 确保插件已正确安装并启用
2. 确保文章中使用了正确的代码块格式（`language-flow` 或 `lang-flow`）
3. 检查浏览器控制台是否有 JavaScript 错误
4. 确保没有其他插件或主题脚本冲突

### Q: 如何自定义流程图样式？
A: 插件会自动引入 `style.css` 文件，你可以通过以下方式自定义样式：
1. 直接修改插件目录下的 `style.css` 文件
2. 在主题的 CSS 文件中添加更高优先级的样式规则
3. 主要的 CSS 类名为 `.flow-chart`，你可以通过它来自定义样式

### Q: 流程图在移动设备上显示不正常？
A: 插件已包含响应式设计，如果仍有问题，可以尝试：
1. 检查主题是否支持响应式设计
2. 在自定义 CSS 中调整 `.flow-chart` 的样式
3. 确保流程图内容不要过于复杂，以免在小屏幕上显示困难

### Q: 可以在暗色主题中使用吗？
A: 是的，插件的 CSS 文件已包含暗色主题适配，会根据系统的暗色模式设置自动调整样式。你也可以通过自定义 CSS 进一步调整暗色主题下的样式。

### Q: 网站已经加载了 jQuery，是否还需要启用"引入jQuery"选项？
A: 如果你的网站已经加载了 jQuery，可以关闭此选项以避免重复加载。

## 技术支持

如遇到问题或需要帮助，可以访问作者网站：https://wangdaodao.com

## 许可证

请查看插件目录下的 LICENSE 文件了解许可证信息。