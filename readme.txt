=== CW Image Optimizer Advanced ===
Contributors: usability.idealist
Donate link: https://www.paypal.me/FabianWolf
Tags: images, image, attachments, attachment, optimization, conversion
Requires at least: 2.9
Tested up to: 4.8
Stable tag: 1.2

Reduce image file sizes and improve performance using Linux image optimization programs.

== Description ==

CW Image Optimizer Advanced is a WordPress plugin that will automatically, both losslessly or lossy optimize your images as you upload them to your blog. It can also optimize the images that you have already uploaded in the past.

CW Image Optimizer Advanced uses both lossless optimization and lossy techniques, the latter one as a fallback (ImageMagick) if none of the littleutils binaries is available.
With the lossless image optimizers, your image quality will be exactly the same before and after the optimization. The only thing that will change is your file size.

The CW Image Optimizer Advanced plugin is based on the WP Smush.it plugin. Unlike the WP Smush.it plugin, your files won’t be uploaded to a third party when using CW Image Optimizer. Your files are optimized using the Linux [littleutils](http://sourceforge.net/projects/littleutils/) image optimization tools (available for free). You don’t need to worry about the Smush.it privacy policy or terms of service because your images never leave your server.

**Why use CW Image Optimizer Advanced?**

1. **Your pages will load faster.** Smaller image sizes means faster page loads. This will make your visitors happy, and can increase ad revenue.
1. **Faster backups.** Smaller image sizes also means faster backups.
1. **Less bandwidth usage.** Optimizing your images can save you hundreds of KB per image, which means significantly less bandwidth usage.
1. **Super fast.** Because it runs on your own server, you don’t have to wait for a third party service to receive, process, and return your images. You can optimize hundreds of images in just a few minutes.

== Installation ==

1. Install littleutils or ImageMagick on your Linux server (step-by-step instructions are below).
1. Upload the 'cw-io-advanced' plugin to your '/wp-content/plugins/' directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Done!

= Installing littleutils: GNU/Debian =

1. Download the latest packages of [littleutils](http://sourceforge.net/projects/littleutils/) - either 64-bit (littleutils-debian-amd64) or 32-bit (littleutils-debian-i386)
1. If you do not know which hardware platform you got, open a terminal window (Applications -> Accessories -> Terminal), and enter *dpkg --print-architecture* - returns *amd64* for 64-bit, and *i686* for 32-bit (could also be: i386, i586, and so on; [also see Wikipedia](https://en.wikipedia.org/wiki/Intel_80386))
1. Open a terminal window (Applications -> Accessories -> Terminal)
1. Create a subdirectory (eg. *mkdir littleutils-debian*) and move the downloads into it: *mv ~/Downloads/littleutils*.deb ~/Downloads/littleutils/*
1. Switch to super user (aka root) with *sudo su*
1. Install dependencies: *apt-get install gifsicle pngcrush lzip libpng12-0 libpng12-dev libjpeg-progs p7zip-full*
1. Switch to the subdirectory you created before and install littleutils: *dpkg -i *.deb*

= Installing littleutils: Ubuntu 14.04 LTS (64-bit) =

These instructions were tested with littleutils 1.0.36 and Linux Mint 17.3 aka Ubuntu 14.04 LTS (64-bit, desktop edition).

1. Download the latest version of [littleutils](http://sourceforge.net/projects/littleutils/) to your Download directory
1. Open a terminal window (Applications -> Accessories -> Terminal)
1. Install dependencies: *sudo apt-get install gifsicle pngcrush lzip libpng12-0 libpng12-dev libjpeg-progs p7zip-full*
1. Uncompress littleutils: *sudo tar xvfj littleutils-1.0.36.tar.bz2 -C /usr/local/src && cd /usr/local/src/littleutils-1.0.36*
1. Switch to the super user (aka root) account: *sudo su*
1. Configure and compile littleutils: *./configure --prefix=/usr && make*
1. Install littleutils: *make install && make install-extra*

= Installing littleutils: CentOS 6.0 (32-bit) =

These instructions were tested with littleutils 1.0.27 and CentOS 6.0 (32-bit, "Basic server" configuration).

1. Log in as the root user.
1. Enable the rpmforge repository: *cd /usr/local/src/ && wget http://packages.sw.be/rpmforge-release/rpmforge-release-0.5.2-2.el6.rf.i686.rpm && rpm -i rpmforge-release-0.5.2-2.el6.rf.i686.rpm*
1. Install dependencies: *yum install gcc libpng libpng-devel gifsicle pngcrush p7zip lzip*
1. Download the latest version of [littleutils](http://sourceforge.net/projects/littleutils/): *cd /usr/local/src; wget http://downloads.sourceforge.net/project/littleutils/littleutils/1.0.27/littleutils-1.0.27.tar.bz2?r=http%3A%2F%2Fsourceforge.net%2Fprojects%2Flittleutils%2F*
1. Uncompress littleutils: *tar jxvf littleutils-1.0.27.tar.bz2 && cd littleutils-1.0.27*
1. Configure and install littleutils: *./configure --prefix=/usr && make && make install && make install-extra*

= Troubleshooting =

**littleutils or ImageMagick is installed, but the plugin says it isn't.** If you are confident that it is installed properly, then go to the plugin configuration page and disable the installation check. 

It is also possible that your binaries aren't accessible to your web server user. You can link these binaries using the following commands:
    ln -s /usr/local/bin/opt-jpg /usr/bin/opt-jpg
    ln -s /usr/local/bin/opt-png /usr/bin/opt-png
    ln -s /usr/local/bin/opt-gif /usr/bin/opt-gif
    ln -s /usr/local/bin/tempname /usr/bin/tempname
    ln -s /usr/local/bin/imagsize /usr/bin/imagsize
    ln -s /usr/local/bin/gifsicle /usr/bin/gifsicle
    ln -s /usr/local/bin/pngcrush /usr/bin/pngcrush
    ln -s /usr/local/bin/pngrecolor /usr/bin/pngrecolor
    ln -s /usr/local/bin/pngstrip /usr/bin/pngstrip
	ln -s /usr/local/bin/convert /usr/bin/convert

The third option is to manually add or change the paths of each tool in the respective field in the plugin configuration page.

== Frequently Asked Questions ==

= Can I use CW Image Optimizer Advanced with a Windows server? =

Yes and no. The littleutils are only available for Linux and other Unix derivates.
ImageMagick is [available for Windows](http://www.imagemagick.org/script/binary-releases.php#windows), too.

= Do I have to have littleutils? =

Preferably yes.

CW Image Optimizer Advanced expects *opt-jpg*, *opt-png*, and *opt-gif* to be in the PATH. If that fails, it will try to use *convert* (ImageMagick) as a fallback.

== Screenshots ==

1. Additional optimize column added to media listing. You can see your savings, or manually optimize individual images.
2. Bulk optimization page. You can optimize all your images at once. This is very useful for existing blogs that have lots of images.

== Changelog ==

= 1.2 =
* Forked from CW Image Optimizer (2016-12-19)
* Added support for ImageMagick (and thus cross-platform compatiblity)
* Added tool usage via path and as WordPress settings (littleutils and ImageMagick binaries)
* Added extended settings and handling via Settings API
* Added dismiss functionality to the admin notice (missing littleutils / ImageMagick)
* Started moving the functions into singular classes / OOP
* Removed OS check (because ImageMagick is available cross-platform)
* Added plugin activation hook
* Added simple internal settings handling API
* Added file size column to media library overview
* Updated and enhanced the readme.txt
* Updated the screenshots


= 1.1.10 =
* Fix exec check on some systems.

= 1.1.9 =
* Updated littleutils instructions.

= 1.1.8 =
* Fixed undefined variable errors caused by absolute path code.
* Fixed undefined index errors that were happening for some file types.

= 1.1.7 =
* Made it easy to skip the check for littleutils. You can now do this from a settings page.
* Added a check for exec(). Some PHP installations have this function disabled, which will prevent this plugin from working.

= 1.1.6 =
* Made it possible to skip the check for littleutils binaries. This is useful on systems where the "which" command doesn't work as expected.

= 1.1.5 =
* Fixed PHP warnings in bulk optimization code when an image didn't have any additional sizes.

= 1.1.4 =
* Removed extra call to mime_content_type()

= 1.1.3 =
* Added an additional method of determining MIME type for those that are missing mime_content_type()

= 1.1.2 =
* Plugin works on Macs, too.
* Added screenshots.
* Added donate link.
* Fixed link to plugin homepage.

= 1.1.1 =
* Fixed versioning error.

= 1.1.0 =
* Added warnings when image optimization binaries are missing.
* Don't display optimization link if unsupported MIME type.

= 1.0.2 =
* Added a warning when the plugin is activated on a non-Linux server.

= 1.0.1 =
* Updated readme.txt to conform to WordPress standards.

= 1.0.0 =
* First edition

== Contact and Credits ==

Written by [Jacob Allred](http://www.jacoballred.com/) at [Corban Works, LLC](http://www.corbanworks.com/). Based on WP Smush.it.
