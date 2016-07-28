=== Sitewide Comment Control ===
Contributors: Ipstenu
Tags: comment, ban, moderate, spam, multisite, wpmu
Requires at least: 4.0
Tested up to: 4.6
Stable tag: 2.1
Donate link: https://store.halfelf.org/donate/

Blacklist, moderate, or spam a list of unregistered commenters across your entire Multisite Network.

== Description ==

When you run a network, blacklisting commenters is handled per-site. That's normally okay, but sometimes people decide to be trolls and spam your whole network. This plugin allows you to ban, spam or moderate an unregistered commenter network wide. It does not replace the per-site blacklists or moderation lists, but simply adds on to it.

When a user posts a comment and they're on the list, they are redirected to the post they just tried to comment on, but their comment has been shunted to the mysterious black hole along with your socks. If you pick 'blackhole', no one will ever see the comment. Pick 'spam' and they go to spam. Pick 'moderate' and the comment is forced into moderation.

A sample email of spammer@example.com is included in the plugin for you to play with.

* [Support](http://wordpress.org/tags/sitewide-comment-control?forum_id=10#postform)
* [Plugin Site](http://halfelf.org/plugins/sitewide-comment-control/)
* [Donate](https://store.halfelf.org/donate/)

= Credits =

Thanks to Jan for the idea of moderating and spamming. Thanks to Joey and Helen for reminding me about `get_permalink()` and it's silly inconsistencies.

= Issues =
* Setting a comment to 'moderate' doesn't always show the 'your comment is in moderation...' to the user, which can be confusing.

== Installation ==

No special instructions needed. This plugin is only network activatable.

== Screenshots ==

1. Network Admin Menu

== Frequently Asked Questions ==

= If I change the blacklist using this plugin, will it change the Comment Blacklist? =

No, this is completely separate.

= Does this list the rejected posters? =

If you set comments to be spammed or moderated, they will show up in the appropriate section of your comments page. If you set comments to be blackhole'd, no one will ever see them. Ever.

= Will this block user names? =

No, it only blocks by email address.

= Why don't you block IPs? =

I don't find it useful to do in WordPress. If you're blocking IPs, given how dynamic they are, it won't do you any good in the long run on the app level. This should be done at the server level.

= Will this block partial emails? =

Yes. If you put `example.com` in your list, it will block `anything@example.com`. 

**Be very careful when you do this!** If you put in just the letter `a` for example, you will block all email addresses with that letter. I strongly recommend you only use full domains ('example.com', not just 'example').

= Will this block wildcards? =

No. You cannot block `foo*@gmail.com` at this time. This is a highly requested feature, but it's complicated to get right without making your network unbearably slow. It doesn't scale well in my tests. Pull requests welcome.

= Will this block existing users? =

This plugin will not block any logged in user, *even if* their email is on the list. If you don't want them commenting, delete their accounts. It's assumed if you let someone on your site, you mean for them to have access.

= Does this work on MultiSite? =

Yes, it does.

= Does this work on Single Site installs? =

No, and what's the point? You already have a blacklist for that.

= Does this work on BuddyPress? =

I don't know. Let me know!

==Changelog==

= 2.1 = 
* 22 March 2016 by Ipstenu
* Cleaning Internationalization
* Security: Nonces, sanitization, validation

= 2.0 =
* 30 October 2013 by Ipstenu
* Works with 3.7
* Removing sort to stop sorting

== Upgrade Notice ==