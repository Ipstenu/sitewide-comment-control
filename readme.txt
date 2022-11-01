=== Sitewide Comment Control ===
Contributors: Ipstenu
Tags: comment, ban, moderate, spam, multisite, network
Requires at least: 5.0
Tested up to: 6.1
Stable tag: 3.1.1
PHP Version: 7.2
Donate link: https://ko-fi.com/A236CEN/

Trash, moderate, or spam a list of unregistered commenters across your entire Multisite Network.

== Description ==

When you run a network, managing commenters is handled per-site. That's normally okay, but sometimes people decide to be trolls and spam your whole network. This plugin allows you to ban, spam or moderate an unregistered commenter network wide. It does not replace the per-site block, allow, or moderation lists, but acts as addition.

Sitewide Comment Control checks the author name, email address and IP address of a commenter. If they're found to be on the list, all comments will be shunted appropriately as determined by the Network Admin:

* Trash - all flagged comments do directly to trash
* Moderated - all flagged comments require human moderation
* Spam - all flagged comments go to spam

=== Credits ===

Thanks to Jan D. for the idea of moderating and spamming. Thanks to Joey and Helen for reminding me about `get_permalink()` and it's silly inconsistencies.

=== Privacy Notes ===

This plugin does not track any data other than what is submitted by commenters. No additional data is recorded. No data is sent to remote services by this plugin.

== Installation ==

No special instructions needed.

This plugin is only network activate-able.

== Screenshots ==

1. Network Admin Menu
2. Example of a spammed comment

== Frequently Asked Questions ==

= If I change the blocked list using this plugin, will it change the comment block lists per site? =

No. The sitewide lists do not edit the per-site lists.

= What happens with people blocked per-site? =

They remain blocked for that site, but <em>only</em> that site.

= Who takes precedence? Sitewide lists or per-site lists? =

This can be a little bit of a shell game. The answer is 'both' and 'neither'.

The act of ''blocking'' a user takes precedence. This means if a site on the network marks a specific user to be blocked, even if the network option is set to spam, the comments will be blocked on that site. On the other hand, if the network blocks someone, that cannot be overruled by any site on the network.

= Can I set specific emails or users to never be blocked? =

Not at this time.

= Does this list the rejected posters? =

Is so far as WordPress itself keeps rejected comments, yes.  If you set comments to be spammed or moderated, they will show up in the appropriate section of your comments page. If you set comments to be trashed they get added and go right to trash.

= What aspects of the commenter does this check? =

Usernames, IP addresses, and email addresses.

= Can this block partial emails? =

Yes. If you put `example.com` in your list, it will block `anything@example.com`. However it will also block `someoneelse@thisexample.com` so use the `@` when specificity is needed.

**Be very careful when you do this!** If you put in just the letter `a` for example, you will block all email addresses with that letter. I strongly recommend you only use full domains ('example.com', not just 'example').

= Can this block wildcards? =

Yes, but it's optional because it's an experimental feature.

The logic is a little chancy and has a higher risk of catching innocents. However if you add `spammer@example.com` and turn on Wildcard checking, then `spammer+avoid@example.com` will be caught, but `spammer+another@gmail.com` **will not** be caught. The downside to this is that `spammerama@example.com` will also be caught.

Use at your own risk.

= Will this block existing users? =

This plugin will not block any logged in user, *even if* their email is on the list. If you don't want them commenting, delete their accounts. It's assumed if you let someone on your site, you mean for them to have access.

= I have an existing user I cannot delete, but they're not welcome anymore. What can I do? =

That's a tough one, I hear you. I recommend you reset their password and change their email to something invalid. Then you can add their old email to your block lists as needed. Sadly there is no plugin I know of that handles this for Multisite.

= Does this work on MultiSite? =

Yes, it does.

= Does this work on Single Site installs? =

No. But then again, you don't need this on single installs.

= Where can I help develop this? =

[Github](http://github.com/ipstenu/sitewide-comment-control) - pull requests are welcome.

== Changelog ==

= 3.1.1 =
* 23 July 2020 by Ipstenu
* Changed function call for 5.5 compat, per https://make.wordpress.org/core/2020/07/23/codebase-language-improvements-in-5-5/

= 3.1 =
* 24 June 2020 by Ipstenu
* Updated: General: Remove “whitelist” and “blacklist” in favour of more clear and inclusive language.

= 3.0 =
* 27 December 2018 by Ipstenu
* Updated: Internationalization
* Updated: Move to OOP
* Updated: Faster processing
* Updated: Split apart the lists so you can ban and spam and moderate.
* New: Wildcards now supported (with caveats)

== Upgrade Notice ==
