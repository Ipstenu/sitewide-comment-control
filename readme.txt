=== Sitewide Comment Control ===
Contributors: Ipstenu
Tags: comment, ban, moderate, spam, multisite, wpmu
Requires at least: 3.3
Tested up to: 3.7
Stable tag: 1.5
Donate link: https://www.wepay.com/donations/halfelf-wp

Manage the ability of unregistered users to comment sitewide, across the entire network.

== Description ==

When you run a network, blacklisting commenters is handled per-site. That's normally okay, but sometimes people decide to be trolls and spam your whole network. This plugin allows you to ban, spam or moderate an unregistered commenter network wide. It does not replace the per-site blacklists or moderation lists, but simply adds on to it.

When a user posts a comment and they're on the list, they are redirected to the post they just tried to comment on, but their comment has been shunted to the mysterious black hole along with your socks. If you pick 'blackhole', no one will ever see the comment. Pick 'spam' and they go to spam. Pick 'moderate' and the post is forced into moderation.

A sample email of spammer@example.com is included in the plugin for you to play with.

* [Support](http://wordpress.org/tags/sitewide-comment-control?forum_id=10#postform)
* [Plugin Site](http://halfelf.org/plugins/sitewide-comment-control/)
* [Donate](https://www.wepay.com/donations/halfelf-wp)

==Changelog==

= 1.5 =
* 24 April, 2012 by Ipstenu
* Uninstall was broken, and I am so, so, so, sorry.

= 1.3 = 
* 17 April, 2012 by Ipstenu
* Readme cleanup, fixing URLs etc.

= 1.2 =
* 29 March, 2012 by Ipstenu
* Formatting change to the help screen. It was showing up on other pages that didn't specify their own b/c I can't spell.

= 1.1 =
* 23 March, 2012 by Ipstenu
* Minor issue with URLs. They need a closing `</a>` you know.

=  1.0 =
* 21 March, 2012 by Ipstenu
* First completed version.

== Installation ==

No special instructions needed. This plugin is only network activatable.

== Screenshots ==

1. Network Admin Menu

== Credits ==

Thanks to Jan for the idea of moderating and spamming. Thanks to Joey and Helen for reminding me about `get_permalink()` and it's silly inconsistancies.

== Issues ==
* Setting a comment to 'moderate' doesn't always show the 'your comment is in moderation...' to the user, which can be confusing.

== Upgrade Notice ==

= 1.5 =
Please back up your blacklist BEFORE upgrading, as the upgrade will wipe it out. This install fixes that problem. Very, very, sorry.

== Frequently Asked Questions ==

= If I change the blacklist using this plugin, will it change the Comment Blacklist? =

No, this is completely separate.

= Does this list the rejected posters? =

If you set comments to be spammed or moderated, they will show up in the appropriate section of your comments page. If you set comments to be blackholed, no one will ever see them. Ever.

= Will this block user names? =

No, it only blocks by email address.

= Why don't you block IPs? =

I don't find it useful, and I think WP's the wrong tool. If you're blocking IPs, given how dynamic they are, it won't do you any good in the long run. 

= Will this block partial emails? =

Yes. If you put 'example.com' in your list, it will block 'anything@example.com'. **Be very careful when you do this!** If you put in just the letter 'a' for example, you will block all email addresses with that letter. Please only use full domains ('example.com', not just 'example').

= Will this block existing users? =

This plugin will not block any logged in user, *even if* their email is on the list. If you don't want them commenting, delete their accounts. It's assumed if you let someone on your site, you mean for them to have access.

= Does this work on MultiSite? =

Yes, it does.

= Does this work on Single Site installs? =

No, and what's the point? You already have a blacklist for that.

= Does this work on BuddyPress? =

I don't know. Let me know!
