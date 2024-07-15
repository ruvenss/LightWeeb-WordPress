### LightWeb-WordPress Plugin ###
<img src="https://camo.githubusercontent.com/9c9a8f34916a7fd1e31673e8901bf8fd9377033609d447a29ffabb082eef36fd/68747470733a2f2f696d616765732e6e697a752e696f2f6c696768747765622f6170706c652d746f7563682d69636f6e2d707265636f6d706f7365642e706e67" width="50"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/Wordpress-Logo.svg/1024px-Wordpress-Logo.svg.png" width="50">

This *WordPRess* PlugIn catches the event of saving a post, and a categorie and sending the data to your LightWeb Stage Server in real time.

The purpose of this PlugIn is to isolate WordPress as an editor blog only and use the LightWeb as the render of the real website. 

But Why? ... you may ask

- WordPress Attacks are becoming more and more often, the plugins less and less sustainables, and more and more insecure. making WordPress a nightmare after 3 months of being installed.
- With LightWeb and WordPress you keep the best of both worlds, in one side the easynest of editing, and updating articles and pages with non tech people and on the other hand a pure html,css, and JS website without CMS platform.

# Installation

- Download the source from GitHub in *zip*
- Upload the same zip as a new plugin to your WordPress edit website.
  - Recommendation: use a domain such as edit.yourwebsite.com for wordpress, and VPN and firewall the hell out of it
  - Recommendation: use another container/instance/server for your stage.yourwebsite.com, and yourwebsite.com with the LightWeb framework
- Generate a user app token or password and set it in the LightWeb Stage Server in the file `api/v1/my_config.php`
- Copy and paste the WordPress secret token to the same file

Your my_config file should look like this:

```php
<?php
/**
 * Here you will define your own global variables
 * Remember LIGHTWEB Globals starts by LIGHTWEB_ 
 * you can allways use your own definitions
 */
define("LIGHTWEB_SENDGRID", false);
define("LIGHTWEB_SENDGRID_API_KEY", null);
define("LIGHTWEB_ONESIGNAL_ID", null);
define("LIGHTWEB_ONESIGNAL_API_KEY", null);
define("LIGHTWEB_NIZUTOKEN", null);
define("LIGHTWEB_NIZUCLOUDAPI", null);
define("LIGHTWEB_STRIPE_SECRET", null);
define("WP_IDX", "wp_");
define("wp_secret", "KCojdZ<vWna16RZm:`k )eH,l,%g2d&d%Vb;lg8ZOytzl+Q)O]MwXi9PHgl;d,OD");
define("wp_token", "LjTb y7J2 mqEd s3km wN1m Ygj7");
define("wp_taxonomy", true);
```

After that Go to your WordPress Tools->LightWeb and indicate your stage server settings, then click save and thats it.
