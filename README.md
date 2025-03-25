# WizChat - WordPress AI智能客服插件

基于OpenAI的智能聊天客服控件，为您的WordPress网站提供智能客服功能。

## 插件信息

- **插件名称**：WizChat
- **描述**：AI智能客服，基于OpenAI的智能聊天客服控件
- **作者**：Lemon
- **官网**：lrai.studio
- **微信**：lemonrere

## 功能特点

- 在前端页面右下角显示聊天气泡，点击可展开聊天界面
- 基于OpenAI强大的AI模型提供智能对话能力
- 支持向量知识库，可针对网站内容进行专业回复
- 对话持久化，用户刷新或跳转页面不会丢失会话
- 现代美观的界面设计，基于TailwindCSS构建
- 灵活的设置选项，支持自定义API通信和模型选择

## 技术栈

- PHP (WordPress插件开发)
- JavaScript/jQuery (前端交互)
- TailwindCSS (UI界面设计)
- OpenAI API (AI对话能力)
- LocalStorage/Cookies (对话持久化)
- REST API (后端通信)

## 项目结构

```
wizchat/
├── README.md                     # 项目说明文档
├── progress.md                   # 开发进度文档
├── wizchat.php                   # 插件主文件
├── uninstall.php                 # 卸载处理文件
├── assets/                       # 前端资源文件
│   ├── css/                      # 样式文件
│   │   └── wizchat.css           # 主样式文件
│   ├── js/                       # JavaScript文件
│   │   ├── wizchat.js            # 主JS文件
│   │   └── wizchat-admin.js      # 管理界面JS文件
│   └── images/                   # 图片资源
├── includes/                     # PHP类和函数
│   ├── class-wizchat.php         # 主类文件
│   ├── class-wizchat-admin.php   # 管理界面类
│   ├── class-wizchat-api.php     # API通信类
│   └── class-wizchat-public.php  # 前端显示类
├── admin/                        # 管理界面
│   ├── partials/                 # 管理界面模板
│   └── class-wizchat-settings.php # 设置页面处理
├── public/                       # 前端显示
│   └── partials/                 # 前端模板
└── vendor/                       # 第三方依赖
```

## 安装说明

1. 下载插件压缩包
2. 在WordPress管理界面中，导航至"插件 > 安装插件"
3. 点击"上传插件"按钮，选择下载的压缩包
4. 安装完成后，激活插件
5. 在"设置 > WizChat设置"中配置您的API密钥和其他选项

## 配置选项

- **API密钥**：您的OpenAI API密钥
- **API基础URL**：API通信的基础URL（默认为OpenAI官方API）
- **AI模型**：选择使用的AI模型（如gpt-4o、gpt-4o-mini等）
- **聊天气泡位置**：可自定义气泡在页面上的位置
- **对话持久化时间**：设置对话历史保存的时间长度
- **界面自定义**：调整聊天界面的外观和颜色

## 开发进度

请查看[progress.md](progress.md)文件获取详细的开发进度信息。

## 许可证

GPL v2或更高版本
