**The problem**: You want to have a discussion on a GitHub README, a forum signature, or an eBay listing. None of those let you post comments. They only render images.

What if the image *was* the comment thread?

Someone scans the QR code in the corner, gets taken to a page, writes something. Next time that image loads, their comment is rendered onto it.

**image-chat** is that.

[**Try it out!**](https://9ol.es/image-chat)

You embed this:

    <img src="https://your.site/image/123.png">

And the image is a live chat thread. Refresh the page and new comments show up in the picture.

For instance:

<img src=https://9ol.es/image-chat/image/1.webp />

## How it works

1. Create a chat on the web UI
2. Copy the embed `<img>` tag
3. Paste it anywhere images work — GitHub, forums, emails, whatever
4. People scan the QR code, leave a comment, reload
5. The server renders all comments onto the image using pandoc → wkhtmltopdf → rasterize → composite

The image is just a PNG (or WebP, or AVIF) with the thread styled and a QR code in the corner pointing back to the comment page.

## Quick start

```bash
# Start MySQL
./setup.sh

# Point Apache at this directory (or php -S)
php -S localhost:8080

# Visit localhost:8080, create a chat, grab the embed code
```

You'll need PHP, MySQL, pandoc, weasyprint, and ImageMagick or poppler-utils installed for the render pipeline.

## What's here

- `index.php` — create and list chats
- `chat.php` — view thread and post comments
- `image.php` — the image renderer (pipeline is stubbed, needs the toolchain wired in)
- `templates/chat-default.html` — a stylesheet for how the chat looks in the image
- `schema.sql` — MySQL tables
- `docker-compose.yml` + `setup.sh` — spins up MySQL with random credentials

## FAQ

- Q: Is this anonymous?
- A: For now. Pick a username. No accounts. SSO when I feel like it.

- Q: Can I style my chat?
- A: Swap the template in `templates/`. Future maybe: per-chat templates.

- Q: Why 250 characters?
- A: It's an image. There isn't infinite room. Write a blog post if you need more.
